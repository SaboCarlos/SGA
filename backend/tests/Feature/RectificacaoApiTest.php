<?php

declare(strict_types=1);

use App\Models\Classificacao;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\PedidoRectificacao;
use App\Models\RegistoAuditoria;
use App\Models\Turma;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->docente = Utilizador::where('email', 'docente@escn.ac.mz')->first();
    $this->ana = Estudante::where('numero_estudante', '2026/0001')->first();
    $turma = Turma::comVagas()->first();
    $this->inscricao = Inscricao::create(['estudante_id' => $this->ana->id, 'turma_id' => $turma->id, 'estado' => 'ACTIVA']);
    $avaliacao = $turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 100, 'estado' => 'RASCUNHO']);
    $this->classificacao = Classificacao::create([
        'avaliacao_id' => $avaliacao->id,
        'inscricao_id' => $this->inscricao->id,
        'valor' => 12.0,
        'estado' => 'PUBLICADA',
        'publicada_em' => now(),
    ]);
});

it('solicita uma rectificacao de nota publicada (UC23)', function () {
    $this->actingAs($this->ana->utilizador)->postJson('/api/v1/rectificacoes', [
        'classificacao_id' => $this->classificacao->id,
        'valor_novo' => 14.0,
        'justificacao' => 'Erro de soma na correcção do teste.',
    ])->assertCreated()->assertJsonPath('estado', 'PENDENTE')->assertJsonPath('valor_anterior', 12);
});

it('recusa justificacao demasiado curta', function () {
    $this->actingAs($this->ana->utilizador)->postJson('/api/v1/rectificacoes', [
        'classificacao_id' => $this->classificacao->id,
        'valor_novo' => 14.0,
        'justificacao' => 'curta',
    ])->assertStatus(422);
});

it('o coordenador aprova o pedido e a nota e actualizada (UC24)', function () {
    $pedido = PedidoRectificacao::create([
        'classificacao_id' => $this->classificacao->id,
        'solicitado_por' => $this->ana->utilizador_id,
        'valor_anterior' => 12.0,
        'valor_novo' => 14.0,
        'justificacao' => 'Erro de soma na correcção do teste.',
        'estado' => 'PENDENTE',
    ]);

    $this->actingAs($this->docente)->postJson("/api/v1/rectificacoes/{$pedido->id}/decisao", [
        'decisao' => 'APROVAR',
    ])->assertOk()->assertJsonPath('estado', 'APROVADO');

    expect($this->classificacao->fresh()->valor)->toBe(14.0)
        ->and($this->classificacao->fresh()->estado)->toBe('RECTIFICADA');

    expect(RegistoAuditoria::where('entidade', 'classificacao')->where('acao', 'classificacao.rectificada')->exists())->toBeTrue();
});

it('recusa a decisao a quem nao e coordenador do curso da turma (US-19)', function () {
    $pedido = PedidoRectificacao::create([
        'classificacao_id' => $this->classificacao->id,
        'solicitado_por' => $this->ana->utilizador_id,
        'valor_anterior' => 12.0,
        'valor_novo' => 14.0,
        'justificacao' => 'Erro de soma na correcção do teste.',
        'estado' => 'PENDENTE',
    ]);

    $this->actingAs($this->ana->utilizador)->postJson("/api/v1/rectificacoes/{$pedido->id}/decisao", [
        'decisao' => 'APROVAR',
    ])->assertForbidden();
});

it('a recusa exige motivo e nao altera a nota', function () {
    $pedido = PedidoRectificacao::create([
        'classificacao_id' => $this->classificacao->id,
        'solicitado_por' => $this->ana->utilizador_id,
        'valor_anterior' => 12.0,
        'valor_novo' => 14.0,
        'justificacao' => 'Erro de soma na correcção do teste.',
        'estado' => 'PENDENTE',
    ]);

    $this->actingAs($this->docente)
        ->postJson("/api/v1/rectificacoes/{$pedido->id}/decisao", ['decisao' => 'RECUSAR'])
        ->assertStatus(422);

    $this->actingAs($this->docente)
        ->postJson("/api/v1/rectificacoes/{$pedido->id}/decisao", ['decisao' => 'RECUSAR', 'motivo' => 'Nota confirmada em revisão.'])
        ->assertOk()
        ->assertJsonPath('estado', 'RECUSADO');

    expect($this->classificacao->fresh()->valor)->toBe(12.0);
});
