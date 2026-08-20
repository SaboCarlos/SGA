<?php

declare(strict_types=1);

namespace App\Dominio\Regras\Regulamento;

use App\Dominio\Enums\SituacaoFinal;
use App\Dominio\Enums\SituacaoFrequencia;
use App\Dominio\Excepcoes\EstruturaAvaliacaoInvalida;
use App\Dominio\Valores\NotaAvaliacao;

/**
 * Regulamento de avaliacao continua, sem exame final.
 *
 * Existe para demonstrar o padrao Strategy em funcionamento: acrescentar este
 * regulamento nao exigiu qualquer alteracao em ServicoAvaliacao nem nos
 * controladores (principio aberto/fechado).
 */
final class RegulamentoSemExame implements RegulamentoAvaliacao
{
    private const APROVACAO = 10.0;

    public function codigo(): string
    {
        return 'SEM_EXAME';
    }

    public function calcularFrequencia(array $notas): float
    {
        $somaPesos = array_sum(array_map(
            static fn (NotaAvaliacao $n) => $n->peso,
            $notas
        ));

        if (abs($somaPesos - 100.0) > 0.01) {
            throw new EstruturaAvaliacaoInvalida($somaPesos);
        }

        $ponderada = 0.0;
        foreach ($notas as $nota) {
            $ponderada += $nota->valorEfectivo() * $nota->peso / 100;
        }

        return round($ponderada, 1);
    }

    /** Sem exame, a frequencia decide directamente a aprovacao. */
    public function determinarSituacaoFrequencia(float $notaFrequencia): SituacaoFrequencia
    {
        return $notaFrequencia >= self::APROVACAO
            ? SituacaoFrequencia::DISPENSADO
            : SituacaoFrequencia::REPROVADO_FREQUENCIA;
    }

    public function calcularClassificacaoFinal(
        float $notaFrequencia,
        ?float $notaExame = null
    ): ?float {
        return round($notaFrequencia, 1);
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
        foreach ($aprovadas as $d) {
            $ponderada += $d['classificacao'] * $d['creditos'];
        }

        return round($ponderada / $creditos, 1);
    }
}
