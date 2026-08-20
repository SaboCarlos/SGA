<?php

declare(strict_types=1);

use App\Models\Estudante;
use App\Models\Turma;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

/**
 * Testes de integracao da API de inscricoes (UC14).
 *
 * Verificam o contrato HTTP definido em openapi.yaml: codigos de estado,
 * codigo da regra violada e autorizacao por perfil.
 */
beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
});

it('inscreve o estudante numa turma elegivel', function () {
    $estudante = Estudante::first();
    $turma = Turma::comVagas()->first();

    $resposta = $this->actingAs($estudante->utilizador)
        ->postJson('/api/v1/inscricoes', ['turmas' => [$turma->id]]);

    $resposta->assertCreated()
        ->assertJsonStructure(['emitido_em', 'estudante', 'disciplinas']);

    expect($estudante->inscricoes()->count())->toBe(1);
});

it('recusa a inscricao sem pre-requisito aprovado devolvendo o codigo da regra', function () {
    $estudante = Estudante::semAprovacoes()->first();
    $turma = Turma::comPreRequisitos()->first();

    $this->actingAs($estudante->utilizador)
        ->postJson('/api/v1/inscricoes', ['turmas' => [$turma->id]])
        ->assertStatus(422)
        ->assertJsonPath('codigo', 'RN-02');
});

it('recusa a inscricao quando a turma esta esgotada', function () {
    $estudante = Estudante::first();
    $turma = Turma::esgotada()->first();

    $this->actingAs($estudante->utilizador)
        ->postJson('/api/v1/inscricoes', ['turmas' => [$turma->id]])
        ->assertStatus(422)
        ->assertJsonPath('codigo', 'RN-04');
});

it('impede o estudante de acessar o aproveitamento de outro estudante', function () {
    [$um, $outro] = Estudante::take(2)->get();

    $this->actingAs($um->utilizador)
        ->getJson("/api/v1/estudantes/{$outro->id}/aproveitamento")
        ->assertForbidden();
});

it('exige autenticacao em todas as rotas de negocio', function (string $rota) {
    $this->getJson($rota)->assertUnauthorized();
})->with([
    '/api/v1/estudantes',
    '/api/v1/inscricoes',
    '/api/v1/auditoria',
]);

it('nao permite escrita no registo de auditoria', function () {
    $admin = Utilizador::administrador()->first();

    $this->actingAs($admin)
        ->postJson('/api/v1/auditoria', ['acao' => 'teste'])
        ->assertStatus(405);
});
