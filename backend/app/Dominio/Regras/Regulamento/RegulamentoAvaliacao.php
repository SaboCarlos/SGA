<?php

declare(strict_types=1);

namespace App\Dominio\Regras\Regulamento;

use App\Dominio\Enums\SituacaoFinal;
use App\Dominio\Enums\SituacaoFrequencia;
use App\Dominio\Excepcoes\EstruturaAvaliacaoInvalida;
use App\Dominio\Valores\NotaAvaliacao;

/**
 * Strategy: encapsula as regras de avaliacao RN-06 a RN-12.
 *
 * Contrato que todas as implementacoes devem respeitar (principio de Liskov):
 *   - as classificacoes devolvidas situam-se sempre em [0, 20];
 *   - so sao lancadas excepcoes do tipo RegraNegocioViolada;
 *   - nenhuma implementacao escreve na base de dados nem produz efeitos laterais.
 */
interface RegulamentoAvaliacao
{
    /** Identificador usado em cursos.regulamento_avaliacao. */
    public function codigo(): string;

    /**
     * Nota de frequencia: media ponderada das avaliacoes de frequencia (RN-07).
     *
     * @param  list<NotaAvaliacao>  $notas
     *
     * @throws EstruturaAvaliacaoInvalida se a soma dos pesos != 100
     */
    public function calcularFrequencia(array $notas): float;

    /** Dispensa, admissao a exame ou reprovacao por frequencia (RN-08, RN-09). */
    public function determinarSituacaoFrequencia(float $notaFrequencia): SituacaoFrequencia;

    /** Classificacao final, a partir da frequencia e, se aplicavel, do exame (RN-10). */
    public function calcularClassificacaoFinal(
        float $notaFrequencia,
        ?float $notaExame = null
    ): ?float;

    public function determinarSituacaoFinal(?float $classificacaoFinal): SituacaoFinal;

    /**
     * Media do curso: media das disciplinas aprovadas ponderada por creditos (RN-12).
     *
     * @param  list<array{classificacao: float, creditos: int}>  $aprovadas
     */
    public function calcularMediaCurso(array $aprovadas): ?float;
}
