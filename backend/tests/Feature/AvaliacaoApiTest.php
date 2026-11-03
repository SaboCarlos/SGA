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

it('a grelha de lancamento lista os inscritos mesmo antes de haver notas', function () {
    $avaliacao = $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 100, 'estado' => 'RASCUNHO']);

    $this->actingAs($this->docente)->getJson("/api/v1/avaliacoes/{$avaliacao->id}/classificacoes")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.inscricao_id', $this->inscricao->id)
        ->assertJsonPath('0.valor', null);
});

it('o docente pode gravar as notas varias vezes antes de publicar', function () {
    $avaliacao = $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 100, 'estado' => 'RASCUNHO']);
    $url = "/api/v1/avaliacoes/{$avaliacao->id}/classificacoes";

    $this->actingAs($this->docente)->putJson($url, ['classificacoes' => [['inscricao_id' => $this->inscricao->id, 'valor' => 12.0]]])->assertOk();
    $this->actingAs($this->docente)->putJson($url, ['classificacoes' => [['inscricao_id' => $this->inscricao->id, 'valor' => 14.0]]])->assertOk();

    expect($avaliacao->classificacoes()->count())->toBe(1)
        ->and($avaliacao->classificacoes()->first()->valor)->toBe(14.0);
});

it('recusa lancar notas depois de publicadas', function () {
    $avaliacao = $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 100, 'estado' => 'PUBLICADA']);

    $this->actingAs($this->docente)->putJson("/api/v1/avaliacoes/{$avaliacao->id}/classificacoes", [
        'classificacoes' => [['inscricao_id' => $this->inscricao->id, 'valor' => 20.0]],
    ])->assertStatus(409);
});

it('recusa lancar notas para uma inscricao de outra turma', function () {
    $avaliacao = $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 100, 'estado' => 'RASCUNHO']);
    $inscricaoAlheia = Inscricao::where('turma_id', '<>', $this->turma->id)->first();

    $this->actingAs($this->docente)->putJson("/api/v1/avaliacoes/{$avaliacao->id}/classificacoes", [
        'classificacoes' => [['inscricao_id' => $inscricaoAlheia->id, 'valor' => 20.0]],
    ])->assertStatus(422);

    expect(Classificacao::where('inscricao_id', $inscricaoAlheia->id)->exists())->toBeFalse();
});

/*
 * Fluxo real de um semestre: Teste 60% + Trabalho 40% e o Exame a parte, cada um
 * publicado quando e corrigido. Antes, publicar o Trabalho sozinho falhava
 * com "a soma dos pesos das avaliacoes de frequencia e 20%".
 */
function estruturaDoSemestre(Turma $turma): array
{
    return [
        'teste' => $turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 60, 'estado' => 'RASCUNHO']),
        'trabalho' => $turma->avaliacoes()->create(['designacao' => 'Trabalho', 'tipo' => 'TRABALHO', 'peso' => 40, 'estado' => 'RASCUNHO']),
        'exame' => $turma->avaliacoes()->create(['designacao' => 'Exame', 'tipo' => 'EXAME', 'peso' => 100, 'estado' => 'RASCUNHO']),
    ];
}

it('publica uma avaliacao de cada vez e so calcula a frequencia quando todas as de frequencia estao publicadas', function () {
    $a = estruturaDoSemestre($this->turma);
    Classificacao::create(['avaliacao_id' => $a['trabalho']->id, 'inscricao_id' => $this->inscricao->id, 'valor' => 15.0, 'estado' => 'RASCUNHO']);
    Classificacao::create(['avaliacao_id' => $a['teste']->id, 'inscricao_id' => $this->inscricao->id, 'valor' => 12.0, 'estado' => 'RASCUNHO']);

    // O Trabalho primeiro: publica, mas ainda nao ha frequencia.
    $this->actingAs($this->docente)->postJson("/api/v1/avaliacoes/{$a['trabalho']->id}/publicar")
        ->assertOk()->assertJsonPath('frequencia_calculada', false);
    expect($this->inscricao->fresh()->nota_frequencia)->toBeNull();

    // Depois o Teste: agora calcula. (12 x 30 + 15 x 20) / 50 = 13,2 -> admitido a exame.
    $this->actingAs($this->docente)->postJson("/api/v1/avaliacoes/{$a['teste']->id}/publicar")
        ->assertOk()->assertJsonPath('frequencia_calculada', true)->assertJsonPath('admitidos_exame', 1);

    $inscricao = $this->inscricao->fresh();
    expect($inscricao->nota_frequencia)->toBe(13.2)
        ->and($inscricao->situacao_frequencia)->toBe('ADMITIDO_EXAME')
        ->and($inscricao->situacao_final)->toBe('EM_CURSO');
});

