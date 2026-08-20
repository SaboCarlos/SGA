<?php

declare(strict_types=1);

use App\Models\Estudante;
use App\Models\Notificacao;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->ana = Estudante::where('numero_estudante', '2026/0001')->first();
});

it('lista as notificacoes do utilizador autenticado', function () {
    Notificacao::create([
        'utilizador_id' => $this->ana->utilizador_id,
        'tipo' => 'notas.publicadas',
        'titulo' => 'Notas publicadas',
        'mensagem' => 'As notas já estão disponíveis.',
    ]);

    $this->actingAs($this->ana->utilizador)
        ->getJson('/api/v1/notificacoes')
        ->assertOk()
        ->assertJsonCount(1);
});

it('filtra apenas as notificacoes nao lidas', function () {
    Notificacao::create(['utilizador_id' => $this->ana->utilizador_id, 'tipo' => 'x', 'titulo' => 'Lida', 'lida_em' => now()]);
    Notificacao::create(['utilizador_id' => $this->ana->utilizador_id, 'tipo' => 'y', 'titulo' => 'Nao lida']);

    $this->actingAs($this->ana->utilizador)
        ->getJson('/api/v1/notificacoes?nao_lidas=1')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.titulo', 'Nao lida');
});

it('marca uma notificacao como lida', function () {
    $notificacao = Notificacao::create(['utilizador_id' => $this->ana->utilizador_id, 'tipo' => 'x', 'titulo' => 'Teste']);

    $this->actingAs($this->ana->utilizador)
        ->patchJson("/api/v1/notificacoes/{$notificacao->id}/lida")
        ->assertNoContent();

    expect($notificacao->fresh()->lida_em)->not->toBeNull();
});

it('nao permite marcar como lida uma notificacao de outro utilizador', function () {
    $outro = Estudante::where('numero_estudante', '2026/0002')->first();
    $notificacao = Notificacao::create(['utilizador_id' => $outro->utilizador_id, 'tipo' => 'x', 'titulo' => 'Alheia']);

    $this->actingAs($this->ana->utilizador)
        ->patchJson("/api/v1/notificacoes/{$notificacao->id}/lida")
        ->assertForbidden();
});
