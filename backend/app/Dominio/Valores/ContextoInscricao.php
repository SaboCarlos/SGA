<?php

declare(strict_types=1);

namespace App\Dominio\Valores;

/**
 * Dados necessarios para validar uma inscricao (RN-01 a RN-05), reunidos pela
 * camada de aplicacao e entregues ao validador como dados puros.
 *
 * Esta separacao e o que permite testar as cinco regras sem base de dados.
 */
final readonly class ContextoInscricao
{
    /**
     * @param  list<int>  $disciplinasDoPlano  ids das disciplinas do plano curricular do curso do estudante
     * @param  list<int>  $disciplinasAprovadas  ids das disciplinas ja aprovadas pelo estudante
     * @param  list<int>  $preRequisitosDaDisciplina  ids exigidos pela disciplina pretendida
     * @param  list<int>  $disciplinasJaInscritas  ids em que o estudante ja esta inscrito no periodo
     */
    public function __construct(
        public int $disciplinaPretendidaId,
        public array $disciplinasDoPlano,
        public array $disciplinasAprovadas,
        public array $preRequisitosDaDisciplina,
        public array $disciplinasJaInscritas,
        public int $inscritosNaTurma,
        public int $capacidadeDaTurma,
        public bool $periodoInscricoesAberto,
        public string $designacaoDisciplina = '',
        /** @var array<int, string> mapa id => designacao, para mensagens legiveis */
        public array $designacoes = [],
        public bool $dispensarPreRequisitos = false,
    ) {}

    public function vagasDisponiveis(): int
    {
        return max(0, $this->capacidadeDaTurma - $this->inscritosNaTurma);
    }
}
