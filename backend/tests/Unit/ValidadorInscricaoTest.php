<?php

declare(strict_types=1);

use App\Dominio\Regras\ValidadorInscricao;
use App\Dominio\Valores\ContextoInscricao;

/** Testes unitarios das regras de inscricao RN-01 a RN-05 (US-13). */
function contexto(array $alteracoes = []): ContextoInscricao
{
    return new ContextoInscricao(
        disciplinaPretendidaId: 301,
        disciplinasDoPlano: $alteracoes['plano'] ?? [101, 201, 301],
        disciplinasAprovadas: $alteracoes['aprovadas'] ?? [101, 201],
        preRequisitosDaDisciplina: $alteracoes['prereq'] ?? [201],
        disciplinasJaInscritas: $alteracoes['inscritas'] ?? [],
        inscritosNaTurma: $alteracoes['inscritos'] ?? 10,
        capacidadeDaTurma: $alteracoes['capacidade'] ?? 30,
        periodoInscricoesAberto: $alteracoes['aberto'] ?? true,
        designacaoDisciplina: 'Engenharia de Software',
        designacoes: [101 => 'Programação I', 201 => 'Programação II'],
        dispensarPreRequisitos: $alteracoes['dispensa'] ?? false,
    );
}

beforeEach(function () {
    $this->validador = new ValidadorInscricao;
});

it('aceita uma inscricao que satisfaz todas as regras', function () {
    expect($this->validador->validar(contexto())->valido())->toBeTrue();
});

it('recusa a inscricao identificando a regra violada', function (array $alteracoes, string $codigo) {
    $resultado = $this->validador->validar(contexto($alteracoes));

    expect($resultado->valido())->toBeFalse()
        ->and($resultado->codigos())->toContain($codigo);
})->with([
    'periodo fechado' => [['aberto' => false],       'RN-05'],
    'fora do plano' => [['plano' => [101, 201]],   'RN-01'],
    'pre-requisito falta' => [['aprovadas' => [101]],    'RN-02'],
    'inscricao duplicada' => [['inscritas' => [301]],    'RN-03'],
    'turma sem vagas' => [['inscritos' => 30],       'RN-04'],
]);

it('acumula todas as violacoes em vez de parar na primeira', function () {
    $resultado = $this->validador->validar(contexto([
        'aberto' => false,
        'plano' => [101, 201],
        'aprovadas' => [101],
        'inscritos' => 30,
    ]));

    expect($resultado->codigos())->toBe(['RN-05', 'RN-01', 'RN-02', 'RN-04']);
});

it('identifica a disciplina em falta na mensagem de pre-requisito', function () {
    $violacao = $this->validador->validar(contexto(['aprovadas' => [101]]))->primeira();

    expect($violacao->getMessage())->toContain('Programação II');
});

it('permite dispensa administrativa de pre-requisitos (UC18)', function () {
    $resultado = $this->validador->validar(contexto([
        'aprovadas' => [101],
        'dispensa' => true,
    ]));

    expect($resultado->valido())->toBeTrue();
});
