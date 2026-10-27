<?php

declare(strict_types=1);

use App\Models\Classificacao;
use App\Models\Disciplina;
use App\Models\Inscricao;
use App\Models\Turma;
use App\Models\Utilizador;
use Database\Seeders\InstituicaoSeeder;

/*
 * Quem ve cada pedido de rectificacao, no cenario real da instituicao:
 * a Ana (LEI, 1.º ano) pede a correccao de uma nota de Matematica Discreta,
 * leccionada pelo Carlos. A Helena coordena a LEI.
 */

function utilizador(string $email): Utilizador
{
    return Utilizador::where('email', $email)->firstOrFail();
}

beforeEach(function () {
    $this->seed(InstituicaoSeeder::class);

    $turma = Turma::where('disciplina_id', Disciplina::where('codigo', 'LEI102')->value('id'))->firstOrFail();
    $avaliacao = $turma->avaliacoes()->create(['designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 100, 'estado' => 'PUBLICADA']);
    $inscricao = Inscricao::where('turma_id', $turma->id)
        ->where('estudante_id', utilizador('ana.chauque@escn.ac.mz')->estudante->id)->firstOrFail();
    $classificacao = Classificacao::create([
        'avaliacao_id' => $avaliacao->id, 'inscricao_id' => $inscricao->id,
        'valor' => 9.0, 'estado' => 'PUBLICADA', 'publicada_em' => now(),
    ]);

    $this->pedido = $this->actingAs(utilizador('ana.chauque@escn.ac.mz'))->postJson('/api/v1/rectificacoes', [
        'classificacao_id' => $classificacao->id,
        'valor_novo' => 11,
        'justificacao' => 'A pergunta 3 foi cotada com zero mas está correcta.',
    ])->assertCreated()->json();
});

function pedidosVistosPor(string $email): array
{
    return test()->actingAs(utilizador($email))->getJson('/api/v1/rectificacoes')->assertOk()->json();
}

it('o aluno so ve os seus pedidos, nunca os dos colegas', function () {
    expect(pedidosVistosPor('ana.chauque@escn.ac.mz'))->toHaveCount(1)
        ->and(pedidosVistosPor('bruno.mussa@escn.ac.mz'))->toHaveCount(0)
        ->and(pedidosVistosPor('katia.chauque@escn.ac.mz'))->toHaveCount(0);
});

it('o docente da disciplina ve o pedido mas nao o decide', function () {
    $vistos = pedidosVistosPor('carlos.macuacua@escn.ac.mz');

    expect($vistos)->toHaveCount(1)
        ->and($vistos[0]['pode_decidir'])->toBeFalse()
        ->and($vistos[0]['disciplina'])->toContain('LEI102');

    $this->actingAs(utilizador('carlos.macuacua@escn.ac.mz'))
        ->postJson("/api/v1/rectificacoes/{$this->pedido['id']}/decisao", ['decisao' => 'APROVAR'])
        ->assertForbidden();
});

it('o coordenador do curso ve o pedido de outra disciplina do curso e decide-o', function () {
    $vistos = pedidosVistosPor('helena.zandamela@escn.ac.mz');

    expect($vistos)->toHaveCount(1)->and($vistos[0]['pode_decidir'])->toBeTrue();

    $this->actingAs(utilizador('helena.zandamela@escn.ac.mz'))
        ->postJson("/api/v1/rectificacoes/{$this->pedido['id']}/decisao", ['decisao' => 'APROVAR'])
        ->assertOk()->assertJsonPath('estado', 'APROVADO');
});

it('docentes de outras disciplinas e o coordenador de outro curso nao veem o pedido', function () {
    expect(pedidosVistosPor('joana.nhantumbo@escn.ac.mz'))->toHaveCount(0)
        ->and(pedidosVistosPor('paulo.chissano@escn.ac.mz'))->toHaveCount(0);

    $this->actingAs(utilizador('paulo.chissano@escn.ac.mz'))
        ->postJson("/api/v1/rectificacoes/{$this->pedido['id']}/decisao", ['decisao' => 'APROVAR'])
        ->assertForbidden();
});

it('o administrador ve todos os pedidos e pode decidir', function () {
    $vistos = pedidosVistosPor('admin@escn.ac.mz');

    expect($vistos)->toHaveCount(1)->and($vistos[0]['pode_decidir'])->toBeTrue();
});
