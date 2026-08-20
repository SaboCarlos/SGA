<?php

declare(strict_types=1);

use App\Models\Estudante;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->admin = Utilizador::administrador()->first();
});

it('o administrador lista os perfis com as respectivas permissoes', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/perfis')
        ->assertOk()
        ->assertJsonStructure([['id', 'nome', 'descricao', 'permissoes']]);
});

it('o administrador cria um perfil novo', function () {
    $this->actingAs($this->admin)->postJson('/api/v1/perfis', [
        'nome' => 'AUDITOR',
        'descricao' => 'Acesso apenas à auditoria',
    ])->assertCreated()->assertJsonPath('nome', 'AUDITOR');
});

it('recusa nome de perfil duplicado', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/perfis', ['nome' => 'ESTUDANTE'])
        ->assertStatus(422);
});

it('atribui uma permissao a um perfil', function () {
    $permissao = Permissao::create(['codigo' => 'classificacao.publicar']);
    $perfil = Perfil::where('nome', 'DOCENTE')->first();

    $this->actingAs($this->admin)
        ->postJson("/api/v1/perfis/{$perfil->id}/permissoes", ['permissao_id' => $permissao->id])
        ->assertCreated()
        ->assertJsonPath('permissoes.0.codigo', 'classificacao.publicar');

    expect($perfil->fresh()->permissoes->pluck('id'))->toContain($permissao->id);
});

it('atribuir a mesma permissao duas vezes nao duplica a associacao', function () {
    $permissao = Permissao::create(['codigo' => 'classificacao.publicar']);
    $perfil = Perfil::where('nome', 'DOCENTE')->first();

    $this->actingAs($this->admin)->postJson("/api/v1/perfis/{$perfil->id}/permissoes", ['permissao_id' => $permissao->id]);
    $this->actingAs($this->admin)->postJson("/api/v1/perfis/{$perfil->id}/permissoes", ['permissao_id' => $permissao->id]);

    expect($perfil->fresh()->permissoes()->count())->toBe(1);
});

it('retira uma permissao de um perfil', function () {
    $permissao = Permissao::create(['codigo' => 'classificacao.publicar']);
    $perfil = Perfil::where('nome', 'DOCENTE')->first();
    $perfil->permissoes()->attach($permissao->id);

    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/perfis/{$perfil->id}/permissoes/{$permissao->id}")
        ->assertNoContent();

    expect($perfil->fresh()->permissoes)->toHaveCount(0);
});

it('um utilizador com a permissao atribuida ao seu perfil fica com temPermissao verdadeiro', function () {
    $permissao = Permissao::create(['codigo' => 'classificacao.publicar']);
    $docenteUtilizador = Utilizador::where('email', 'docente@escn.ac.mz')->first();
    $perfilDocente = Perfil::where('nome', 'DOCENTE')->first();

    $this->actingAs($this->admin)->postJson("/api/v1/perfis/{$perfilDocente->id}/permissoes", ['permissao_id' => $permissao->id]);

    expect($docenteUtilizador->fresh()->load('perfis.permissoes')->temPermissao('classificacao.publicar'))->toBeTrue();
});

it('recusa a gestao de perfis a quem nao e administrador', function () {
    $ana = Estudante::where('numero_estudante', '2026/0001')->first();

    $this->actingAs($ana->utilizador)
        ->postJson('/api/v1/perfis', ['nome' => 'X'])
        ->assertForbidden();
});
