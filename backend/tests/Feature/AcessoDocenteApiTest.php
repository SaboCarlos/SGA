<?php

declare(strict_types=1);

use App\Models\Docente;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\Perfil;
use App\Models\Turma;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;
use Illuminate\Support\Facades\Hash;

/*
 * Cada perfil ve so o que lhe diz respeito: a direccao (Administrador,
 * Coordenador) ve tudo; o docente so as turmas que lecciona e os estudantes
 * nelas inscritos; o estudante so as turmas em que esta inscrito.
 */

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);

    // Docente apenas com o perfil DOCENTE, responsavel por uma unica turma.
    $utilizador = Utilizador::create([
        'nome' => 'Prof. Joana', 'email' => 'joana.teste@escn.ac.mz',
        'palavra_passe' => Hash::make('palavra-passe'), 'activo' => true,
    ]);
    $utilizador->perfis()->attach(Perfil::where('nome', 'DOCENTE')->value('id'));
    Docente::create(['utilizador_id' => $utilizador->id, 'codigo' => 'DOC900', 'nome_completo' => 'Joana Teste', 'activo' => true]);
    $this->joana = $utilizador->fresh();

    $this->turmaDaJoana = Turma::comVagas()->first();
    $this->turmaDaJoana->update(['docente_id' => $this->joana->docente->id]);
    $this->outraTurma = Turma::where('id', '<>', $this->turmaDaJoana->id)->first();

    $this->ana = Estudante::where('numero_estudante', '2026/0001')->first();
    $this->bruno = Estudante::where('numero_estudante', '2026/0002')->first();

    // Ana na turma da Joana; Bruno so noutra turma (a esgotada, do seeder).
    Inscricao::create(['estudante_id' => $this->ana->id, 'turma_id' => $this->turmaDaJoana->id, 'estado' => 'ACTIVA']);
});

it('o docente so lista as turmas que lecciona', function () {
    $resposta = $this->actingAs($this->joana)->getJson('/api/v1/turmas');

    $resposta->assertOk();
    expect(collect($resposta->json())->pluck('id')->all())->toBe([$this->turmaDaJoana->id]);
});

it('o docente nao abre turma, pauta, inscritos nem avaliacoes de outro docente', function () {
    $id = $this->outraTurma->id;

    $this->actingAs($this->joana)->getJson("/api/v1/turmas/{$id}")->assertForbidden();
    $this->actingAs($this->joana)->getJson("/api/v1/turmas/{$id}/pauta")->assertForbidden();
    $this->actingAs($this->joana)->getJson("/api/v1/turmas/{$id}/inscritos")->assertForbidden();
    $this->actingAs($this->joana)->getJson("/api/v1/turmas/{$id}/avaliacoes")->assertForbidden();
    $this->actingAs($this->joana)->postJson("/api/v1/turmas/{$id}/avaliacoes", [
        'designacao' => 'Teste', 'tipo' => 'TESTE', 'peso' => 50,
    ])->assertForbidden();
});

it('o docente ve e gere a sua turma', function () {
    $id = $this->turmaDaJoana->id;

    $this->actingAs($this->joana)->getJson("/api/v1/turmas/{$id}/inscritos")->assertOk()->assertJsonCount(1);
    $this->actingAs($this->joana)->getJson("/api/v1/turmas/{$id}/pauta")->assertOk();
    $this->actingAs($this->joana)->postJson("/api/v1/turmas/{$id}/avaliacoes", [
        'designacao' => 'Teste 1', 'tipo' => 'TESTE', 'peso' => 50,
    ])->assertCreated();
});

it('o docente so ve os estudantes e as inscricoes das suas turmas', function () {
    $estudantes = $this->actingAs($this->joana)->getJson('/api/v1/estudantes')->assertOk();
    expect(collect($estudantes->json('dados'))->pluck('id')->all())->toBe([$this->ana->id]);

    $this->actingAs($this->joana)->getJson("/api/v1/estudantes/{$this->ana->id}")->assertOk();
    $this->actingAs($this->joana)->getJson("/api/v1/estudantes/{$this->bruno->id}")->assertForbidden();

    $inscricoes = $this->actingAs($this->joana)->getJson('/api/v1/inscricoes')->assertOk();
    expect(collect($inscricoes->json())->pluck('turma.id')->unique()->values()->all())->toBe([$this->turmaDaJoana->id]);
});

it('so a direccao lista os docentes', function () {
    $this->actingAs($this->joana)->getJson('/api/v1/docentes')->assertForbidden();
    $this->actingAs($this->ana->utilizador)->getJson('/api/v1/docentes')->assertForbidden();
    $this->actingAs(Utilizador::administrador()->first())->getJson('/api/v1/docentes')->assertOk();
});

it('a direccao ve todas as turmas', function () {
    $this->actingAs(Utilizador::administrador()->first())->getJson('/api/v1/turmas')
        ->assertOk()->assertJsonCount(Turma::count());
});

it('o estudante so lista as turmas em que esta inscrito', function () {
    $resposta = $this->actingAs($this->ana->utilizador)->getJson('/api/v1/turmas');

    $resposta->assertOk();
    expect(collect($resposta->json())->pluck('id')->all())->toBe([$this->turmaDaJoana->id]);
    $this->actingAs($this->ana->utilizador)->getJson("/api/v1/turmas/{$this->outraTurma->id}")->assertForbidden();
});

it('as notas do estudante indicam o curso, o ano em que esta e o ano de cada disciplina', function () {
    $resposta = $this->actingAs($this->ana->utilizador)->getJson('/api/v1/notas')->assertOk();

    expect($resposta->json('curso'))->not->toBeNull()
        ->and($resposta->json('ano_lectivo_actual'))->toBe('2026')
        ->and($resposta->json('ano_curricular'))->toBeInt()
        ->and($resposta->json('disciplinas.0.ano_curricular'))->toBe($resposta->json('ano_curricular'));
});
