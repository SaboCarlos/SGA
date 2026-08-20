<?php

declare(strict_types=1);

namespace App\Infraestrutura\Repositorios;

use App\Dominio\Repositorios\RepositorioClassificacao;
use App\Models\Classificacao;
use Illuminate\Support\Facades\DB;

final class RepositorioClassificacaoEloquent implements RepositorioClassificacao
{
    public function porAvaliacao(int $avaliacaoId): array
    {
        return Classificacao::query()->where('avaliacao_id', $avaliacaoId)->get()->all();
    }

    /**
     * Notas de uma inscricao, com o tipo e o peso da respectiva avaliacao —
     * campos que ServicoAvaliacao::notasDe() espera encontrar directamente no
     * objecto (nao existem na tabela classificacoes, so na de avaliacoes).
     */
    public function porInscricao(string $inscricaoId): array
    {
        return Classificacao::query()
            ->join('avaliacoes', 'avaliacoes.id', '=', 'classificacoes.avaliacao_id')
            ->where('classificacoes.inscricao_id', $inscricaoId)
            ->get([
                'classificacoes.*',
                'avaliacoes.tipo as tipo',
                'avaliacoes.peso as peso',
            ])
            ->all();
    }

    public function guardarLote(array $classificacoes): void
    {
        DB::transaction(function () use ($classificacoes): void {
            foreach ($classificacoes as $dados) {
                Classificacao::updateOrCreate(
                    [
                        'avaliacao_id' => $dados['avaliacao_id'],
                        'inscricao_id' => $dados['inscricao_id'],
                    ],
                    [
                        'valor' => $dados['valor'] ?? null,
                        'faltou' => $dados['faltou'] ?? false,
                        'lancada_por' => $dados['lancada_por'] ?? null,
                        'lancada_em' => now(),
                    ]
                );
            }
        });
    }

    public function publicarPorAvaliacao(int $avaliacaoId): int
    {
        return Classificacao::query()
            ->where('avaliacao_id', $avaliacaoId)
            ->update(['estado' => 'PUBLICADA', 'publicada_em' => now()]);
    }
}
