<?php

declare(strict_types=1);

namespace App\Servicos;

use App\Dominio\Enums\SituacaoFrequencia;
use App\Dominio\Enums\TipoAvaliacao;
use App\Dominio\Excepcoes\RegraNegocioViolada;
use App\Dominio\Regras\Regulamento\FabricaRegulamento;
use App\Dominio\Repositorios\RepositorioClassificacao;
use App\Dominio\Repositorios\RepositorioInscricao;
use App\Dominio\Valores\NotaAvaliacao;
use App\Events\NotasPublicadas;

/**
 * Caso de uso UC21: publicacao de classificacoes e recalculo dos resultados.
 *
 * Depende exclusivamente de abstraccoes (DIP). Nao conhece Eloquent, nem HTTP,
 * nem as regras concretas de calculo -- delega-as no regulamento resolvido pela
 * fabrica a partir do curso (Strategy, ADR-004).
 */
final class ServicoAvaliacao
{
    public function __construct(
        private readonly RepositorioInscricao $inscricoes,
        private readonly RepositorioClassificacao $classificacoes,
        private readonly FabricaRegulamento $fabrica,
        private readonly ServicoAuditoria $auditoria,
    ) {}

    /**
     * Publica as notas de UMA avaliacao. O docente publica cada avaliacao a
     * medida que a corrige (Teste 1 em Marco, Trabalho em Maio...), por isso
     * os resultados so se calculam quando ha dados para isso:
     *
     *  - a nota de frequencia (RN-07), quando todas as avaliacoes de
     *    frequencia da turma estiverem publicadas -- media ponderada entre
     *    elas, pelos pesos relativos;
     *  - a classificacao final, quando o exame for publicado (para quem foi
     *    admitido) ou logo com a frequencia (dispensados e excluidos).
     *
     * @param  list<array{id: int, tipo: string, peso: float, estado: string}>  $estrutura
     *                                                                                      todas as avaliacoes da turma (RN-06: as de frequencia somam 100%; o exame conta a parte)
     * @return array{publicadas: int, dispensados: int, admitidos_exame: int, reprovados_frequencia: int, frequencia_calculada: bool}
     */
    public function publicar(object $avaliacao, bool $confirmarIncompleta = false, array $estrutura = []): array
    {
        // RN-06: os testes, trabalhos e praticas (frequencia) somam 100%. O
        // exame fica de fora: conta a parte na nota final, (frequencia + exame) / 2.
        $somaPesos = round(array_sum(array_column(
            array_filter($estrutura, static fn (array $a) => TipoAvaliacao::from($a['tipo'])->contaParaFrequencia()),
            'peso'
        )), 2);
        if ($estrutura !== [] && abs($somaPesos - 100.0) > 0.01) {
            throw new RegraNegocioViolada(
                'RN-06',
                sprintf(
                    'Os testes e trabalhos desta disciplina somam %s%%. Ajuste os pesos para somarem 100%% antes de publicar (o exame conta à parte).',
                    rtrim(rtrim(number_format($somaPesos, 2, ',', ''), '0'), ',')
                ),
                'peso'
            );
        }

        $inscricoes = $this->inscricoes->porTurma($avaliacao->turma_id);
        $lancadas = $this->classificacoes->porAvaliacao($avaliacao->id);

        $emFalta = $this->estudantesSemClassificacao($inscricoes, $lancadas);

        if ($emFalta !== [] && ! $confirmarIncompleta) {
            throw new RegraNegocioViolada(
                'US-18',
                sprintf('Há %d aluno(s) sem nota nem falta marcada.', count($emFalta)),
                'classificacoes'
            );
        }

        $regulamento = $this->fabrica->porCodigo($avaliacao->regulamento_avaliacao);

        $publicadas = $this->classificacoes->publicarPorAvaliacao($avaliacao->id);

        $resumo = [
            'publicadas' => $publicadas,
            'dispensados' => 0,
            'admitidos_exame' => 0,
            'reprovados_frequencia' => 0,
            'frequencia_calculada' => false,
        ];

        // Sem estrutura (chamada antiga), a avaliacao publicada e a unica conhecida.
        if ($estrutura === []) {
            $estrutura = [['id' => (int) $avaliacao->id, 'tipo' => (string) $avaliacao->tipo, 'peso' => (float) $avaliacao->peso, 'estado' => 'PUBLICADA']];
        }

        $publicada = static fn (array $a) => $a['estado'] === 'PUBLICADA' || $a['id'] === (int) $avaliacao->id;
        $deFrequencia = array_values(array_filter($estrutura, static fn (array $a) => TipoAvaliacao::from($a['tipo'])->contaParaFrequencia()));
        $exames = array_values(array_filter($estrutura, static fn (array $a) => ! TipoAvaliacao::from($a['tipo'])->contaParaFrequencia()));

        $frequenciaCompleta = $deFrequencia !== [] && count(array_filter($deFrequencia, $publicada)) === count($deFrequencia);
        $exame = $exames[0] ?? null;
        $examePublicado = $exame !== null && $publicada($exame);

        if (! $frequenciaCompleta) {
            $this->registarEvento($avaliacao, $resumo);

            return $resumo;
        }

        $resumo['frequencia_calculada'] = true;
        $somaFrequencia = array_sum(array_column($deFrequencia, 'peso'));

        foreach ($inscricoes as $inscricao) {
            $notasPorAvaliacao = [];
            foreach ($this->classificacoes->porInscricao($inscricao->id) as $c) {
                $notasPorAvaliacao[(int) $c->avaliacao_id] = $c;
            }

            // RN-07: media ponderada das avaliacoes de frequencia, com os pesos
            // relativos entre elas (30% + 20% contam como 60/40). Nota em
            // branco conta como falta, isto e, zero (US-20).
            $notas = array_map(function (array $a) use ($notasPorAvaliacao, $somaFrequencia) {
                $c = $notasPorAvaliacao[$a['id']] ?? null;
                $valor = ($c === null || $c->valor === null) ? null : (float) $c->valor;
                $faltou = $valor === null || (bool) $c->faltou;

                return new NotaAvaliacao(
                    TipoAvaliacao::from($a['tipo']),
                    round((float) $a['peso'] * 100 / $somaFrequencia, 6),
                    $valor,
                    $faltou,
                );
            }, $deFrequencia);

            $frequencia = $regulamento->calcularFrequencia($this->ajustarParaCem($notas));
            $situacao = $regulamento->determinarSituacaoFrequencia($frequencia);

            $notaExame = null;
            if ($examePublicado && $situacao === SituacaoFrequencia::ADMITIDO_EXAME) {
                $c = $notasPorAvaliacao[$exame['id']] ?? null;
                $notaExame = ($c === null || $c->faltou || $c->valor === null) ? 0.0 : (float) $c->valor;
            }

            $final = $regulamento->calcularClassificacaoFinal($frequencia, $notaExame);

            $inscricao->nota_frequencia = $frequencia;
            $inscricao->nota_exame = $notaExame;
            $inscricao->situacao_frequencia = $situacao->value;
            $inscricao->classificacao_final = $final;
            $inscricao->situacao_final = $regulamento->determinarSituacaoFinal($final)->value;

            $this->inscricoes->guardar($inscricao);

            $chave = match ($situacao) {
                SituacaoFrequencia::DISPENSADO => 'dispensados',
                SituacaoFrequencia::ADMITIDO_EXAME => 'admitidos_exame',
                SituacaoFrequencia::REPROVADO_FREQUENCIA => 'reprovados_frequencia',
                default => null,
            };

            if ($chave !== null) {
                $resumo[$chave]++;
            }
        }

        $this->registarEvento($avaliacao, $resumo);

        return $resumo;
    }

