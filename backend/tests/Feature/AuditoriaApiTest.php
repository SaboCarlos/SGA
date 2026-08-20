<?php

declare(strict_types=1);

use App\Models\Estudante;
use App\Models\RegistoAuditoria;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->admin = Utilizador::administrador()->first();
});

it('o administrador consulta o registo de auditoria (UC31)', function () {
    RegistoAuditoria::create([
        'utilizador_id' => $this->admin->id,
        'acao' => 'inscricao.dispensa_pre_requisitos',
        'entidade' => 'inscricao',
        'entidade_id' => (string) Str::uuid(),
    ]);

    $this->actingAs($this->admin)
        ->getJson('/api/v1/auditoria')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.acao', 'inscricao.dispensa_pre_requisitos');
});

it('filtra a auditoria por entidade', function () {
    RegistoAuditoria::create(['utilizador_id' => $this->admin->id, 'acao' => 'x', 'entidade' => 'inscricao', 'entidade_id' => '1']);
    RegistoAuditoria::create(['utilizador_id' => $this->admin->id, 'acao' => 'y', 'entidade' => 'classificacao', 'entidade_id' => '2']);

    $this->actingAs($this->admin)
        ->getJson('/api/v1/auditoria?entidade=classificacao')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.entidade', 'classificacao');
});

it('recusa a consulta da auditoria a um estudante', function () {
    $ana = Estudante::where('numero_estudante', '2026/0001')->first();

    $this->actingAs($ana->utilizador)
        ->getJson('/api/v1/auditoria')
        ->assertForbidden();
});
