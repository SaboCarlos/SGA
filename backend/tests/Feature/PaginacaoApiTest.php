<?php

declare(strict_types=1);

use App\Models\Utilizador;
use Database\Seeders\InstituicaoSeeder;

/*
 * Estudantes, docentes, disciplinas e turmas: 10 por pagina, com o mesmo
 * formato {pagina, por_pagina, total, total_paginas, dados}. Sem "pagina",
 * docentes/disciplinas/turmas devolvem a lista completa (campos de escolha).
 */

beforeEach(function () {
    $this->seed(InstituicaoSeeder::class);
    $this->admin = Utilizador::where('email', 'admin@escn.ac.mz')->first();
});

it('pagina as listas de 10 em 10', function (string $url, int $total) {
    $primeira = $this->actingAs($this->admin)->getJson("/api/v1/{$url}?pagina=1")->assertOk();

    expect($primeira->json('por_pagina'))->toBe(10)
        ->and($primeira->json('total'))->toBe($total)
        ->and($primeira->json('total_paginas'))->toBe((int) ceil($total / 10))
        ->and($primeira->json('dados'))->toHaveCount(10);

    $ultima = $this->actingAs($this->admin)->getJson("/api/v1/{$url}?pagina=".(int) ceil($total / 10))->assertOk();
    expect($ultima->json('dados'))->toHaveCount($total % 10 ?: 10);
})->with([
    'estudantes' => ['estudantes', 50],
    'docentes' => ['docentes', 25],
    'disciplinas' => ['disciplinas', 25],
    'turmas' => ['turmas', 25],
]);

it('as paginas nao repetem nem perdem itens', function () {
    $ids = collect(range(1, 3))->flatMap(
        fn (int $p) => $this->actingAs($this->admin)->getJson("/api/v1/docentes?pagina={$p}")->json('dados')
    )->pluck('id');

    expect($ids)->toHaveCount(25)->and($ids->unique())->toHaveCount(25);
});

it('sem pagina, os docentes vem todos (para o campo de escolha ao criar uma turma)', function () {
    $this->actingAs($this->admin)->getJson('/api/v1/docentes')->assertOk()->assertJsonCount(25);
});
