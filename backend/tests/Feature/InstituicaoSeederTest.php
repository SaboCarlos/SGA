<?php

declare(strict_types=1);

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\ItemPlanoCurricular;
use App\Models\Turma;
use App\Models\Utilizador;
use Database\Seeders\InstituicaoSeeder;

beforeEach(function () {
    $this->seed(InstituicaoSeeder::class);
});

it('cria 5 cursos com alunos no 1.º, 2.º, 2.º, 3.º e 4.º ano', function () {
    $anos = Curso::orderBy('id')->get()->map(
        fn (Curso $c) => ItemPlanoCurricular::whereHas('plano', fn ($q) => $q->where('curso_id', $c->id))->value('ano_curricular')
    )->all();

    expect($anos)->toBe([1, 2, 2, 3, 4]);
});

it('cada curso tem 5 disciplinas, cada uma com um docente diferente', function () {
    expect(Turma::count())->toBe(25)
        ->and(Turma::distinct('docente_id')->count('docente_id'))->toBe(25)
        ->and(Docente::count())->toBe(25);
});

it('cada curso tem 10 alunos proprios, inscritos nas 5 disciplinas do seu ano', function () {
    foreach (Curso::all() as $curso) {
        $alunos = Estudante::where('curso_id', $curso->id)->get();
        expect($alunos)->toHaveCount(10);

        foreach ($alunos as $aluno) {
            expect(Inscricao::where('estudante_id', $aluno->id)->where('estado', 'ACTIVA')->count())->toBe(5);
        }
    }

    expect(Estudante::count())->toBe(50)
        ->and(Utilizador::where('email', 'like', '%@escn.ac.mz')->count())->toBe(1 + 25 + 50);
});

it('o aluno ve o ano em que esta em As minhas notas', function () {
    $aluno = Estudante::whereHas('curso', fn ($q) => $q->where('codigo', 'LEN'))->first();

    $this->actingAs($aluno->utilizador)->getJson('/api/v1/notas')
        ->assertOk()
        ->assertJsonPath('ano_curricular', 3)
        ->assertJsonCount(5, 'disciplinas');
});

it('o docente ve so a sua disciplina e os 10 alunos dela', function () {
    $docente = Docente::where('codigo', 'DOC007')->first()->utilizador;

    $turmas = $this->actingAs($docente)->getJson('/api/v1/turmas')->assertOk()->json();
    expect($turmas)->toHaveCount(1);

    $this->actingAs($docente)->getJson("/api/v1/turmas/{$turmas[0]['id']}/inscritos")->assertOk()->assertJsonCount(10);
});
