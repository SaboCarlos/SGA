<?php

declare(strict_types=1);

namespace App\Dominio\Repositorios;

/**
 * Abstraccao declarada no dominio e implementada na infra-estrutura (DIP).
 *
 * Interface estreita, apenas com o que os servicos de inscricao, avaliacao e
 * pauta precisam (principio de segregacao de interfaces).
 */
interface RepositorioInscricao
{
    public function porId(string $id): ?object;

    /** @return list<object> */
    public function porTurma(int $turmaId): array;

    /** @return list<int> ids das disciplinas aprovadas pelo estudante */
    public function disciplinasAprovadas(string $estudanteId): array;

    /** @return list<int> ids das disciplinas em que o estudante ja esta inscrito no periodo */
    public function disciplinasInscritasNoPeriodo(string $estudanteId, int $periodoLectivoId): array;

    public function contarActivasNaTurma(int $turmaId): int;

    public function guardar(object $inscricao): object;

    /** @return list<array{classificacao: float, creditos: int}> */
    public function aprovadasComCreditos(string $estudanteId): array;
}
