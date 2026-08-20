<?php

declare(strict_types=1);

namespace App\Dominio\Excepcoes;

/** RN-06: a soma dos pesos das avaliacoes de frequencia deve ser 100%. */
final class EstruturaAvaliacaoInvalida extends RegraNegocioViolada
{
    public function __construct(public readonly float $somaPesos)
    {
        parent::__construct(
            'RN-06',
            sprintf(
                'A soma dos pesos das avaliações de frequência é %.2f%%, quando deveria ser 100%%.',
                $somaPesos
            ),
            'avaliacoes'
        );
    }
}
