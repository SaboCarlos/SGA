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
use Illuminate\Support\Str;

/**
 * Cenario da instituicao para demonstracao e uso diario (`make instalar`):
 *
 *  - 5 cursos, cada um com uma turma de alunos num ano diferente
 *    (1.º, 2.º, 2.º, 3.º e 4.º ano);
 *  - 5 disciplinas por curso, desse ano, cada uma com o seu docente
 *    (25 docentes diferentes, cada um so com uma disciplina);
 *  - 10 alunos por curso (50 no total), inscritos nas 5 disciplinas do seu ano.
 *
 * Nao lanca avaliacoes nem notas: isso e trabalho dos docentes.
 * Todas as contas usam a palavra-passe "palavra-passe".
 *
 * Os testes automaticos usam o CenarioAcademicoSeeder, mais pequeno e
 * desenhado para os casos de teste.
 */
class InstituicaoSeeder extends Seeder
{
    private const PALAVRA_PASSE = 'palavra-passe';

    private const ANO_LECTIVO = 2026;

    /** codigo, designacao, ano dos alunos, disciplinas (codigo => designacao) */
    private const CURSOS = [
        ['LEI', 'Licenciatura em Engenharia Informática', 1, [
            'LEI101' => 'Programação I',
            'LEI102' => 'Matemática Discreta',
            'LEI103' => 'Introdução aos Sistemas Informáticos',
            'LEI104' => 'Álgebra Linear',
            'LEI105' => 'Comunicação Técnica',
        ]],
        ['PSIT', 'Programação de Sistemas Informáticos e de Telecomunicações', 2, [
            'PSI201' => 'Programação II',
            'PSI202' => 'Estruturas de Dados',
            'PSI203' => 'Sistemas Digitais',
            'PSI204' => 'Bases de Dados I',
            'PSI205' => 'Probabilidades e Estatística',
        ]],
        ['LRT', 'Licenciatura em Redes e Telecomunicações', 2, [
            'LRT201' => 'Redes de Computadores I',
            'LRT202' => 'Sinais e Sistemas',
            'LRT203' => 'Electrónica Analógica',
            'LRT204' => 'Sistemas Operativos',
            'LRT205' => 'Análise Matemática II',
        ]],
        ['LEN', 'Licenciatura em Engenharia Náutica', 3, [
            'LEN301' => 'Navegação Costeira',
            'LEN302' => 'Meteorologia Marítima',
            'LEN303' => 'Estabilidade do Navio',
            'LEN304' => 'Segurança Marítima',
            'LEN305' => 'Inglês Técnico Marítimo',
        ]],
        ['LGP', 'Licenciatura em Gestão Portuária', 4, [
            'LGP401' => 'Logística Portuária',
            'LGP402' => 'Direito Marítimo',
            'LGP403' => 'Gestão de Terminais',
            'LGP404' => 'Economia dos Transportes',
            'LGP405' => 'Projecto Final',
        ]],
    ];

    /** 25 docentes, um por disciplina, pela ordem das disciplinas acima. */
    private const DOCENTES = [
        'Helena Zandamela', 'Carlos Macuácua', 'Joana Nhantumbo', 'Idalécio Cossa', 'Marta Sitoe',
        'Paulo Chissano', 'Isabel Mabjaia', 'Rui Tembe', 'Luísa Muianga', 'Fernando Machava',
        'Rosa Langa', 'Alberto Matsinhe', 'Teresa Ubisse', 'Jorge Mondlane', 'Celeste Chirindza',
        'Américo Nhaca', 'Graça Fumo', 'Samuel Guambe', 'Lurdes Massingue', 'Armando Bila',
        'Esperança Mucavele', 'Tomás Cumbe', 'Felizarda Mandlate', 'Domingos Novela', 'Benedita Cuamba',
    ];

    private const NOMES = [
        'Ana', 'Bruno', 'Cátia', 'Dinis', 'Elsa', 'Fábio', 'Glória', 'Hélio', 'Inês', 'Júlio',
        'Kátia', 'Lino', 'Márcia', 'Nelson', 'Olga', 'Pedro', 'Quitéria', 'Raul', 'Sónia', 'Tiago',
        'Vânia', 'Wilson', 'Yolanda', 'Zacarias', 'Amélia',
    ];

    private const APELIDOS = [
        'Chauque', 'Mussa', 'Macamo', 'Tivane', 'Mabunda', 'Nhamussua', 'Sambo', 'Manjate', 'Cuna', 'Matavele',
    ];

