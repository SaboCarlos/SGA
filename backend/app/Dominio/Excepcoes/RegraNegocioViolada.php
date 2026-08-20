<?php

declare(strict_types=1);

namespace App\Dominio\Excepcoes;

use RuntimeException;

/**
 * Excepcao de dominio que transporta o codigo da regra violada (RN-xx).
 *
 * O codigo e devolvido ao cliente no corpo do erro, tornando a resposta da API
 * rastreavel aos requisitos da Fase 1 (Fase 3, seccao 8.3).
 */
class RegraNegocioViolada extends RuntimeException
{
    public function __construct(
        public readonly string $codigoRegra,
        string $mensagem,
        public readonly ?string $campo = null,
    ) {
        parent::__construct($mensagem);
    }

    public function paraResposta(): array
    {
        return array_filter([
            'codigo' => $this->codigoRegra,
            'mensagem' => $this->getMessage(),
            'campo' => $this->campo,
        ], static fn ($v) => $v !== null);
    }
}
