<?php

declare(strict_types=1);

namespace App\Dominio\Repositorios;

interface RepositorioClassificacao
{
    /** @return list<object> */
    public function porAvaliacao(int $avaliacaoId): array;

    /** @return list<object> notas de uma inscricao, com a avaliacao carregada */
    public function porInscricao(string $inscricaoId): array;

    public function guardarLote(array $classificacoes): void;

    public function publicarPorAvaliacao(int $avaliacaoId): int;
}
