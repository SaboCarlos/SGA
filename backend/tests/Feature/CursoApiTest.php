<?php

declare(strict_types=1);

use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Utilizador;
use Database\Seeders\CenarioAcademicoSeeder;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
    $this->admin = Utilizador::administrador()->first();
});

it('lista os cursos activos', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/cursos')
        ->assertOk()
        ->assertJsonCount(1);
});

it('cria um curso novo (UC08)', function () {
    $this->actingAs($this->admin)->postJson('/api/v1/cursos', [
        'codigo' => 'GNAV',
        'designacao' => 'Gestão Náutica',
        'grau' => 'Licenciatura',
        'duracao_anos' => 4,
        'total_creditos' => 240,
    ])->assertCreated()->assertJsonPath('codigo', 'GNAV');
});

it('devolve o plano curricular do curso (UC10)', function () {
    $curso = Curso::first();

    $resposta = $this->actingAs($this->admin)->getJson("/api/v1/cursos/{$curso->id}/plano-curricular");

    $resposta->assertOk()->assertJsonCount(4);
});

it('acrescenta uma disciplina ao plano curricular', function () {
    $curso = Curso::first();
    $disciplina = Disciplina::create(['codigo' => 'INF401', 'designacao' => 'Sistemas Distribuídos', 'creditos' => 6, 'carga_horaria' => 90, 'activo' => true]);

    $this->actingAs($this->admin)->postJson("/api/v1/cursos/{$curso->id}/plano-curricular", [
        'disciplina_id' => $disciplina->id,
        'ano_curricular' => 3,
        'semestre' => 1,
    ])->assertCreated();
});

it('recusa acrescentar disciplina com ano curricular acima da duracao do curso (US-09)', function () {
    $curso = Curso::first();
    $disciplina = Disciplina::create(['codigo' => 'INF999', 'designacao' => 'Fora de escala', 'creditos' => 6, 'carga_horaria' => 90, 'activo' => true]);

    $this->actingAs($this->admin)->postJson("/api/v1/cursos/{$curso->id}/plano-curricular", [
        'disciplina_id' => $disciplina->id,
        'ano_curricular' => 99,
        'semestre' => 1,
    ])->assertStatus(422);
});

it('recusa disciplina repetida no mesmo plano curricular (US-09)', function () {
    $curso = Curso::first();
    $d1 = Disciplina::where('codigo', 'INF101')->first();

    $this->actingAs($this->admin)->postJson("/api/v1/cursos/{$curso->id}/plano-curricular", [
        'disciplina_id' => $d1->id,
        'ano_curricular' => 1,
        'semestre' => 1,
    ])->assertStatus(422);
});
