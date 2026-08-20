<?php

declare(strict_types=1);

use App\Models\Curso;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\Turma;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->admin = Utilizador::administrador()->first();
    $this->ana = Estudante::where('numero_estudante', '2026/0001')->first();
});

it('lista estudantes paginados', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/estudantes')
        ->assertOk()
        ->assertJsonStructure(['pagina', 'por_pagina', 'total', 'total_paginas', 'dados']);
});

it('pesquisa estudantes pelo nome, insensivel a maiusculas', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/estudantes?pesquisa=ana estudante')
        ->assertOk()
        ->assertJsonPath('total', 1);
});

it('regista um novo estudante e cria a conta de utilizador (RF-09)', function () {
    $curso = Curso::first();

    $resposta = $this->actingAs($this->admin)->postJson('/api/v1/estudantes', [
        'nome_completo' => 'Carla Nova',
        'documento_identificacao' => '999888777',
        'data_nascimento' => '2005-01-10',
        'sexo' => 'FEMININO',
        'email' => 'carla@escn.ac.mz',
        'curso_id' => $curso->id,
        'ano_ingresso' => 2026,
    ]);

    $resposta->assertCreated()->assertJsonPath('numero_estudante', '2026/0003');

    expect(Utilizador::where('email', 'carla@escn.ac.mz')->first()?->temPerfil('ESTUDANTE'))->toBeTrue();
});

it('recusa o registo com email ja usado por outro utilizador', function () {
    $curso = Curso::first();

    $this->actingAs($this->admin)->postJson('/api/v1/estudantes', [
        'nome_completo' => 'Duplicado',
        'documento_identificacao' => '111222333',
        'data_nascimento' => '2005-01-10',
        'email' => 'ana@escn.ac.mz',
        'curso_id' => $curso->id,
        'ano_ingresso' => 2026,
    ])->assertStatus(422);
});

it('mostra um estudante especifico', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/v1/estudantes/{$this->ana->id}")
        ->assertOk()
        ->assertJsonPath('numero_estudante', '2026/0001');
});

it('actualiza os dados de um estudante', function () {
    $this->actingAs($this->admin)
        ->putJson("/api/v1/estudantes/{$this->ana->id}", ['telefone' => '840000000'])
        ->assertOk();

    expect($this->ana->fresh()->telefone)->toBe('840000000');
});

it('nao elimina fisicamente um estudante com historico academico', function () {
    $turma = Turma::first();
    Inscricao::create(['estudante_id' => $this->ana->id, 'turma_id' => $turma->id, 'estado' => 'ACTIVA']);

    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/estudantes/{$this->ana->id}")
        ->assertStatus(409);
});

it('inactiva logicamente um estudante sem historico', function () {
    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/estudantes/{$this->ana->id}")
        ->assertNoContent();

    expect($this->ana->fresh()->estado)->toBe('DESISTENTE');
});

it('o proprio estudante consulta o seu aproveitamento', function () {
    $this->actingAs($this->ana->utilizador)
        ->getJson("/api/v1/estudantes/{$this->ana->id}/aproveitamento")
        ->assertOk()
        ->assertJsonStructure(['estudante', 'numero_estudante', 'curso', 'media_ponderada', 'creditos_obtidos', 'creditos_curso', 'percentagem_conclusao', 'anos_lectivos']);
});

it('o coordenador do curso consulta o aproveitamento dos seus estudantes', function () {
    $docenteUtilizador = Utilizador::where('email', 'docente@escn.ac.mz')->first();

    $this->actingAs($docenteUtilizador)
        ->getJson("/api/v1/estudantes/{$this->ana->id}/aproveitamento")
        ->assertOk();
});

it('o administrador consulta o aproveitamento de qualquer estudante', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/v1/estudantes/{$this->ana->id}/aproveitamento")
        ->assertOk();
});
