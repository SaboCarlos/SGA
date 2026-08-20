<?php

declare(strict_types=1);

namespace App\Dominio\Valores;

use App\Dominio\Enums\TipoAvaliacao;
use InvalidArgumentException;

/**
 * Objecto de valor imutavel: a nota de um estudante numa avaliacao.
 *
 * Existe para que o regulamento de avaliacao trabalhe sobre dados puros, sem
 * dependencia de Eloquent nem da base de dados. E o que torna as regras RN-06 a
 * RN-12 testaveis em milissegundos (ADR-001, principio DIP).
 */
final readonly class NotaAvaliacao
{
    public function __construct(
        public TipoAvaliacao $tipo,
        public float $peso,
        public ?float $valor,
        public bool $faltou = false,
    ) {
        if ($peso <= 0 || $peso > 100) {
            throw new InvalidArgumentException("Peso invalido: {$peso}. Admitido: (0, 100].");
        }

        if ($valor !== null && ($valor < 0 || $valor > 20)) {
            throw new InvalidArgumentException("Classificacao invalida: {$valor}. Admitido: [0, 20].");
        }

        if ($valor === null && ! $faltou) {
            throw new InvalidArgumentException('Nota sem valor deve estar marcada como falta.');
        }
    }

    /** Valor usado no calculo: a falta conta como zero (US-20). */
    public function valorEfectivo(): float
    {
        return $this->faltou ? 0.0 : (float) $this->valor;
    }

    public function contaParaFrequencia(): bool
    {
        return $this->tipo->contaParaFrequencia();
    }
}
