<?php

declare(strict_types=1);

use App\Models\Avaliacao;
use App\Models\Classificacao;
use App\Models\Inscricao;
use Database\Seeders\CenarioAcademicoSeeder;
use Database\Seeders\DemonstracaoSeeder;

/*
 * Os dados de demonstracao sao o que o docente ve na defesa. Tem de obedecer
 * as mesmas regras que a aplicacao: nenhuma nota final sem avaliacoes por
 * tras, nenhuma avaliacao publicada sem notas.
 */

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->seed(DemonstracaoSeeder::class);
});

it('nao ha resultados finais sem notas lancadas que os expliquem', function () {
    $semNotas = Inscricao::whereNotNull('classificacao_final')
        ->whereDoesntHave('classificacoes')
        ->count();

    expect(Inscricao::whereNotNull('classificacao_final')->count())->toBeGreaterThan(0)
        ->and($semNotas)->toBe(0);
});

it('nao ha avaliacoes de frequencia publicadas sem notas', function () {
    $vazias = Avaliacao::where('estado', 'PUBLICADA')
        ->where('tipo', '<>', 'EXAME')
        ->whereHas('turma.inscricoes', fn ($q) => $q->where('estado', '<>', 'ANULADA'))
        ->get()
        ->filter(fn (Avaliacao $a) => $a->classificacoes()->count() === 0);

    expect($vazias)->toHaveCount(0);
});

it('a situacao de frequencia bate com a nota (dispensa so a partir de 14)', function () {
    expect(Inscricao::where('situacao_frequencia', 'DISPENSADO')->where('nota_frequencia', '<', 14)->count())->toBe(0)
        ->and(Inscricao::where('situacao_frequencia', 'REPROVADO_FREQUENCIA')->where('nota_frequencia', '>=', 10)->count())->toBe(0);
});

it('os alunos admitidos a exame tem a nota do exame lancada e publicada', function () {
    $admitidos = Inscricao::where('situacao_frequencia', 'ADMITIDO_EXAME')->whereNotNull('classificacao_final')->get();

    expect($admitidos)->not->toBeEmpty();
    foreach ($admitidos as $i) {
        expect($i->nota_exame)->not->toBeNull()
            ->and(Classificacao::where('inscricao_id', $i->id)->where('valor', $i->nota_exame)->where('estado', 'PUBLICADA')->exists())->toBeTrue();
    }
});
