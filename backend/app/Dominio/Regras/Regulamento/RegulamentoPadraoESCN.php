<?php

declare(strict_types=1);

namespace App\Dominio\Regras\Regulamento;

use App\Dominio\Enums\SituacaoFinal;
use App\Dominio\Enums\SituacaoFrequencia;
use App\Dominio\Excepcoes\EstruturaAvaliacaoInvalida;
use App\Dominio\Valores\NotaAvaliacao;

/**
 * Regulamento em vigor assumido para a ESCN (premissa PR-03 da Fase 1).
 *
 * ATENCAO: os limiares abaixo resultam de uma premissa do grupo e aguardam
 * confirmacao junto do Coordenador do curso (questao aberta 1 da Fase 2).
 * A alteracao dos valores nao exige modificar nenhuma outra classe -- e
 * precisamente esse o objectivo do padrao Strategy (ADR-004).
 */
final class RegulamentoPadraoESCN implements RegulamentoAvaliacao
{
    /** Nota de frequencia a partir da qual o estudante dispensa exame (RN-08). */
    private const DISPENSA = 14.0;

    /** Nota de frequencia minima para admissao a exame (RN-09). */
    private const MINIMO_EXAME = 10.0;

    /** Classificacao final minima de aprovacao (RN-10). */
    private const APROVACAO = 10.0;

    /** Tolerancia na comparacao da soma dos pesos, para evitar erros de virgula flutuante. */
    private const TOLERANCIA_PESO = 0.01;

    public function codigo(): string
    {
        return 'PADRAO_ESCN';
    }

    public function calcularFrequencia(array $notas): float
    {
        $frequencia = array_values(array_filter(
            $notas,
            static fn (NotaAvaliacao $n) => $n->contaParaFrequencia()
        ));

        $somaPesos = array_sum(array_map(
            static fn (NotaAvaliacao $n) => $n->peso,
            $frequencia
        ));

        // RN-06
        if (abs($somaPesos - 100.0) > self::TOLERANCIA_PESO) {
            throw new EstruturaAvaliacaoInvalida($somaPesos);
        }

        $ponderada = 0.0;
        foreach ($frequencia as $nota) {
            $ponderada += $nota->valorEfectivo() * $nota->peso / 100;
        }

        // RN-07: uma casa decimal
        return $this->arredondar($ponderada);
    }

    public function determinarSituacaoFrequencia(float $notaFrequencia): SituacaoFrequencia
    {
        return match (true) {
            $notaFrequencia >= self::DISPENSA => SituacaoFrequencia::DISPENSADO,
            $notaFrequencia >= self::MINIMO_EXAME => SituacaoFrequencia::ADMITIDO_EXAME,
            default => SituacaoFrequencia::REPROVADO_FREQUENCIA,
        };
    }

    public function calcularClassificacaoFinal(
        float $notaFrequencia,
        ?float $notaExame = null
    ): ?float {
        $situacao = $this->determinarSituacaoFrequencia($notaFrequencia);

        // Reprovado por frequencia: nao e admitido a exame; a final e a propria
        // frequencia, o que preserva o valor comunicado ao estudante (RN-09).
        if ($situacao === SituacaoFrequencia::REPROVADO_FREQUENCIA) {
            return $this->arredondar($notaFrequencia);
        }

        // Dispensado: a classificacao final e a nota de frequencia (RN-08).
        if ($situacao === SituacaoFrequencia::DISPENSADO) {
            return $this->arredondar($notaFrequencia);
        }

        // Admitido a exame e ainda sem exame realizado: disciplina em curso.
        if ($notaExame === null) {
            return null;
        }

        // Media simples entre frequencia e exame, com peso igual.
        // NOTA: a ponderacao definitiva aguarda confirmacao (questao aberta 2 da Fase 2).
        return $this->arredondar(($notaFrequencia + $notaExame) / 2);
    }

    public function determinarSituacaoFinal(?float $classificacaoFinal): SituacaoFinal
    {
        if ($classificacaoFinal === null) {
            return SituacaoFinal::EM_CURSO;
        }

        return $classificacaoFinal >= self::APROVACAO
            ? SituacaoFinal::APROVADO
            : SituacaoFinal::REPROVADO;
    }

    public function calcularMediaCurso(array $aprovadas): ?float
    {
        if ($aprovadas === []) {
            return null;
        }

        $creditos = array_sum(array_column($aprovadas, 'creditos'));

        if ($creditos === 0) {
            return null;
        }

        $ponderada = 0.0;
        foreach ($aprovadas as $disciplina) {
            $ponderada += $disciplina['classificacao'] * $disciplina['creditos'];
        }

        return $this->arredondar($ponderada / $creditos);
    }

    /**
     * Arredondamento a uma casa decimal, meio para cima (round half up).
     *
     * Explicitado num metodo proprio porque o criterio de arredondamento e uma
     * regra academica, nao um detalhe tecnico: 13,95 tem de dar 14,0 e conceder
     * dispensa. Alterar isto altera situacoes de estudantes.
     */
    private function arredondar(float $valor): float
    {
        return round($valor, 1);
    }
}
