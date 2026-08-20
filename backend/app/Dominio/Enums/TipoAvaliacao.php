<?php

declare(strict_types=1);

namespace App\Dominio\Enums;

enum TipoAvaliacao: string
{
    case TESTE = 'TESTE';
    case TRABALHO = 'TRABALHO';
    case PRATICA = 'PRATICA';
    case EXAME = 'EXAME';

    /** O exame nao entra no calculo da nota de frequencia (RN-07). */
    public function contaParaFrequencia(): bool
    {
        return $this !== self::EXAME;
    }
}
