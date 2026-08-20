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
     * @return array{publicadas: int, dispensados: int, admitidos_exame: int, reprovados_frequencia: int}
     */
    public function publicar(object $avaliacao, bool $confirmarIncompleta = false): array
    {
        $inscricoes = $this->inscricoes->porTurma($avaliacao->turma_id);
        $lancadas = $this->classificacoes->porAvaliacao($avaliacao->id);

        $emFalta = $this->estudantesSemClassificacao($inscricoes, $lancadas);

        if ($emFalta !== [] && ! $confirmarIncompleta) {
            throw new RegraNegocioViolada(
                'US-18',
                sprintf('Existem %d estudantes sem classificação nem marca de falta.', count($emFalta)),
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
        ];

        foreach ($inscricoes as $inscricao) {
            $notas = $this->notasDe($inscricao);

            if ($notas === []) {
                continue;
            }

            $frequencia = $regulamento->calcularFrequencia($notas);
            $situacao = $regulamento->determinarSituacaoFrequencia($frequencia);
            $final = $regulamento->calcularClassificacaoFinal($frequencia, $inscricao->nota_exame ?? null);

            $inscricao->nota_frequencia = $frequencia;
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

        $this->auditoria->registar('avaliacao.publicada', 'avaliacao', (string) $avaliacao->id, null, $resumo);

        // Observer: os ouvintes (notificacoes, pauta provisoria) reagem depois da
        // transaccao. Uma falha no envio nao anula a publicacao (US-18, fluxo A5).
        event(new NotasPublicadas($avaliacao, $resumo));

        return $resumo;
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

    /** @return list<NotaAvaliacao> */
    private function notasDe(object $inscricao): array
    {
        $notas = [];

        foreach ($this->classificacoes->porInscricao($inscricao->id) as $classificacao) {
            $notas[] = new NotaAvaliacao(
                TipoAvaliacao::from($classificacao->tipo),
                (float) $classificacao->peso,
                $classificacao->valor === null ? null : (float) $classificacao->valor,
                (bool) $classificacao->faltou,
            );
        }

        return $notas;
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
