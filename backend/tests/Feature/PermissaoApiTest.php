<?php

declare(strict_types=1);

use App\Models\Estudante;
use App\Models\Permissao;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->admin = Utilizador::administrador()->first();
});

it('o administrador lista o catalogo de permissoes', function () {
    Permissao::create(['codigo' => 'classificacao.publicar', 'descricao' => 'Publicar classificações']);

    $this->actingAs($this->admin)
        ->getJson('/api/v1/permissoes')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.codigo', 'classificacao.publicar');
});

it('o administrador cria uma permissao nova', function () {
    $this->actingAs($this->admin)->postJson('/api/v1/permissoes', [
        'codigo' => 'auditoria.consultar',
        'descricao' => 'Consultar o registo de auditoria',
    ])->assertCreated()->assertJsonPath('codigo', 'auditoria.consultar');
});

it('recusa codigo de permissao duplicado', function () {
    Permissao::create(['codigo' => 'auditoria.consultar']);

    $this->actingAs($this->admin)
        ->postJson('/api/v1/permissoes', ['codigo' => 'auditoria.consultar'])
        ->assertStatus(422);
});

it('recusa a gestao de permissoes a quem nao e administrador', function () {
    $ana = Estudante::where('numero_estudante', '2026/0001')->first();

    $this->actingAs($ana->utilizador)
        ->getJson('/api/v1/permissoes')
        ->assertForbidden();
});
