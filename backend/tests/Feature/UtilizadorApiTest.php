<?php

declare(strict_types=1);

use App\Models\Estudante;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->admin = Utilizador::administrador()->first();
    $this->ana = Estudante::where('numero_estudante', '2026/0001')->first();
});

it('o administrador lista todos os utilizadores', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/utilizadores')
        ->assertOk();
});

it('recusa a gestao de utilizadores a quem nao e administrador', function () {
    $this->actingAs($this->ana->utilizador)
        ->getJson('/api/v1/utilizadores')
        ->assertForbidden();
});

it('o administrador cria um utilizador com perfis', function () {
    $resposta = $this->actingAs($this->admin)->postJson('/api/v1/utilizadores', [
        'nome' => 'Nova Conta',
        'email' => 'nova@escn.ac.mz',
        'palavra_passe' => 'palavra-passe-forte',
        'perfis' => ['ADMINISTRADOR'],
    ]);

    $resposta->assertCreated()->assertJsonPath('perfis.0', 'ADMINISTRADOR');
});

it('o administrador altera os perfis de um utilizador', function () {
    $resposta = $this->actingAs($this->admin)
        ->putJson("/api/v1/utilizadores/{$this->ana->utilizador_id}", ['perfis' => ['ESTUDANTE', 'COORDENADOR']])
        ->assertOk();

    expect($resposta->json('perfis'))->toEqualCanonicalizing(['ESTUDANTE', 'COORDENADOR']);
});

it('o administrador desactiva um utilizador e revoga os seus tokens', function () {
    $token = $this->ana->utilizador->createToken('t')->plainTextToken;

    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/utilizadores/{$this->ana->utilizador_id}")
        ->assertNoContent();

    expect($this->ana->utilizador->fresh()->activo)->toBeFalse()
        ->and($this->ana->utilizador->fresh()->tokens()->count())->toBe(0);
});
