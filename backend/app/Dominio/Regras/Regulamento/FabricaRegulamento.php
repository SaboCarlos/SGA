<?php

declare(strict_types=1);

namespace App\Dominio\Regras\Regulamento;

use InvalidArgumentException;

/**
 * Factory que resolve a estrategia concreta a partir do codigo guardado no curso
 * (cursos.regulamento_avaliacao).
 *
 * Concentra a decisao num unico ponto: nenhum servico faz "new Regulamento...".
 */
final class FabricaRegulamento
{
    /** @var array<string, class-string<RegulamentoAvaliacao>> */
    private const REGULAMENTOS = [
        'PADRAO_ESCN' => RegulamentoPadraoESCN::class,
        'SEM_EXAME' => RegulamentoSemExame::class,
    ];

    public function porCodigo(string $codigo): RegulamentoAvaliacao
    {
        $classe = self::REGULAMENTOS[$codigo] ?? null;

        if ($classe === null) {
            throw new InvalidArgumentException(
                "Regulamento de avaliação desconhecido: {$codigo}. "
                .'Registados: '.implode(', ', array_keys(self::REGULAMENTOS)).'.'
            );
        }

        return new $classe;
    }

    /** @return list<string> */
    public function disponiveis(): array
    {
        return array_keys(self::REGULAMENTOS);
    }
}
