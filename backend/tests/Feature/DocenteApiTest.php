<?php

declare(strict_types=1);

use App\Models\Docente;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->admin = Utilizador::administrador()->first();
});

it('lista docentes activos', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/docentes')
        ->assertOk()
        ->assertJsonCount(1);
});

it('regista um novo docente e cria a conta de utilizador (UC07)', function () {
    $resposta = $this->actingAs($this->admin)->postJson('/api/v1/docentes', [
        'nome_completo' => 'Profa. Joana Sitoe',
        'email' => 'joana@escn.ac.mz',
        'codigo' => 'DOC002',
        'grau_academico' => 'Doutora',
    ]);

    $resposta->assertCreated()->assertJsonPath('codigo', 'DOC002');

    expect(Utilizador::where('email', 'joana@escn.ac.mz')->first()?->temPerfil('DOCENTE'))->toBeTrue();
});

it('actualiza os dados de um docente', function () {
    $docente = Docente::first();

    $this->actingAs($this->admin)
        ->putJson("/api/v1/docentes/{$docente->id}", ['categoria' => 'Docente Associado'])
        ->assertOk()
        ->assertJsonPath('categoria', 'Docente Associado');
});

it('inactiva um docente (eliminacao logica)', function () {
    $docente = Docente::first();

    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/docentes/{$docente->id}")
        ->assertNoContent();

    expect($docente->fresh()->activo)->toBeFalse();
});
