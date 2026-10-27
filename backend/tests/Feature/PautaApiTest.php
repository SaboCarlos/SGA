<?php

declare(strict_types=1);

use App\Models\Classificacao;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\Pauta;
use App\Models\Turma;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->docente = Utilizador::where('email', 'docente@escn.ac.mz')->first();
    $this->turma = Turma::comVagas()->first();
    $this->ana = Estudante::where('numero_estudante', '2026/0001')->first();
});

it('emite a pauta provisoria de uma turma (UC26)', function () {
    Inscricao::create([
        'estudante_id' => $this->ana->id, 'turma_id' => $this->turma->id, 'estado' => 'ACTIVA',
        'situacao_final' => 'APROVADO', 'classificacao_final' => 15.0,
    ]);

    $resposta = $this->actingAs($this->docente)->getJson("/api/v1/turmas/{$this->turma->id}/pauta");

    $resposta->assertOk()
        ->assertJsonPath('estado', 'PROVISORIA')
        ->assertJsonPath('total_inscritos', 1)
        ->assertJsonPath('total_aprovados', 1)
        ->assertJsonPath('taxa_aprovacao', 100);
});

it('recusa homologar pauta com estudantes sem classificacao nem falta', function () {
    Inscricao::create(['estudante_id' => $this->ana->id, 'turma_id' => $this->turma->id, 'estado' => 'ACTIVA']);

    $this->actingAs($this->docente)->getJson("/api/v1/turmas/{$this->turma->id}/pauta");
    $pauta = Pauta::where('turma_id', $this->turma->id)->first();

    $this->actingAs($this->docente)
        ->postJson("/api/v1/pautas/{$pauta->id}/homologar")
        ->assertStatus(409);
});

it('homologa a pauta quando todos os estudantes tem resultado (UC27)', function () {
    Inscricao::create([
        'estudante_id' => $this->ana->id, 'turma_id' => $this->turma->id, 'estado' => 'ACTIVA',
        'situacao_final' => 'APROVADO', 'classificacao_final' => 15.0,
    ]);

    $this->actingAs($this->docente)->getJson("/api/v1/turmas/{$this->turma->id}/pauta");
    $pauta = Pauta::where('turma_id', $this->turma->id)->first();

    $this->actingAs($this->docente)
        ->postJson("/api/v1/pautas/{$pauta->id}/homologar")
        ->assertOk();

    expect($pauta->fresh()->estado)->toBe('HOMOLOGADA');
});

it('a pauta mostra as notas que o docente lancou, por avaliacao', function () {
    $inscricao = Inscricao::create(['estudante_id' => $this->ana->id, 'turma_id' => $this->turma->id, 'estado' => 'ACTIVA']);
    $teste = $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 50, 'estado' => 'PUBLICADA']);
    $exame = $this->turma->avaliacoes()->create(['designacao' => 'Exame', 'tipo' => 'EXAME', 'peso' => 50, 'estado' => 'ACTIVA']);
    Classificacao::create(['avaliacao_id' => $teste->id, 'inscricao_id' => $inscricao->id, 'valor' => 13.5, 'estado' => 'PUBLICADA']);
    Classificacao::create(['avaliacao_id' => $exame->id, 'inscricao_id' => $inscricao->id, 'valor' => 11.0, 'estado' => 'RASCUNHO']);

    $resposta = $this->actingAs($this->docente)->getJson("/api/v1/turmas/{$this->turma->id}/pauta")->assertOk();

    expect(collect($resposta->json('avaliacoes'))->pluck('designacao')->all())->toBe(['Teste 1', 'Exame'])
        ->and($resposta->json("linhas.0.notas.{$teste->id}"))->toMatchArray(['valor' => 13.5, 'publicada' => true])
        ->and($resposta->json("linhas.0.notas.{$exame->id}"))->toMatchArray(['valor' => 11, 'publicada' => false]);
});
