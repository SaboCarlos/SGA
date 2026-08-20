<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnoLectivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Docente;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\ItemPlanoCurricular;
use App\Models\Perfil;
use App\Models\PeriodoLectivo;
use App\Models\PlanoCurricular;
use App\Models\Turma;
use App\Models\Utilizador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cenario minimo e deliberado para os testes de integracao da API
 * (tests/Feature/InscricaoApiTest.php) e para exploracao manual em
 * desenvolvimento (RNF-30).
 *
 * Cada elemento existe para satisfazer um caso de teste especifico -- ver o
 * comentario junto de cada bloco. Alterar os dados aqui exige reler
 * InscricaoApiTest.php para nao quebrar um cenario silenciosamente.
 */
class CenarioAcademicoSeeder extends Seeder
{
    public function run(): void
    {
        [$administrador, $docente, $estudante] = [
            Perfil::firstOrCreate(['nome' => 'ADMINISTRADOR']),
            Perfil::firstOrCreate(['nome' => 'DOCENTE']),
            Perfil::firstOrCreate(['nome' => 'ESTUDANTE']),
        ];
        Perfil::firstOrCreate(['nome' => 'COORDENADOR']);

        $curso = Curso::create([
            'codigo' => 'PSIT',
            'designacao' => 'Programação de Sistemas Informáticos e de Telecomunicações',
            'grau' => 'Licenciatura',
            'duracao_anos' => 4,
            'total_creditos' => 240,
            'regulamento_avaliacao' => 'PADRAO_ESCN',
            'activo' => true,
        ]);

        $plano = PlanoCurricular::create([
            'curso_id' => $curso->id,
            'versao' => '2026',
            'ano_inicio_vigencia' => 2026,
            'activo' => true,
        ]);

        // D2 tem pre-requisito D1: usada pelo cenario RN-02 (Turma::comPreRequisitos()).
        // D3 e D4 nao tem pre-requisitos: usadas pelos cenarios RN-04/comVagas, sem
        // interferir com a validacao de pre-requisitos.
        $d1 = Disciplina::create(['codigo' => 'INF101', 'designacao' => 'Programação I', 'creditos' => 6, 'carga_horaria' => 90, 'activo' => true]);
        $d2 = Disciplina::create(['codigo' => 'INF201', 'designacao' => 'Programação II', 'creditos' => 6, 'carga_horaria' => 90, 'activo' => true]);
        $d3 = Disciplina::create(['codigo' => 'INF301', 'designacao' => 'Engenharia de Software', 'creditos' => 6, 'carga_horaria' => 90, 'activo' => true]);
        $d4 = Disciplina::create(['codigo' => 'INF302', 'designacao' => 'Redes de Computadores', 'creditos' => 6, 'carga_horaria' => 90, 'activo' => true]);

        $d2->preRequisitos()->attach($d1->id);

        foreach ([[$d1, 1], [$d2, 1], [$d3, 2], [$d4, 2]] as [$disciplina, $ano]) {
            ItemPlanoCurricular::create([
                'plano_id' => $plano->id,
                'disciplina_id' => $disciplina->id,
                'ano_curricular' => $ano,
                'semestre' => 1,
                'tipo' => 'OBRIGATORIA',
            ]);
        }

        $anoLectivo = AnoLectivo::create([
            'designacao' => '2026',
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31',
            'estado' => 'ACTIVO',
        ]);

        // Periodo de inscricoes aberto hoje (RN-05 satisfeita por omissao).
        $periodo = PeriodoLectivo::create([
            'ano_lectivo_id' => $anoLectivo->id,
            'semestre' => 1,
            'inscricao_inicio' => now()->subDays(5)->toDateString(),
            'inscricao_fim' => now()->addDays(30)->toDateString(),
            'notas_inicio' => now()->addDays(60)->toDateString(),
            'notas_fim' => now()->addDays(90)->toDateString(),
        ]);

        $utilizadorDocente = Utilizador::create([
            'nome' => 'Prof. Carlos Macuácua',
            'email' => 'docente@escn.ac.mz',
            'palavra_passe' => Hash::make('palavra-passe'),
            'activo' => true,
        ]);
        $utilizadorDocente->perfis()->attach([$docente->id, Perfil::where('nome', 'COORDENADOR')->value('id')]);

        $prof = Docente::create([
            'utilizador_id' => $utilizadorDocente->id,
            'codigo' => 'DOC001',
            'nome_completo' => 'Carlos Macuácua',
            'grau_academico' => 'Mestre',
            'categoria' => 'Docente Auxiliar',
            'activo' => true,
        ]);

        $curso->update(['coordenador_id' => $prof->id]);

        // Turma::comVagas(): capacidade 30, sem inscritos.
        $turmaComVagas = Turma::create([
            'disciplina_id' => $d3->id,
            'periodo_lectivo_id' => $periodo->id,
            'docente_id' => $prof->id,
            'designacao' => 'A',
            'capacidade_maxima' => 30,
        ]);

        // Turma::comPreRequisitos(): disciplina D2 exige D1. Capacidade com vagas,
        // para que RN-04 nao interfira com o cenario RN-02.
        $turmaComPreRequisitos = Turma::create([
            'disciplina_id' => $d2->id,
            'periodo_lectivo_id' => $periodo->id,
            'docente_id' => $prof->id,
            'designacao' => 'A',
            'capacidade_maxima' => 30,
        ]);

        // Turma::esgotada(): capacidade 1, ocupada pelo estudante de preenchimento
        // abaixo -- nao pelo Estudante::first() usado nos testes.
        $turmaEsgotada = Turma::create([
            'disciplina_id' => $d4->id,
            'periodo_lectivo_id' => $periodo->id,
            'docente_id' => $prof->id,
            'designacao' => 'A',
            'capacidade_maxima' => 1,
        ]);

        // Estudante::first(): sujeito principal dos testes RN-04/comVagas. Sem
        // nenhuma inscricao previa, para nao violar RN-03 nos cenarios testados.
        $utilizadorA = Utilizador::create([
            'nome' => 'Ana Estudante',
            'email' => 'ana@escn.ac.mz',
            'palavra_passe' => Hash::make('palavra-passe'),
            'activo' => true,
        ]);
        $utilizadorA->perfis()->attach($estudante->id);

        $estudanteA = Estudante::create([
            'utilizador_id' => $utilizadorA->id,
            'curso_id' => $curso->id,
            'plano_id' => $plano->id,
            'numero_estudante' => '2026/0001',
            'nome_completo' => 'Ana Estudante',
            'documento_identificacao' => '110100001A',
            'data_nascimento' => '2004-03-15',
            'sexo' => 'FEMININO',
            'ano_ingresso' => 2026,
            'estado' => 'ACTIVO',
        ]);

        // Estudante de preenchimento: ocupa a unica vaga de $turmaEsgotada, para
        // que Estudante::first() (Ana) continue livre e o teste da RN-04 nao
        // colida com RN-03 (inscricao duplicada).
        $utilizadorB = Utilizador::create([
            'nome' => 'Bruno Estudante',
            'email' => 'bruno@escn.ac.mz',
            'palavra_passe' => Hash::make('palavra-passe'),
            'activo' => true,
        ]);
        $utilizadorB->perfis()->attach($estudante->id);

        $estudanteB = Estudante::create([
            'utilizador_id' => $utilizadorB->id,
            'curso_id' => $curso->id,
            'plano_id' => $plano->id,
            'numero_estudante' => '2026/0002',
            'nome_completo' => 'Bruno Estudante',
            'documento_identificacao' => '110100002B',
            'data_nascimento' => '2003-07-22',
            'sexo' => 'MASCULINO',
            'ano_ingresso' => 2026,
            'estado' => 'ACTIVO',
        ]);

        Inscricao::create([
            'estudante_id' => $estudanteB->id,
            'turma_id' => $turmaEsgotada->id,
            'estado' => 'ACTIVA',
        ]);

        $administradorUtilizador = Utilizador::create([
            'nome' => 'Administrador SGA',
            'email' => 'admin@escn.ac.mz',
            'palavra_passe' => Hash::make('palavra-passe'),
            'activo' => true,
        ]);
        $administradorUtilizador->perfis()->attach($administrador->id);
    }
}