it('a nota do exame entra na classificacao final de quem foi admitido', function () {
    $a = estruturaDoSemestre($this->turma);
    Classificacao::create(['avaliacao_id' => $a['teste']->id, 'inscricao_id' => $this->inscricao->id, 'valor' => 12.0, 'estado' => 'RASCUNHO']);
    Classificacao::create(['avaliacao_id' => $a['trabalho']->id, 'inscricao_id' => $this->inscricao->id, 'valor' => 15.0, 'estado' => 'RASCUNHO']);
    Classificacao::create(['avaliacao_id' => $a['exame']->id, 'inscricao_id' => $this->inscricao->id, 'valor' => 14.0, 'estado' => 'RASCUNHO']);

    foreach (['teste', 'trabalho', 'exame'] as $chave) {
        $this->actingAs($this->docente)->postJson("/api/v1/avaliacoes/{$a[$chave]->id}/publicar")->assertOk();
    }

    // Frequencia 13,2; exame 14; final (13,2 + 14) / 2 = 13,6 -> aprovado.
    $inscricao = $this->inscricao->fresh();
    expect($inscricao->nota_exame)->toBe(14.0)
        ->and($inscricao->classificacao_final)->toBe(13.6)
        ->and($inscricao->situacao_final)->toBe('APROVADO');
});

it('o exame conta a parte: testes e trabalhos a 100% e exame a 100% e uma estrutura valida', function () {
    $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 50, 'estado' => 'RASCUNHO']);
    $this->turma->avaliacoes()->create(['designacao' => 'Teste 2', 'tipo' => 'TESTE', 'peso' => 30, 'estado' => 'RASCUNHO']);
    $this->turma->avaliacoes()->create(['designacao' => 'Trabalho', 'tipo' => 'TRABALHO', 'peso' => 20, 'estado' => 'RASCUNHO']);
    $this->turma->avaliacoes()->create(['designacao' => 'Exame', 'tipo' => 'EXAME', 'peso' => 100, 'estado' => 'RASCUNHO']);

    $this->actingAs($this->docente)->getJson("/api/v1/turmas/{$this->turma->id}/avaliacoes")
        ->assertOk()
        ->assertJsonPath('soma_pesos', 100)
        ->assertJsonPath('estrutura_valida', true);
});

it('recusa publicar enquanto os pesos da disciplina nao somam 100%', function () {
    $teste = $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 30, 'estado' => 'RASCUNHO']);
    $this->turma->avaliacoes()->create(['designacao' => 'Trabalho', 'tipo' => 'TRABALHO', 'peso' => 20, 'estado' => 'RASCUNHO']);
    Classificacao::create(['avaliacao_id' => $teste->id, 'inscricao_id' => $this->inscricao->id, 'valor' => 12.0, 'estado' => 'RASCUNHO']);

    $this->actingAs($this->docente)->postJson("/api/v1/avaliacoes/{$teste->id}/publicar")
        ->assertStatus(409)
        ->assertJsonPath('codigo', 'RN-06');
});

it('uma nota deixada em branco conta como falta, sem rebentar a publicacao', function () {
    $teste = $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 100, 'estado' => 'RASCUNHO']);
    Classificacao::create(['avaliacao_id' => $teste->id, 'inscricao_id' => $this->inscricao->id, 'valor' => null, 'faltou' => false, 'estado' => 'RASCUNHO']);

    $this->actingAs($this->docente)->postJson("/api/v1/avaliacoes/{$teste->id}/publicar", ['confirmar_incompleta' => true])
        ->assertOk();

    expect($this->inscricao->fresh()->nota_frequencia)->toBe(0.0);
});
