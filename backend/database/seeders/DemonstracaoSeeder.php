<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Dominio\Enums\SituacaoFrequencia;
use App\Dominio\Enums\TipoAvaliacao;
use App\Dominio\Regras\Regulamento\RegulamentoPadraoESCN;
use App\Dominio\Valores\NotaAvaliacao;
use App\Models\AnoLectivo;
use App\Models\Avaliacao;
use App\Models\Classificacao;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Docente;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\ItemPlanoCurricular;
use App\Models\Notificacao;
use App\Models\Perfil;
use App\Models\PeriodoLectivo;
use App\Models\PlanoCurricular;
use App\Models\Turma;
use App\Models\Utilizador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Volume de demonstracao, por cima do CenarioAcademicoSeeder (RNF-30).
 *
 * Deliberadamente SEPARADO do CenarioAcademicoSeeder: esse e um cenario
 * minimo e afinado, do qual os testes de integracao dependem valor a valor
 * (ha assercoes sobre "2 estudantes", sobre a turma esgotada de capacidade
 * 1, etc.). Acrescentar volume la dentro partia esses testes. Aqui, em
 * contrapartida, nada e assumido por nenhum teste -- este seeder existe
 * para que o painel, os relatorios e as listagens tenham dados suficientes
 * para se lerem como um sistema real, em vez de tres linhas.
 *
 * Corre com:  php artisan db:seed --class=DemonstracaoSeeder
 * ou:         make dados-demo
 *
 * E idempotente por omissao: se ja existir o estudante 2026/1001, sai sem
 * fazer nada, para nao duplicar tudo a cada invocacao.
 */
class DemonstracaoSeeder extends Seeder
{
    /** Distribuicao dos estados de matricula -- soma 60 estudantes. */
    private const ESTADOS = [
        'ACTIVO' => 44,
        'SUSPENSO' => 5,
        'GRADUADO' => 6,
        'TRANSFERIDO' => 3,
        'DESISTENTE' => 2,
    ];

    private const PRIMEIROS_NOMES = [
        'Amélia', 'Belmiro', 'Carla', 'Dinis', 'Elsa', 'Fernando', 'Gilda', 'Hélder',
        'Isaura', 'Jorge', 'Kátia', 'Lázaro', 'Márcia', 'Nelson', 'Olívia', 'Paulo',
        'Quitéria', 'Rogério', 'Sónia', 'Tomás', 'Ubaldo', 'Vânia', 'Wilson', 'Xénia',
        'Yara', 'Zacarias', 'Adélia', 'Bento', 'Célia', 'Damião',
    ];

    private const APELIDOS = [
        'Macuácua', 'Chissano', 'Mabjaia', 'Nhantumbo', 'Cossa', 'Muianga', 'Sitoe',
        'Bila', 'Tembe', 'Machava', 'Langa', 'Zandamela', 'Matsinhe', 'Ubisse',
        'Mondlane', 'Chirindza', 'Nhaca', 'Fumo', 'Guambe', 'Massingue',
    ];

    public function run(): void
    {
        if (Estudante::where('numero_estudante', '2026/1001')->exists()) {
            $this->command?->warn('DemonstracaoSeeder: dados ja presentes, nada a fazer.');

            return;
        }

        // Sequencia fixa: o painel de demonstracao tem de ser reproduzivel
        // entre maquinas (a mesma apresentacao da os mesmos graficos).
        mt_srand(20260905);

        $curso = Curso::where('codigo', 'PSIT')->firstOrFail();
        $plano = PlanoCurricular::where('curso_id', $curso->id)->firstOrFail();
        $periodo = PeriodoLectivo::where('semestre', 1)->firstOrFail();
        $perfilEstudante = Perfil::where('nome', 'ESTUDANTE')->firstOrFail();

        $prof = Docente::where('codigo', 'DOC001')->firstOrFail();
        $docentes = array_merge([$prof], $this->criarDocentes());

        $turmas = $this->criarOfertaFormativa($plano, $periodo, $docentes);
        $estudantes = $this->criarEstudantes($curso, $plano, $perfilEstudante);

        $this->inscrever($estudantes, $turmas);
        $this->criarAvaliacoes($turmas);
        $this->criarNotificacoes();
    }

