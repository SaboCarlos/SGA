<?php

declare(strict_types=1);

namespace App\Servicos;

use App\Models\RegistoAuditoria;

/**
 * Registo de auditoria (RF-61, RNF-31).
 *
 * Apenas escreve: nao existe metodo de actualizacao nem de eliminacao, em
 * coerencia com o trigger de imutabilidade da base de dados (RNF-32, ADR-008).
 */
final class ServicoAuditoria
{
    public function registar(
        string $acao,
        string $entidade,
        string $entidadeId,
        ?array $valorAnterior = null,
        ?array $valorNovo = null,
        ?string $justificacao = null,
    ): void {
        RegistoAuditoria::create([
            'utilizador_id' => auth()->id(),
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_id' => $entidadeId,
            'valor_anterior' => $valorAnterior,
            'valor_novo' => $valorNovo,
            'justificacao' => $justificacao,
            'endereco_ip' => request()->ip(),
        ]);
    }
}
