<?php

declare(strict_types=1);

use App\Models\Disciplina;
use App\Models\Docente;
use App\Models\PeriodoLectivo;
use App\Models\Turma;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->admin = Utilizador::administrador()->first();
});

it('lista turmas', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/turmas')
        ->assertOk()
        ->assertJsonCount(3);
});

it('filtra turmas com vagas', function () {
    $turmaEsgotada = Turma::esgotada()->first();

    $resposta = $this->actingAs($this->admin)->getJson('/api/v1/turmas?com_vagas=1');

    $resposta->assertOk();
    expect(collect($resposta->json())->pluck('id'))->not->toContain($turmaEsgotada->id);
});

it('cria uma turma nova (UC13)', function () {
    $disciplina = Disciplina::where('codigo', 'INF101')->first();
    $periodo = PeriodoLectivo::first();
    $docente = Docente::first();

    $this->actingAs($this->admin)->postJson('/api/v1/turmas', [
        'disciplina_id' => $disciplina->id,
        'periodo_lectivo_id' => $periodo->id,
        'docente_id' => $docente->id,
        'designacao' => 'B',
        'capacidade_maxima' => 25,
    ])->assertCreated()->assertJsonPath('designacao', 'B');
});

it('lista os inscritos de uma turma, com a situacao de frequencia (RF-28)', function () {
    $turmaEsgotada = Turma::esgotada()->first();

    $resposta = $this->actingAs($this->admin)->getJson("/api/v1/turmas/{$turmaEsgotada->id}/inscritos");

    $resposta->assertOk()->assertJsonCount(1)->assertJsonStructure([
        ['inscricao_id', 'numero_estudante', 'nome_completo', 'nota_frequencia', 'situacao_frequencia'],
    ]);
});