    /** @return list<Docente> */
    private function criarDocentes(): array
    {
        $perfilDocente = Perfil::where('nome', 'DOCENTE')->firstOrFail();

        $definicoes = [
            ['DOC002', 'Helena Zandamela', 'Doutora', 'Professora Associada', 'Redes e Sistemas'],
            ['DOC003', 'Idalécio Cossa', 'Mestre', 'Docente Auxiliar', 'Engenharia de Software'],
            ['DOC004', 'Joana Nhantumbo', 'Licenciada', 'Assistente', 'Matemática Aplicada'],
        ];

        $criados = [];

        foreach ($definicoes as [$codigo, $nome, $grau, $categoria, $area]) {
            $utilizador = Utilizador::create([
                'nome' => 'Prof. '.$nome,
                'email' => strtolower(explode(' ', $nome)[0]).'@escn.ac.mz',
                'palavra_passe' => Hash::make('palavra-passe'),
                'activo' => true,
            ]);
            $utilizador->perfis()->attach($perfilDocente->id);

            $criados[] = Docente::create([
                'utilizador_id' => $utilizador->id,
                'codigo' => $codigo,
                'nome_completo' => $nome,
                'grau_academico' => $grau,
                'categoria' => $categoria,
                'area_cientifica' => $area,
                'activo' => true,
            ]);
        }

        return $criados;
    }

    /**
     * Disciplinas adicionais do plano + uma turma por disciplina. As quatro
     * disciplinas do cenario minimo ja existem; estas completam os dois
     * primeiros anos para o grafico de aproveitamento ter espinha dorsal.
     *
     * @param  list<Docente>  $docentes
     * @return list<Turma>
     */
    private function criarOfertaFormativa(PlanoCurricular $plano, PeriodoLectivo $periodo, array $docentes): array
    {
        $novas = [
            ['MAT101', 'Análise Matemática I', 6, 90, 1],
            ['FIS101', 'Física Aplicada', 5, 75, 1],
            ['INF102', 'Sistemas Digitais', 5, 75, 1],
            ['INF202', 'Estruturas de Dados', 6, 90, 2],
            ['INF203', 'Bases de Dados', 6, 90, 2],
            ['TEL201', 'Sistemas de Telecomunicações', 6, 90, 2],
        ];

        foreach ($novas as [$codigo, $designacao, $creditos, $carga, $ano]) {
            $disciplina = Disciplina::create([
                'codigo' => $codigo,
                'designacao' => $designacao,
                'creditos' => $creditos,
                'carga_horaria' => $carga,
                'activo' => true,
            ]);

            ItemPlanoCurricular::create([
                'plano_id' => $plano->id,
                'disciplina_id' => $disciplina->id,
                'ano_curricular' => $ano,
                'semestre' => 1,
                'tipo' => 'OBRIGATORIA',
            ]);
        }

        // Turmas para todas as disciplinas sem turma neste periodo -- as tres
        // do cenario minimo ja tem a sua, e sao deixadas intactas (a de
        // capacidade 1 sustenta o teste da RN-04).
        $semTurma = Disciplina::whereDoesntHave(
            'turmas',
            fn ($q) => $q->where('periodo_lectivo_id', $periodo->id)
        )->get();

        $turmas = [];
        $i = 0;

        foreach ($semTurma as $disciplina) {
            $turmas[] = Turma::create([
                'disciplina_id' => $disciplina->id,
                'periodo_lectivo_id' => $periodo->id,
                'docente_id' => $docentes[$i % count($docentes)]->id,
                'designacao' => 'A',
                'capacidade_maxima' => [25, 30, 35, 40][$i % 4],
            ]);
            $i++;
        }

        // As turmas pre-existentes tambem recebem inscritos: sem isso metade
        // do grafico de ocupacao ficaria a zero sem razao nenhuma. A turma de
        // capacidade 1 fica de fora -- ja esta cheia e assim tem de ficar.
        $preExistentes = Turma::where('periodo_lectivo_id', $periodo->id)
            ->where('capacidade_maxima', '>', 1)
            ->whereNotIn('id', array_map(fn (Turma $t) => $t->id, $turmas))
            ->get()
            ->all();

        return array_merge($preExistentes, $turmas);
    }

    /** @return list<Estudante> */
    private function criarEstudantes(Curso $curso, PlanoCurricular $plano, Perfil $perfilEstudante): array
    {
        $estados = [];

        foreach (self::ESTADOS as $estado => $quantos) {
            $estados = array_merge($estados, array_fill(0, $quantos, $estado));
        }

        $estudantes = [];

        foreach ($estados as $indice => $estado) {
            $numero = 1001 + $indice;
            $nome = self::PRIMEIROS_NOMES[$indice % count(self::PRIMEIROS_NOMES)]
                .' '.self::APELIDOS[($indice * 7) % count(self::APELIDOS)];

            $utilizador = Utilizador::create([
                'nome' => $nome,
                'email' => sprintf('estudante%d@escn.ac.mz', $numero),
                'palavra_passe' => Hash::make('palavra-passe'),
                'activo' => $estado === 'ACTIVO',
            ]);
            $utilizador->perfis()->attach($perfilEstudante->id);

            $estudantes[] = Estudante::create([
                'utilizador_id' => $utilizador->id,
                'curso_id' => $curso->id,
                'plano_id' => $plano->id,
                'numero_estudante' => sprintf('2026/%04d', $numero),
                'nome_completo' => $nome,
                'documento_identificacao' => sprintf('1101%06dX', $numero),
                'data_nascimento' => sprintf('%d-%02d-%02d', 2000 + ($indice % 6), ($indice % 12) + 1, ($indice % 27) + 1),
                'sexo' => $indice % 2 === 0 ? 'FEMININO' : 'MASCULINO',
                'ano_ingresso' => 2026 - ($indice % 3),
                'estado' => $estado,
            ]);
        }

        return $estudantes;
    }

