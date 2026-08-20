<?php

declare(strict_types=1);

use App\Models\Classificacao;
use App\Models\Docente;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\Notificacao;
use App\Models\Perfil;
use App\Models\Turma;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->docente = Utilizador::where('email', 'docente@escn.ac.mz')->first();
    $this->turma = Turma::comVagas()->first();
    $this->ana = Estudante::where('numero_estudante', '2026/0001')->first();
    $this->inscricao = Inscricao::create([
        'estudante_id' => $this->ana->id,
        'turma_id' => $this->turma->id,
        'estado' => 'ACTIVA',
    ]);
});

it('define uma avaliacao na turma (UC19)', function () {
    $this->actingAs($this->docente)->postJson("/api/v1/turmas/{$this->turma->id}/avaliacoes", [
        'designacao' => 'Teste 1',
        'tipo' => 'TESTE',
        'peso' => 100,
    ])->assertCreated()->assertJsonPath('estado', 'RASCUNHO');
});

it('recusa definir avaliacao a um docente que nao e responsavel pela turma', function () {
    $outroUtilizador = Utilizador::create([
        'nome' => 'Outro Docente',
        'email' => 'outro@escn.ac.mz',
        'palavra_passe' => Hash::make('x'),
        'activo' => true,
    ]);
    $outroUtilizador->perfis()->attach(Perfil::where('nome', 'DOCENTE')->value('id'));
    Docente::create(['utilizador_id' => $outroUtilizador->id, 'codigo' => 'DOC099', 'nome_completo' => 'Outro Docente', 'activo' => true]);

    $this->actingAs($outroUtilizador)->postJson("/api/v1/turmas/{$this->turma->id}/avaliacoes", [
        'designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 100,
    ])->assertForbidden();
});

it('mostra a soma dos pesos e se a estrutura e valida', function () {
    $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 40, 'estado' => 'RASCUNHO']);
    $this->turma->avaliacoes()->create(['designacao' => 'Teste 2', 'tipo' => 'TESTE', 'peso' => 60, 'estado' => 'RASCUNHO']);

    $this->actingAs($this->docente)
        ->getJson("/api/v1/turmas/{$this->turma->id}/avaliacoes")
        ->assertOk()
        ->assertJsonPath('soma_pesos', 100)
        ->assertJsonPath('estrutura_valida', true);
});

it('lanca classificacoes em bloco (UC20)', function () {
    $avaliacao = $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 100, 'estado' => 'RASCUNHO']);

    $this->actingAs($this->docente)->putJson("/api/v1/avaliacoes/{$avaliacao->id}/classificacoes", [
        'classificacoes' => [
            ['inscricao_id' => $this->inscricao->id, 'valor' => 15.0],
        ],
    ])->assertOk();

    expect($avaliacao->classificacoes()->first()->valor)->toBe(15.0);
});

it('recusa lancar classificacoes fora do intervalo 0-20 (RF-38)', function () {
    $avaliacao = $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 100, 'estado' => 'RASCUNHO']);

    $this->actingAs($this->docente)->putJson("/api/v1/avaliacoes/{$avaliacao->id}/classificacoes", [
        'classificacoes' => [
            ['inscricao_id' => $this->inscricao->id, 'valor' => 25.0],
        ],
    ])->assertStatus(422);
});

it('publica as classificacoes e calcula frequencia, situacao e classificacao final (UC21)', function () {
    $avaliacao = $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 100, 'estado' => 'RASCUNHO']);
    Classificacao::create(['avaliacao_id' => $avaliacao->id, 'inscricao_id' => $this->inscricao->id, 'valor' => 15.0, 'estado' => 'RASCUNHO']);

    $resposta = $this->actingAs($this->docente)->postJson("/api/v1/avaliacoes/{$avaliacao->id}/publicar");

    $resposta->assertOk()->assertJsonStructure(['publicadas', 'dispensados', 'admitidos_exame', 'reprovados_frequencia', 'media_turma']);

    $inscricao = $this->inscricao->fresh();
    expect($inscricao->nota_frequencia)->toBe(15.0)
        ->and($inscricao->situacao_frequencia)->toBe('DISPENSADO')
        ->and($inscricao->classificacao_final)->toBe(15.0)
        ->and($inscricao->situacao_final)->toBe('APROVADO');

    expect(Notificacao::where('utilizador_id', $this->ana->utilizador_id)->where('tipo', 'notas.publicadas')->exists())->toBeTrue();
});

it('recusa publicar com estudantes sem classificacao nem marca de falta, sem confirmacao (US-18)', function () {
    $avaliacao = $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 100, 'estado' => 'RASCUNHO']);
    // nenhuma classificacao lancada para a unica inscricao da turma

    $this->actingAs($this->docente)
        ->postJson("/api/v1/avaliacoes/{$avaliacao->id}/publicar")
        ->assertStatus(409);
});
