<?php

declare(strict_types=1);

use App\Models\Disciplina;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->admin = Utilizador::administrador()->first();
});

it('lista disciplinas activas', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/disciplinas')
        ->assertOk()
        ->assertJsonCount(4);
});

it('cria uma disciplina nova', function () {
    $this->actingAs($this->admin)->postJson('/api/v1/disciplinas', [
        'codigo' => 'INF501',
        'designacao' => 'Segurança Informática',
        'creditos' => 6,
        'carga_horaria' => 90,
    ])->assertCreated();
});

it('lista os pre-requisitos de uma disciplina (D2 exige D1)', function () {
    $d2 = Disciplina::where('codigo', 'INF201')->first();

    $this->actingAs($this->admin)
        ->getJson("/api/v1/disciplinas/{$d2->id}/pre-requisitos")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.codigo', 'INF101');
});

it('define um novo pre-requisito (UC11)', function () {
    $d3 = Disciplina::where('codigo', 'INF301')->first();
    $d4 = Disciplina::where('codigo', 'INF302')->first();

    $this->actingAs($this->admin)
        ->postJson("/api/v1/disciplinas/{$d3->id}/pre-requisitos", ['pre_requisito_id' => $d4->id])
        ->assertCreated();

    expect($d3->fresh()->preRequisitos->pluck('id'))->toContain($d4->id);
});

it('recusa uma dependencia circular directa (US-10)', function () {
    $d1 = Disciplina::where('codigo', 'INF101')->first();
    $d2 = Disciplina::where('codigo', 'INF201')->first();

    // D2 ja exige D1 (seeder); tentar o inverso fecharia um ciclo directo.
    $this->actingAs($this->admin)
        ->postJson("/api/v1/disciplinas/{$d1->id}/pre-requisitos", ['pre_requisito_id' => $d2->id])
        ->assertStatus(422)
        ->assertJsonPath('codigo', 'US-10');
});

it('recusa uma dependencia circular indirecta em cadeia', function () {
    $d1 = Disciplina::where('codigo', 'INF101')->first();
    $d2 = Disciplina::where('codigo', 'INF201')->first();
    $d3 = Disciplina::where('codigo', 'INF301')->first();

    // D3 passa a exigir D2 (que ja exige D1). Fechar com D1 -> D3 criaria um ciclo.
    $d3->preRequisitos()->attach($d2->id);

    $this->actingAs($this->admin)
        ->postJson("/api/v1/disciplinas/{$d1->id}/pre-requisitos", ['pre_requisito_id' => $d3->id])
        ->assertStatus(422)
        ->assertJsonPath('codigo', 'US-10');
});

it('recusa uma disciplina como pre-requisito de si mesma', function () {
    $d1 = Disciplina::where('codigo', 'INF101')->first();

    $this->actingAs($this->admin)
        ->postJson("/api/v1/disciplinas/{$d1->id}/pre-requisitos", ['pre_requisito_id' => $d1->id])
        ->assertStatus(422);
});