    /**
     * Inscreve cada estudante em 3 a 5 turmas, respeitando a capacidade, e
     * fecha uma parte das inscricoes com nota. As inscricoes por fechar
     * ficam EM_CURSO -- e o que da ao grafico de aproveitamento as tres
     * classes em vez de duas.
     *
     * @param  list<Estudante>  $estudantes
     * @param  list<Turma>  $turmas
     */
    private function inscrever(array $estudantes, array $turmas): void
    {
        $lotacao = [];

        foreach ($turmas as $turma) {
            $lotacao[$turma->id] = Inscricao::where('turma_id', $turma->id)->where('estado', 'ACTIVA')->count();
        }

        foreach ($estudantes as $indice => $estudante) {
            // Quem desistiu ou se transferiu nao tem inscricoes activas neste
            // semestre -- so historico, e nem sempre.
            $quantas = match ($estudante->estado) {
                'DESISTENTE', 'TRANSFERIDO' => 1,
                'SUSPENSO' => 2,
                default => 3 + ($indice % 3),
            };

            $escolhidas = $this->baralhar($turmas, $indice);
            $feitas = 0;

            foreach ($escolhidas as $turma) {
                if ($feitas >= $quantas) {
                    break;
                }

                if ($lotacao[$turma->id] >= $turma->capacidade_maxima) {
                    continue;
                }

                // A semente combina estudante e turma (numeros primos, para
                // nao alinhar com a rotacao do baralhar): se dependesse so da
                // ordem, disciplinas inteiras saiam todas fechadas ou todas
                // em curso, e a barra empilhada perdia uma das classes.
                $this->inscricao($estudante, $turma, $indice * 31 + $turma->id * 17);
                $lotacao[$turma->id]++;
                $feitas++;
            }
        }
    }

    private function inscricao(Estudante $estudante, Turma $turma, int $semente): void
    {
        // Sem notas aqui: os resultados saem das avaliacoes lancadas em
        // lancarNotas(), calculados pelas mesmas regras do sistema. Antes
        // inventavam-se notas finais sem nenhuma avaliacao por tras, e a
        // pauta mostrava valores que nenhum docente tinha lancado.
        // Quem ja nao esta activo (desistiu, transferiu-se...) tem a
        // inscricao anulada.
        Inscricao::create([
            'estudante_id' => $estudante->id,
            'turma_id' => $turma->id,
            'estado' => $estudante->estado === 'ACTIVO' ? 'ACTIVA' : 'ANULADA',
        ]);
    }

    /** @param  list<Turma>  $turmas */
    private function criarAvaliacoes(array $turmas): void
    {
        $modelo = [
            ['1.º Teste', 30.0, 'TESTE'],
            ['Trabalho prático', 20.0, 'TRABALHO'],
            ['Exame final', 50.0, 'EXAME'],
        ];

        foreach ($turmas as $indice => $turma) {
            foreach ($modelo as $posicao => [$designacao, $peso, $tipo]) {
                Avaliacao::create([
                    'turma_id' => $turma->id,
                    'designacao' => $designacao,
                    'peso' => $peso,
                    'tipo' => $tipo,
                    'data_prevista' => now()->addDays(15 + $posicao * 25)->toDateString(),
                    'estado' => 'RASCUNHO',
                ]);
            }

            $this->lancarNotas($turma, $indice % 3);
        }
    }

