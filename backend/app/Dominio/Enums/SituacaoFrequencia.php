<?php

declare(strict_types=1);

namespace App\Dominio\Enums;

/** Situacao do estudante face a frequencia (RN-08, RN-09). */
enum SituacaoFrequencia: string
{
    case EM_CURSO = 'EM_CURSO';
    case DISPENSADO = 'DISPENSADO';
    case ADMITIDO_EXAME = 'ADMITIDO_EXAME';
    case REPROVADO_FREQUENCIA = 'REPROVADO_FREQUENCIA';

    public function admiteExame(): bool
    {
        return $this === self::ADMITIDO_EXAME;
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::EM_CURSO => 'Em curso',
            self::DISPENSADO => 'Dispensado',
            self::ADMITIDO_EXAME => 'Admitido a exame',
            self::REPROVADO_FREQUENCIA => 'Reprovado por frequência',
        };
    }
}
