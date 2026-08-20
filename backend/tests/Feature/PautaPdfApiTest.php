<?php

declare(strict_types=1);

use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\Turma;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->docente = Utilizador::where('email', 'docente@escn.ac.mz')->first();
    $this->turma = Turma::comVagas()->first();
});

it('emite a pauta em PDF quando formato=pdf (UC26)', function () {
    $ana = Estudante::where('numero_estudante', '2026/0001')->first();
    Inscricao::create([
        'estudante_id' => $ana->id, 'turma_id' => $this->turma->id, 'estado' => 'ACTIVA',
        'situacao_final' => 'APROVADO', 'classificacao_final' => 15.0,
    ]);

    $resposta = $this->actingAs($this->docente)
        ->get("/api/v1/turmas/{$this->turma->id}/pauta?formato=pdf");

    $resposta->assertOk();
    expect($resposta->headers->get('content-type'))->toContain('application/pdf');
    expect(substr($resposta->getContent(), 0, 4))->toBe('%PDF');
});
