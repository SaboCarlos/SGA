<?php

declare(strict_types=1);

use App\Models\AnoLectivo;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->admin = Utilizador::administrador()->first();
});

it('lista os anos lectivos', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/anos-lectivos')
        ->assertOk()
        ->assertJsonCount(1);
});

it('cria um ano lectivo novo, planeado (UC12)', function () {
    $this->actingAs($this->admin)->postJson('/api/v1/anos-lectivos', [
        'designacao' => '2027',
        'data_inicio' => '2027-01-01',
        'data_fim' => '2027-12-31',
    ])->assertCreated()->assertJsonPath('estado', 'PLANEADO');
});

it('recusa duas datas invertidas', function () {
    $this->actingAs($this->admin)->postJson('/api/v1/anos-lectivos', [
        'designacao' => '2028',
        'data_inicio' => '2028-12-31',
        'data_fim' => '2028-01-01',
    ])->assertStatus(422);
});

it('recusa activar um segundo ano lectivo enquanto outro esta activo', function () {
    $novo = AnoLectivo::create([
        'designacao' => '2027', 'data_inicio' => '2027-01-01', 'data_fim' => '2027-12-31', 'estado' => 'PLANEADO',
    ]);

    // O seeder ja deixa "2026" ACTIVO — activar outro em simultaneo viola a
    // restricao de unicidade da Fase 2 (indice unico parcial).
    $this->actingAs($this->admin)
        ->postJson("/api/v1/anos-lectivos/{$novo->id}/activar")
        ->assertStatus(409);
});

it('activa um ano lectivo depois de encerrar o anterior', function () {
    $anoActivo = AnoLectivo::where('estado', 'ACTIVO')->first();
    $novo = AnoLectivo::create([
        'designacao' => '2027', 'data_inicio' => '2027-01-01', 'data_fim' => '2027-12-31', 'estado' => 'PLANEADO',
    ]);

    $this->actingAs($this->admin)->postJson("/api/v1/anos-lectivos/{$anoActivo->id}/encerrar")->assertOk();
    $this->actingAs($this->admin)->postJson("/api/v1/anos-lectivos/{$novo->id}/activar")
        ->assertOk()->assertJsonPath('estado', 'ACTIVO');
});

it('cria um periodo lectivo para o ano (US-12)', function () {
    $novo = AnoLectivo::create([
        'designacao' => '2027', 'data_inicio' => '2027-01-01', 'data_fim' => '2027-12-31', 'estado' => 'PLANEADO',
    ]);

    $this->actingAs($this->admin)->postJson("/api/v1/anos-lectivos/{$novo->id}/periodos", [
        'semestre' => 1,
        'inscricao_inicio' => '2027-01-10',
        'inscricao_fim' => '2027-01-25',
        'notas_inicio' => '2027-06-01',
        'notas_fim' => '2027-06-15',
    ])->assertCreated();
});

it('recusa dois periodos para o mesmo semestre do mesmo ano', function () {
    $anoActivo = AnoLectivo::where('estado', 'ACTIVO')->first();

    $this->actingAs($this->admin)->postJson("/api/v1/anos-lectivos/{$anoActivo->id}/periodos", [
        'semestre' => 1, // o seeder ja criou o periodo do semestre 1
        'inscricao_inicio' => '2026-01-10',
        'inscricao_fim' => '2026-01-25',
        'notas_inicio' => '2026-06-01',
        'notas_fim' => '2026-06-15',
    ])->assertStatus(422);
});

it('lista os periodos de um ano lectivo, com o estado das inscricoes', function () {
    $anoActivo = AnoLectivo::where('estado', 'ACTIVO')->first();

    $this->actingAs($this->admin)
        ->getJson("/api/v1/anos-lectivos/{$anoActivo->id}/periodos")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.inscricoes_abertas', true);
});