    public function run(): void
    {
        [$perfilAdmin, $perfilDocente, $perfilEstudante] = [
            Perfil::firstOrCreate(['nome' => 'ADMINISTRADOR']),
            Perfil::firstOrCreate(['nome' => 'DOCENTE']),
            Perfil::firstOrCreate(['nome' => 'ESTUDANTE']),
        ];
        Perfil::firstOrCreate(['nome' => 'COORDENADOR']);

        $admin = $this->utilizador('Administrador SGA', 'admin@escn.ac.mz');
        $admin->perfis()->attach($perfilAdmin->id);

        $anoLectivo = AnoLectivo::create([
            'designacao' => (string) self::ANO_LECTIVO,
            'data_inicio' => self::ANO_LECTIVO.'-01-01',
            'data_fim' => self::ANO_LECTIVO.'-12-31',
            'estado' => 'ACTIVO',
        ]);

        // Inscricoes e lancamento de notas abertos a partir de hoje.
        $periodo = PeriodoLectivo::create([
            'ano_lectivo_id' => $anoLectivo->id,
            'semestre' => 1,
            'inscricao_inicio' => now()->subDays(7)->toDateString(),
            'inscricao_fim' => now()->addDays(30)->toDateString(),
            'notas_inicio' => now()->subDays(7)->toDateString(),
            'notas_fim' => now()->addDays(120)->toDateString(),
        ]);

        $indiceDocente = 0;
        $indiceAluno = 0;

        foreach (self::CURSOS as [$codigoCurso, $designacaoCurso, $ano, $disciplinas]) {
            $curso = Curso::create([
                'codigo' => $codigoCurso,
                'designacao' => $designacaoCurso,
                'grau' => 'Licenciatura',
                'duracao_anos' => 4,
                'total_creditos' => 240,
                'regulamento_avaliacao' => 'PADRAO_ESCN',
                'activo' => true,
            ]);

            $plano = PlanoCurricular::create([
                'curso_id' => $curso->id,
                'versao' => (string) self::ANO_LECTIVO,
                'ano_inicio_vigencia' => self::ANO_LECTIVO,
                'activo' => true,
            ]);

            $turmas = [];

            foreach ($disciplinas as $codigo => $designacao) {
                $disciplina = Disciplina::create([
                    'codigo' => $codigo,
                    'designacao' => $designacao,
                    'creditos' => 6,
                    'carga_horaria' => 90,
                    'activo' => true,
                ]);

                ItemPlanoCurricular::create([
                    'plano_id' => $plano->id,
                    'disciplina_id' => $disciplina->id,
                    'ano_curricular' => $ano,
                    'semestre' => 1,
                    'tipo' => 'OBRIGATORIA',
                ]);

                $docente = $this->docente(self::DOCENTES[$indiceDocente], $indiceDocente + 1, $perfilDocente);
                $indiceDocente++;

                // O primeiro docente de cada curso coordena-o: e quem decide os
                // pedidos de rectificacao (US-19). Fica so com o perfil DOCENTE,
                // por isso continua a ver apenas a sua disciplina.
                if ($curso->coordenador_id === null) {
                    $curso->update(['coordenador_id' => $docente->id]);
                }

                $turmas[] = Turma::create([
                    'disciplina_id' => $disciplina->id,
                    'periodo_lectivo_id' => $periodo->id,
                    'docente_id' => $docente->id,
                    'designacao' => 'A',
                    'capacidade_maxima' => 30,
                ]);
            }

            // 10 alunos do curso, no ano indicado: entraram ha (ano - 1) anos.
            $anoIngresso = self::ANO_LECTIVO - $ano + 1;

            for ($n = 1; $n <= 10; $n++) {
                $estudante = $this->estudante($indiceAluno, $curso, $plano, $anoIngresso, $perfilEstudante);
                $indiceAluno++;

                foreach ($turmas as $turma) {
                    Inscricao::create([
                        'estudante_id' => $estudante->id,
                        'turma_id' => $turma->id,
                        'estado' => 'ACTIVA',
                    ]);
                }
            }
        }
    }

    private function utilizador(string $nome, string $email): Utilizador
    {
        return Utilizador::create([
            'nome' => $nome,
            'email' => $email,
            'palavra_passe' => Hash::make(self::PALAVRA_PASSE),
            'activo' => true,
        ]);
    }

    private function docente(string $nome, int $numero, Perfil $perfil): Docente
    {
        $utilizador = $this->utilizador('Prof. '.$nome, $this->email($nome));
        $utilizador->perfis()->attach($perfil->id);

        return Docente::create([
            'utilizador_id' => $utilizador->id,
            'codigo' => sprintf('DOC%03d', $numero),
            'nome_completo' => $nome,
            'grau_academico' => $numero % 3 === 0 ? 'Doutor' : 'Mestre',
            'categoria' => $numero % 3 === 0 ? 'Professor Auxiliar' : 'Assistente',
            'activo' => true,
        ]);
    }

    private function estudante(int $indice, Curso $curso, PlanoCurricular $plano, int $anoIngresso, Perfil $perfil): Estudante
    {
        // 25 nomes x 10 apelidos: o par (nome, apelido) nunca se repete nos 50 alunos.
        $nome = self::NOMES[$indice % 25].' '.self::APELIDOS[($indice + intdiv($indice, 25)) % 10];

        $utilizador = $this->utilizador($nome, $this->email($nome));
        $utilizador->perfis()->attach($perfil->id);

        return Estudante::create([
            'utilizador_id' => $utilizador->id,
            'curso_id' => $curso->id,
            'plano_id' => $plano->id,
            'numero_estudante' => sprintf('%d/%04d', $anoIngresso, $indice + 1),
            'nome_completo' => $nome,
            'documento_identificacao' => sprintf('1101%05d%s', $indice + 1, chr(65 + $indice % 26)),
            'data_nascimento' => sprintf('%d-%02d-%02d', $anoIngresso - 19, $indice % 12 + 1, $indice % 28 + 1),
            'sexo' => ($indice % 25) % 2 === 0 ? 'FEMININO' : 'MASCULINO',
            'ano_ingresso' => $anoIngresso,
            'estado' => 'ACTIVO',
        ]);
    }

    /** "Idalécio Cossa" -> idalecio.cossa@escn.ac.mz (sem acentos). */
    private function email(string $nome): string
    {
        return Str::of(Str::ascii($nome))->lower()->replace(' ', '.')->append('@escn.ac.mz')->toString();
    }
}
