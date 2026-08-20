<?php

declare(strict_types=1);

namespace App\Dominio\Regras;

use App\Dominio\Excepcoes\RegraNegocioViolada;
use App\Dominio\Valores\ContextoInscricao;
use App\Dominio\Valores\ResultadoValidacao;

/**
 * Regras de inscricao RN-01 a RN-05 (UC14 / US-13).
 *
 * Classe de dominio puro: nao conhece HTTP, Eloquent nem a base de dados.
 * As mesmas regras estao tambem garantidas na base de dados onde ha risco de
 * concorrencia (RN-03 por UNIQUE, RN-04 por trigger) -- duplicacao deliberada,
 * registada em ADR-008.
 */
final class ValidadorInscricao
{
    public function validar(ContextoInscricao $ctx): ResultadoValidacao
    {
        $resultado = new ResultadoValidacao;

        $this->validarPeriodo($ctx, $resultado);          // RN-05
        $this->validarPlanoCurricular($ctx, $resultado);   // RN-01
        $this->validarPreRequisitos($ctx, $resultado);     // RN-02
        $this->validarDuplicacao($ctx, $resultado);        // RN-03
        $this->validarCapacidade($ctx, $resultado);        // RN-04

        return $resultado;
    }

    /** RN-05: so e possivel inscrever-se com o periodo de inscricoes aberto. */
    private function validarPeriodo(ContextoInscricao $ctx, ResultadoValidacao $r): void
    {
        if (! $ctx->periodoInscricoesAberto) {
            $r->acrescentar(new RegraNegocioViolada(
                'RN-05',
                'O período de inscrições não está aberto.',
                'periodo'
            ));
        }
    }

    /** RN-01: a disciplina tem de pertencer ao plano curricular do curso. */
    private function validarPlanoCurricular(ContextoInscricao $ctx, ResultadoValidacao $r): void
    {
        if (! in_array($ctx->disciplinaPretendidaId, $ctx->disciplinasDoPlano, true)) {
            $r->acrescentar(new RegraNegocioViolada(
                'RN-01',
                sprintf(
                    'A disciplina %s não pertence ao plano curricular do curso do estudante.',
                    $ctx->designacaoDisciplina ?: "#{$ctx->disciplinaPretendidaId}"
                ),
                'disciplina'
            ));
        }
    }

    /** RN-02: todos os pre-requisitos devem estar aprovados. */
    private function validarPreRequisitos(ContextoInscricao $ctx, ResultadoValidacao $r): void
    {
        if ($ctx->dispensarPreRequisitos) {
            return;   // UC18: dispensa administrativa, com justificacao e auditoria
        }

        $emFalta = array_values(array_diff(
            $ctx->preRequisitosDaDisciplina,
            $ctx->disciplinasAprovadas
        ));

        if ($emFalta === []) {
            return;
        }

        $nomes = array_map(
            static fn (int $id) => $ctx->designacoes[$id] ?? "#{$id}",
            $emFalta
        );

        $r->acrescentar(new RegraNegocioViolada(
            'RN-02',
            sprintf(
                'Pré-requisito não satisfeito: %s não %s aprovada%s.',
                implode(', ', $nomes),
                count($nomes) === 1 ? 'está' : 'estão',
                count($nomes) === 1 ? '' : 's'
            ),
            'pre_requisitos'
        ));
    }

    /** RN-03: nao pode existir inscricao repetida na mesma disciplina no periodo. */
    private function validarDuplicacao(ContextoInscricao $ctx, ResultadoValidacao $r): void
    {
        if (in_array($ctx->disciplinaPretendidaId, $ctx->disciplinasJaInscritas, true)) {
            $r->acrescentar(new RegraNegocioViolada(
                'RN-03',
                sprintf(
                    'O estudante já está inscrito em %s neste semestre.',
                    $ctx->designacaoDisciplina ?: "#{$ctx->disciplinaPretendidaId}"
                ),
                'disciplina'
            ));
        }
    }

    /** RN-04: a turma nao pode exceder a capacidade maxima. */
    private function validarCapacidade(ContextoInscricao $ctx, ResultadoValidacao $r): void
    {
        if ($ctx->vagasDisponiveis() <= 0) {
            $r->acrescentar(new RegraNegocioViolada(
                'RN-04',
                sprintf(
                    'A turma de %s não tem vagas disponíveis (%d/%d).',
                    $ctx->designacaoDisciplina ?: "#{$ctx->disciplinaPretendidaId}",
                    $ctx->inscritosNaTurma,
                    $ctx->capacidadeDaTurma
                ),
                'turma'
            ));
        }
    }
}