    /**
     * Os pesos relativos sao arredondados; o ultimo absorve o residuo para a
     * soma dar exactamente 100 e o regulamento (RN-06) os aceitar.
     *
     * @param  list<NotaAvaliacao>  $notas
     * @return list<NotaAvaliacao>
     */
    private function ajustarParaCem(array $notas): array
    {
        $residuo = 100.0 - array_sum(array_map(static fn (NotaAvaliacao $n) => $n->peso, $notas));
        $ultima = array_pop($notas);

        if ($ultima === null) {
            return [];
        }

        $notas[] = new NotaAvaliacao($ultima->tipo, $ultima->peso + $residuo, $ultima->valor, $ultima->faltou);

        return $notas;
    }

    /** @param array<string, int|bool> $resumo */
    private function registarEvento(object $avaliacao, array $resumo): void
    {
        $this->auditoria->registar('avaliacao.publicada', 'avaliacao', (string) $avaliacao->id, null, $resumo);

        // Observer: os ouvintes (notificacoes, pauta provisoria) reagem depois da
        // transaccao. Uma falha no envio nao anula a publicacao (US-18, fluxo A5).
        event(new NotasPublicadas($avaliacao, $resumo));
    }

    /**
     * RN-12, para a consulta de aproveitamento (UC25). Fica no servico, e nao
     * no controlador, porque resolve o regulamento pela fabrica (Strategy,
     * ADR-004) -- a camada de interface nao pode conhecer classes de
     * App\Dominio\Regras\Regulamento directamente (tests/Arquitectura).
     *
     * @return array{media_ponderada: ?float, creditos_obtidos: int}
     */
    public function calcularMediaCurso(string $estudanteId, string $codigoRegulamento): array
    {
        $aprovadas = $this->inscricoes->aprovadasComCreditos($estudanteId);
        $regulamento = $this->fabrica->porCodigo($codigoRegulamento);

        return [
            'media_ponderada' => $regulamento->calcularMediaCurso($aprovadas),
            'creditos_obtidos' => array_sum(array_column($aprovadas, 'creditos')),
        ];
    }

    /** @return list<string> */
    private function estudantesSemClassificacao(array $inscricoes, array $lancadas): array
    {
        $comNota = [];

        foreach ($lancadas as $c) {
            if ($c->valor !== null || $c->faltou) {
                $comNota[] = $c->inscricao_id;
            }
        }

        return array_values(array_diff(
            array_map(static fn (object $i) => $i->id, $inscricoes),
            $comNota
        ));
    }
}
