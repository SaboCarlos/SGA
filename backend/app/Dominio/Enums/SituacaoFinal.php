<?php

declare(strict_types=1);

namespace App\Dominio\Enums;

enum SituacaoFinal: string
{
    case EM_CURSO = 'EM_CURSO';
    case APROVADO = 'APROVADO';
    case REPROVADO = 'REPROVADO';
}
