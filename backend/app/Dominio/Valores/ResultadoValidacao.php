<?php

declare(strict_types=1);

namespace App\Dominio\Valores;

use App\Dominio\Excepcoes\RegraNegocioViolada;

/**
 * Resultado da validacao de uma inscricao.
 *
 * Reune TODAS as regras violadas em vez de interromper na primeira: o estudante
 * recebe de uma vez os impedimentos, evitando submissoes sucessivas (RNF-21 e
 * diagrama de actividades da Fase 2, onde as validacoes correm em paralelo).
 */
final class ResultadoValidacao
{
    /** @var list<RegraNegocioViolada> */
    private array $violacoes = [];

    public function acrescentar(RegraNegocioViolada $violacao): void
    {
        $this->violacoes[] = $violacao;
    }

    public function valido(): bool
    {
        return $this->violacoes === [];
    }

    /** @return list<RegraNegocioViolada> */
    public function violacoes(): array
    {
        return $this->violacoes;
    }

    public function primeira(): ?RegraNegocioViolada
    {
        return $this->violacoes[0] ?? null;
    }

    /** @return list<array<string, string>> */
    public function paraResposta(): array
    {
        return array_map(
            static fn (RegraNegocioViolada $v) => $v->paraResposta(),
            $this->violacoes
        );
    }

    /** @return list<string> codigos das regras violadas, ex.: ['RN-02', 'RN-04'] */
    public function codigos(): array
    {
        return array_map(
            static fn (RegraNegocioViolada $v) => $v->codigoRegra,
            $this->violacoes
        );
    }
}
