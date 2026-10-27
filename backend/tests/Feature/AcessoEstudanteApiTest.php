<?php

declare(strict_types=1);

use App\Models\Classificacao;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\Pauta;
use App\Models\Turma;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

/*
 * Um estudante ve as suas notas e nada mais: nem a pauta da turma, nem as
 * notas dos colegas, nem as operacoes de gestao. Antes destas regras, bastava
 * o token de um estudante para ler a pauta inteira ou criar um curso.
 */

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->turma = Turma::comVagas()->first();
    $this->ana = Estudante::where('numero_estudante', '2026/0001')->first();
    $this->bruno = Estudante::where('numero_estudante', '2026/0002')->first();

    $this->inscricaoAna = Inscricao::create(['estudante_id' => $this->ana->id, 'turma_id' => $this->turma->id, 'estado' => 'ACTIVA']);
    $this->inscricaoBruno = Inscricao::create(['estudante_id' => $this->bruno->id, 'turma_id' => $this->turma->id, 'estado' => 'ACTIVA']);

    $this->teste = $this->turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 40, 'estado' => 'PUBLICADA']);
    $this->exame = $this->turma->avaliacoes()->create(['designacao' => 'Exame', 'tipo' => 'EXAME', 'peso' => 60, 'estado' => 'RASCUNHO']);

    Classificacao::create(['avaliacao_id' => $this->teste->id, 'inscricao_id' => $this->inscricaoAna->id, 'valor' => 14.0, 'estado' => 'PUBLICADA', 'publicada_em' => now()]);
    Classificacao::create(['avaliacao_id' => $this->teste->id, 'inscricao_id' => $this->inscricaoBruno->id, 'valor' => 9.0, 'estado' => 'PUBLICADA', 'publicada_em' => now()]);
    Classificacao::create(['avaliacao_id' => $this->exame->id, 'inscricao_id' => $this->inscricaoAna->id, 'valor' => 17.0, 'estado' => 'RASCUNHO']);
});

it('recusa ao estudante a pauta, as classificacoes e os inscritos da turma', function () {
    $ana = $this->ana->utilizador;

    $this->actingAs($ana)->getJson("/api/v1/turmas/{$this->turma->id}/pauta")->assertForbidden();
    $this->actingAs($ana)->getJson("/api/v1/avaliacoes/{$this->teste->id}/classificacoes")->assertForbidden();
    $this->actingAs($ana)->getJson("/api/v1/turmas/{$this->turma->id}/inscritos")->assertForbidden();
});

it('recusa ao estudante homologar uma pauta', function () {
    $pauta = Pauta::create(['turma_id' => $this->turma->id, 'versao' => 1, 'numero' => 'PAUTA/2026/9999', 'total_inscritos' => 2, 'total_aprovados' => 0, 'estado' => 'PROVISORIA']);

    $this->actingAs($this->ana->utilizador)->postJson("/api/v1/pautas/{$pauta->id}/homologar")->assertForbidden();

    expect($pauta->fresh()->estado)->toBe('PROVISORIA');
});

it('recusa ao estudante definir avaliacoes e as operacoes de gestao', function () {
    $ana = $this->ana->utilizador;

    $this->actingAs($ana)->postJson("/api/v1/turmas/{$this->turma->id}/avaliacoes", ['designacao' => 'X', 'tipo' => 'TESTE', 'peso' => 10])->assertForbidden();
    $this->actingAs($ana)->postJson('/api/v1/cursos', [])->assertForbidden();
    $this->actingAs($ana)->putJson("/api/v1/cursos/{$this->ana->curso_id}", ['designacao' => 'Outro'])->assertForbidden();
    $this->actingAs($ana)->postJson('/api/v1/disciplinas', [])->assertForbidden();
    $this->actingAs($ana)->postJson('/api/v1/turmas', [])->assertForbidden();
    $this->actingAs($ana)->postJson('/api/v1/docentes', [])->assertForbidden();
    $this->actingAs($ana)->postJson('/api/v1/estudantes', [])->assertForbidden();
    $this->actingAs($ana)->postJson('/api/v1/anos-lectivos', [])->assertForbidden();
    $this->actingAs($ana)->getJson('/api/v1/estudantes')->assertForbidden();
    $this->actingAs($ana)->getJson("/api/v1/estudantes/{$this->bruno->id}")->assertForbidden();
    $this->actingAs($ana)->getJson('/api/v1/relatorios/aproveitamento-disciplina')->assertForbidden();
});

it('o estudante so ve as suas inscricoes, mesmo pedindo as de outro', function () {
    $resposta = $this->actingAs($this->ana->utilizador)->getJson("/api/v1/inscricoes?estudante_id={$this->bruno->id}");

    $resposta->assertOk();
    expect(collect($resposta->json())->pluck('id')->all())->toBe([$this->inscricaoAna->id]);
});

it('o estudante so pede rectificacao de uma nota sua e so ve os seus pedidos', function () {
    $notaDoBruno = Classificacao::where('inscricao_id', $this->inscricaoBruno->id)->first();

    $this->actingAs($this->ana->utilizador)->postJson('/api/v1/rectificacoes', [
        'classificacao_id' => $notaDoBruno->id,
        'valor_novo' => 20,
        'justificacao' => 'Tentativa de alterar a nota de um colega.',
    ])->assertForbidden();

    $this->actingAs($this->bruno->utilizador)->postJson('/api/v1/rectificacoes', [
        'classificacao_id' => $notaDoBruno->id,
        'valor_novo' => 11,
        'justificacao' => 'Erro de soma na correcção do teste.',
    ])->assertCreated();

    $this->actingAs($this->ana->utilizador)->getJson('/api/v1/rectificacoes')->assertOk()->assertJsonCount(0);
});

it('mostra ao estudante as suas notas por disciplina, so as publicadas (GET /notas)', function () {
    $resposta = $this->actingAs($this->ana->utilizador)->getJson('/api/v1/notas');

    $resposta->assertOk()->assertJsonPath('numero_estudante', '2026/0001');

    $disciplina = collect($resposta->json('disciplinas'))->firstWhere('inscricao_id', $this->inscricaoAna->id);
    $avaliacoes = collect($disciplina['avaliacoes'])->keyBy('designacao');

    expect($avaliacoes['Teste 1'])->toMatchArray(['publicada' => true, 'valor' => 14])
        ->and($avaliacoes['Exame'])->toMatchArray(['publicada' => false, 'valor' => null]);

    // Nenhuma nota do Bruno pode aparecer na resposta da Ana.
    expect(json_encode($resposta->json()))->not->toContain('Bruno');
});

it('recusa GET /notas a quem nao e estudante', function () {
    $docente = Utilizador::where('email', 'docente@escn.ac.mz')->first();

    $this->actingAs($docente)->getJson('/api/v1/notas')->assertForbidden();
});

it('o docente da turma continua a ver a pauta e as classificacoes', function () {
    $docente = Utilizador::where('email', 'docente@escn.ac.mz')->first();

    $this->actingAs($docente)->getJson("/api/v1/turmas/{$this->turma->id}/pauta")->assertOk();
    $this->actingAs($docente)->getJson("/api/v1/avaliacoes/{$this->teste->id}/classificacoes")->assertOk()->assertJsonCount(2);
});
