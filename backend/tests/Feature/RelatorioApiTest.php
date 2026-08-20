<?php

declare(strict_types=1);

use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\Turma;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->admin = Utilizador::administrador()->first();
});

it('devolve a taxa de aprovacao por disciplina (UC29)', function () {
    $turma = Turma::comVagas()->first();
    $ana = Estudante::where('numero_estudante', '2026/0001')->first();

    Inscricao::create([
        'estudante_id' => $ana->id, 'turma_id' => $turma->id, 'estado' => 'ACTIVA',
        'situacao_final' => 'APROVADO', 'classificacao_final' => 16.0,
    ]);

    $resposta = $this->actingAs($this->admin)->getJson('/api/v1/relatorios/aproveitamento-disciplina?ano_lectivo=2026&semestre=1');

    $resposta->assertOk();
    expect(collect($resposta->json())->firstWhere('codigo', 'INF301'))
        ->toMatchArray(['inscritos' => 1, 'aprovados' => 1, 'reprovados' => 0]);
});

it('devolve o relatorio em PDF', function () {
    $resposta = $this->actingAs($this->admin)->get('/api/v1/relatorios/aproveitamento-disciplina?formato=pdf');

    $resposta->assertOk();
    expect($resposta->headers->get('content-type'))->toContain('application/pdf');
});

it('devolve o relatorio em CSV', function () {
    $turma = Turma::comVagas()->first();
    $ana = Estudante::where('numero_estudante', '2026/0001')->first();
    Inscricao::create([
        'estudante_id' => $ana->id, 'turma_id' => $turma->id, 'estado' => 'ACTIVA',
        'situacao_final' => 'APROVADO', 'classificacao_final' => 16.0,
    ]);

    $resposta = $this->actingAs($this->admin)->get('/api/v1/relatorios/aproveitamento-disciplina?formato=csv');

    $resposta->assertOk();
    expect($resposta->headers->get('content-type'))->toContain('text/csv');
    expect($resposta->streamedContent())->toContain('INF301');
});
