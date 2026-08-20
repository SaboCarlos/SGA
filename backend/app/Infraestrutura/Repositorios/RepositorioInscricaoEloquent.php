<?php

declare(strict_types=1);

namespace App\Infraestrutura\Repositorios;

use App\Dominio\Repositorios\RepositorioInscricao;
use App\Models\Inscricao;

/**
 * Implementacao concreta sobre Eloquent/PostgreSQL (camada 5, ADR-001).
 *
 * Respeita o mesmo contrato de RepositorioInscricaoEmMemoria (Liskov): os
 * servicos de aplicacao (camada 3) nao distinguem qual das duas recebem.
 */
final class RepositorioInscricaoEloquent implements RepositorioInscricao
{
    public function porId(string $id): ?object
    {
        return Inscricao::find($id);
    }

    public function porTurma(int $turmaId): array
    {
        return Inscricao::query()
            ->where('turma_id', $turmaId)
            ->where('estado', 'ACTIVA')
            ->get()
            ->all();
    }

    public function disciplinasAprovadas(string $estudanteId): array
    {
        return Inscricao::query()
            ->join('turmas', 'turmas.id', '=', 'inscricoes.turma_id')
            ->where('inscricoes.estudante_id', $estudanteId)
            ->where('inscricoes.situacao_final', 'APROVADO')
            ->pluck('turmas.disciplina_id')
            ->unique()
            ->values()
            ->all();
    }

    public function disciplinasInscritasNoPeriodo(string $estudanteId, int $periodoLectivoId): array
    {
        return Inscricao::query()
            ->join('turmas', 'turmas.id', '=', 'inscricoes.turma_id')
            ->where('inscricoes.estudante_id', $estudanteId)
            ->where('turmas.periodo_lectivo_id', $periodoLectivoId)
            ->where('inscricoes.estado', 'ACTIVA')
            ->pluck('turmas.disciplina_id')
            ->unique()
            ->values()
            ->all();
    }

    public function contarActivasNaTurma(int $turmaId): int
    {
        return Inscricao::query()
            ->where('turma_id', $turmaId)
            ->where('estado', 'ACTIVA')
            ->count();
    }

    public function guardar(object $inscricao): object
    {
        $inscricao->save();

        return $inscricao;
    }

    public function aprovadasComCreditos(string $estudanteId): array
    {
        return Inscricao::query()
            ->join('turmas', 'turmas.id', '=', 'inscricoes.turma_id')
            ->join('disciplinas', 'disciplinas.id', '=', 'turmas.disciplina_id')
            ->where('inscricoes.estudante_id', $estudanteId)
            ->where('inscricoes.situacao_final', 'APROVADO')
            ->get(['inscricoes.classificacao_final', 'disciplinas.creditos'])
            ->map(fn ($linha) => [
                'classificacao' => (float) $linha->classificacao_final,
                'creditos' => (int) $linha->creditos,
            ])
            ->all();
    }
}
