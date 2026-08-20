<?php

declare(strict_types=1);

namespace App\Infraestrutura\Repositorios;

use App\Dominio\Repositorios\RepositorioInscricao;

/**
 * Implementacao em memoria, usada nos testes unitarios.
 *
 * Permite exercitar os servicos de aplicacao sem base de dados: os testes das
 * regras RN-06 a RN-12 correm em milissegundos e sao deterministicos (RNF-24).
 * Respeita o mesmo contrato da implementacao Eloquent (principio de Liskov).
 */
final class RepositorioInscricaoEmMemoria implements RepositorioInscricao
{
    /** @var array<string, object> */
    private array $inscricoes = [];

    /** @var array<string, list<int>> */
    private array $aprovadas = [];

    /** @var array<string, list<array{classificacao: float, creditos: int}>> */
    private array $aprovadasComCreditos = [];

    public function comInscricao(object $inscricao): self
    {
        $this->inscricoes[$inscricao->id] = $inscricao;

        return $this;
    }

    /** @param list<int> $disciplinas */
    public function comAprovacoes(string $estudanteId, array $disciplinas): self
    {
        $this->aprovadas[$estudanteId] = $disciplinas;

        return $this;
    }

    /** @param list<array{classificacao: float, creditos: int}> $aprovadas */
    public function comAprovadasComCreditos(string $estudanteId, array $aprovadas): self
    {
        $this->aprovadasComCreditos[$estudanteId] = $aprovadas;

        return $this;
    }

    public function porId(string $id): ?object
    {
        return $this->inscricoes[$id] ?? null;
    }

    public function porTurma(int $turmaId): array
    {
        return array_values(array_filter(
            $this->inscricoes,
            static fn (object $i) => $i->turma_id === $turmaId && $i->estado === 'ACTIVA'
        ));
    }

    public function disciplinasAprovadas(string $estudanteId): array
    {
        return $this->aprovadas[$estudanteId] ?? [];
    }

    public function disciplinasInscritasNoPeriodo(string $estudanteId, int $periodoLectivoId): array
    {
        $ids = [];

        foreach ($this->inscricoes as $inscricao) {
            if ($inscricao->estudante_id === $estudanteId
                && ($inscricao->periodo_lectivo_id ?? null) === $periodoLectivoId
                && $inscricao->estado === 'ACTIVA') {
                $ids[] = $inscricao->disciplina_id;
            }
        }

        return $ids;
    }

    public function contarActivasNaTurma(int $turmaId): int
    {
        return count($this->porTurma($turmaId));
    }

    public function guardar(object $inscricao): object
    {
        $this->inscricoes[$inscricao->id] = $inscricao;

        return $inscricao;
    }

    public function aprovadasComCreditos(string $estudanteId): array
    {
        return $this->aprovadasComCreditos[$estudanteId] ?? [];
    }
}