    /**
     * Cada turma fica numa fase coerente do semestre:
     *   0 — avaliada: as tres avaliacoes publicadas, frequencia e nota final
     *       calculadas pelo regulamento (os mesmos calculos da aplicacao);
     *   1 — a meio: Teste publicado, Trabalho com notas em rascunho, Exame
     *       por lancar;
     *   2 — no inicio: nada lancado.
     */
    private function lancarNotas(Turma $turma, int $fase): void
    {
        if ($fase === 2) {
            return;
        }

        $avaliacoes = Avaliacao::where('turma_id', $turma->id)->orderBy('id')->get()->values();
        [$teste, $trabalho, $exame] = [$avaliacoes[0], $avaliacoes[1], $avaliacoes[2]];
        $inscricoes = Inscricao::where('turma_id', $turma->id)->where('estado', 'ACTIVA')->get();
        $lancadaPor = $turma->docente?->utilizador_id;
        $regulamento = new RegulamentoPadraoESCN;

        $nota = static fn () => mt_rand(8, 40) / 2;   // 4,0 a 20,0, de meio em meio valor
        $lancar = function (Avaliacao $a, Inscricao $i, float $valor, bool $publicada) use ($lancadaPor): void {
            Classificacao::create([
                'avaliacao_id' => $a->id,
                'inscricao_id' => $i->id,
                'valor' => $valor,
                'faltou' => false,
                'estado' => $publicada ? 'PUBLICADA' : 'RASCUNHO',
                'lancada_por' => $lancadaPor,
                'lancada_em' => now(),
                'publicada_em' => $publicada ? now() : null,
            ]);
        };

        foreach ($inscricoes as $inscricao) {
            $notaTeste = $nota();
            $notaTrabalho = $nota();
            $lancar($teste, $inscricao, $notaTeste, true);
            $lancar($trabalho, $inscricao, $notaTrabalho, $fase === 0);

            if ($fase === 1) {
                continue;
            }

            // Teste 30% e Trabalho 20%: entre si, 60/40 (RN-07).
            $frequencia = $regulamento->calcularFrequencia([
                new NotaAvaliacao(TipoAvaliacao::TESTE, 60.0, $notaTeste),
                new NotaAvaliacao(TipoAvaliacao::TRABALHO, 40.0, $notaTrabalho),
            ]);
            $situacao = $regulamento->determinarSituacaoFrequencia($frequencia);

            $notaExame = null;
            if ($situacao === SituacaoFrequencia::ADMITIDO_EXAME) {
                $notaExame = $nota();
                $lancar($exame, $inscricao, $notaExame, true);
            }

            $final = $regulamento->calcularClassificacaoFinal($frequencia, $notaExame);

            $inscricao->update([
                'estado' => 'CONCLUIDA',
                'nota_frequencia' => $frequencia,
                'situacao_frequencia' => $situacao->value,
                'nota_exame' => $notaExame,
                'classificacao_final' => $final,
                'situacao_final' => $regulamento->determinarSituacaoFinal($final)->value,
            ]);
        }

        Avaliacao::whereIn('id', $fase === 0 ? [$teste->id, $trabalho->id, $exame->id] : [$teste->id])
            ->update(['estado' => 'PUBLICADA']);

        if ($fase === 1) {
            $trabalho->update(['estado' => 'ACTIVA']);
        }
    }

    private function criarNotificacoes(): void
    {
        $anoLectivo = AnoLectivo::where('estado', 'ACTIVO')->first();

        $avisos = [
            ['PERIODO', 'Período de inscrições a fechar', 'Faltam 30 dias para o fim das inscrições do 1.º semestre.'],
            ['PAUTA', 'Pauta por homologar', 'A pauta de Redes de Computadores aguarda homologação.'],
            ['RECTIFICACAO', 'Pedido de rectificação pendente', 'Um pedido de correcção de nota aguarda decisão.'],
            ['SISTEMA', 'Dados de demonstração carregados', 'O ambiente foi preenchido com o cenário de demonstração.'],
        ];

        foreach (Utilizador::all() as $utilizador) {
            foreach ($avisos as $posicao => [$tipo, $titulo, $mensagem]) {
                Notificacao::create([
                    'utilizador_id' => $utilizador->id,
                    'tipo' => $tipo,
                    'titulo' => $titulo,
                    'mensagem' => $mensagem,
                    'referencia' => $anoLectivo?->designacao,
                    'lida_em' => $posicao > 1 ? now()->subDays($posicao) : null,
                    'criada_em' => now()->subDays($posicao),
                ]);
            }
        }
    }

    /**
     * Permutacao deterministica dependente do estudante: sem isto todos se
     * inscreviam nas mesmas primeiras turmas e o grafico de ocupacao ficava
     * com um degrau artificial.
     *
     * @param  list<Turma>  $turmas
     * @return list<Turma>
     */
    private function baralhar(array $turmas, int $semente): array
    {
        $deslocamento = $semente % max(1, count($turmas));

        return array_merge(
            array_slice($turmas, $deslocamento),
            array_slice($turmas, 0, $deslocamento)
        );
    }
}
