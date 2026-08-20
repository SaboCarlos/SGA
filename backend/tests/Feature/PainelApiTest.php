<?php

declare(strict_types=1);

use App\Models\Estudante;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
});

it('mostra indicadores de estudante para um perfil ESTUDANTE (UC30)', function () {
    $ana = Estudante::where('numero_estudante', '2026/0001')->first();

    $this->actingAs($ana->utilizador)
        ->getJson('/api/v1/painel')
        ->assertOk()
        ->assertJsonStructure(['disciplinas_inscritas', 'disciplinas_aprovadas', 'estado']);
});

it('mostra indicadores de docente para um perfil DOCENTE', function () {
    $docente = Utilizador::where('email', 'docente@escn.ac.mz')->first();

    $this->actingAs($docente)
        ->getJson('/api/v1/painel')
        ->assertOk()
        ->assertJsonStructure(['turmas_atribuidas', 'avaliacoes_por_publicar']);
});

it('mostra indicadores globais para o Administrador', function () {
    $admin = Utilizador::administrador()->first();

    $this->actingAs($admin)
        ->getJson('/api/v1/painel')
        ->assertOk()
        ->assertJsonStructure(['total_estudantes', 'total_inscricoes_activas', 'total_turmas']);
});

it('mostra os cursos coordenados a um docente que tambem e Coordenador (US-23)', function () {
    $docente = Utilizador::where('email', 'docente@escn.ac.mz')->first();

    $resposta = $this->actingAs($docente)->getJson('/api/v1/painel');

    $resposta->assertOk()
        ->assertJsonStructure(['turmas_atribuidas', 'cursos_coordenados' => [['curso', 'total_estudantes', 'inscricoes_activas', 'rectificacoes_pendentes']]])
        ->assertJsonPath('cursos_coordenados.0.curso', 'Programação de Sistemas Informáticos e de Telecomunicações')
        ->assertJsonPath('cursos_coordenados.0.total_estudantes', 2);
});
