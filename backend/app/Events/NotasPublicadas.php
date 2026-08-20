<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** Evento de dominio (Observer, ADR-005). */
final class NotasPublicadas
{
    use Dispatchable;

    public function __construct(
        public readonly object $avaliacao,
        public readonly array $resumo,
    ) {}
}
