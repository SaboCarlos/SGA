<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Dados iniciais da instituicao (5 cursos, 25 disciplinas e docentes,
     * 50 alunos). Os testes automaticos nao passam por aqui: semeiam o
     * CenarioAcademicoSeeder directamente.
     */
    public function run(): void
    {
        $this->call(InstituicaoSeeder::class);
    }
}
