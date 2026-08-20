<?php

declare(strict_types=1);

use App\Models\Classificacao;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\RegistoAuditoria;
use App\Models\Turma;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->admin = Utilizador::administrador()->first();
    $this->ana = Estudante::where('numero_estudante', '2026/0001')->first();
});

it('o proprio estudante anula a sua inscricao (UC16)', function () {
    $turma = Turma::comVagas()->first();
    $inscricao = Inscricao::create(['estudante_id' => $this->ana->id, 'turma_id' => $turma->id, 'estado' => 'ACTIVA']);

    $this->actingAs($this->ana->utilizador)
        ->deleteJson("/api/v1/inscricoes/{$inscricao->id}")
        ->assertNoContent();

    expect($inscricao->fresh()->estado)->toBe('ANULADA');
});

it('nao permite anular a inscricao de outro estudante', function () {
    $turma = Turma::comVagas()->first();
    $inscricao = Inscricao::create(['estudante_id' => $this->ana->id, 'turma_id' => $turma->id, 'estado' => 'ACTIVA']);

    $outro = Estudante::where('numero_estudante', '2026/0002')->first();

    $this->actingAs($outro->utilizador)
        ->deleteJson("/api/v1/inscricoes/{$inscricao->id}")
        ->assertForbidden();
});

it('recusa anular quando ja ha classificacao publicada (US-14)', function () {
    $turma = Turma::comVagas()->first();
    $inscricao = Inscricao::create(['estudante_id' => $this->ana->id, 'turma_id' => $turma->id, 'estado' => 'ACTIVA']);
    $avaliacao = $turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 100, 'estado' => 'RASCUNHO']);
    Classificacao::create([
        'avaliacao_id' => $avaliacao->id, 'inscricao_id' => $inscricao->id,
        'valor' => 15.0, 'estado' => 'PUBLICADA', 'publicada_em' => now(),
    ]);

    $this->actingAs($this->ana->utilizador)
        ->deleteJson("/api/v1/inscricoes/{$inscricao->id}")
        ->assertStatus(409);
});

it('o administrador inscreve um estudante em nome dele (UC17)', function () {
    $turma = Turma::comVagas()->first();

    $this->actingAs($this->admin)->postJson('/api/v1/inscricoes', [
        'turmas' => [$turma->id],
        'estudante_id' => $this->ana->id,
    ])->assertCreated();

    expect($this->ana->inscricoes()->count())->toBe(1);
});

it('recusa a inscricao administrativa a quem nao e administrador', function () {
    $turma = Turma::comVagas()->first();
    $outro = Estudante::where('numero_estudante', '2026/0002')->first();

    $this->actingAs($this->ana->utilizador)->postJson('/api/v1/inscricoes', [
        'turmas' => [$turma->id],
        'estudante_id' => $outro->id,
    ])->assertForbidden();
});

it('o administrador dispensa pre-requisitos com justificacao (UC18) e regista auditoria', function () {
    $turmaComPreRequisitos = Turma::comPreRequisitos()->first();

    $this->actingAs($this->admin)->postJson('/api/v1/inscricoes', [
        'turmas' => [$turmaComPreRequisitos->id],
        'estudante_id' => $this->ana->id,
        'dispensar_pre_requisitos' => true,
        'justificacao' => 'Equivalência reconhecida por processo administrativo.',
    ])->assertCreated();

    expect(RegistoAuditoria::where('acao', 'inscricao.dispensa_pre_requisitos')->exists())->toBeTrue();
});

it('um estudante nao pode dispensar os proprios pre-requisitos', function () {
    $turmaComPreRequisitos = Turma::comPreRequisitos()->first();

    $this->actingAs($this->ana->utilizador)->postJson('/api/v1/inscricoes', [
        'turmas' => [$turmaComPreRequisitos->id],
        'dispensar_pre_requisitos' => true,
    ])->assertForbidden();
});
