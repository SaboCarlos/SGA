<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Avaliacao;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\PedidoRectificacao;
use App\Models\Turma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * UC30 (US-27, US-23). Indicadores por perfil; um utilizador com varios
 * perfis ve todos.
 *
 * Alem dos contadores escalares (os cartoes do topo do painel), devolve
 * series agregadas prontas a desenhar. Sao computadas aqui, e nao no
 * cliente, por duas razoes: o cliente teria de puxar as coleccoes
 * completas so para as contar, e a agregacao tem de respeitar o mesmo
 * ambito de visibilidade que o resto da API -- um Coordenador ve apenas
 * os cursos que coordena, o Administrador ve tudo.
 *
 * As series vem sempre com todas as classes do dominio presentes, mesmo a
 * zero: o grafico mantem a mesma legenda e a mesma cor por classe entre
 * carregamentos, em vez de a paleta saltar quando uma classe fica vazia.
 */
class PainelController extends Controller
{
    /** Ordem fixa das classes: garante cor estavel por classe no cliente. */
    private const ESTADOS_ESTUDANTE = ['ACTIVO', 'SUSPENSO', 'TRANSFERIDO', 'GRADUADO', 'DESISTENTE'];

    private const SITUACOES_FINAIS = ['EM_CURSO', 'APROVADO', 'REPROVADO'];

    private const ESTADOS_AVALIACAO = ['RASCUNHO', 'ACTIVA', 'PUBLICADA'];

    public function __invoke(Request $request)
    {
        $utilizador = $request->user();
        $dados = [];

        if ($utilizador->estudante) {
            /** @var Estudante $estudante */
            $estudante = $utilizador->estudante;
            $dados += $this->indicadoresEstudante($estudante);
        }

        if ($utilizador->docente) {
            /** @var Docente $docente */
            $docente = $utilizador->docente;
            $dados += $this->indicadoresDocente($docente);
        }

        // Ambito das series agregadas: o Administrador ve a instituicao
        // inteira; o Coordenador que nao seja Administrador ve apenas os
        // estudantes dos cursos que coordena.
        if ($utilizador->temPerfil('ADMINISTRADOR')) {
            $dados += [
                'total_estudantes' => Estudante::count(),
                'total_inscricoes_activas' => Inscricao::where('estado', 'ACTIVA')->count(),
                'total_turmas' => Turma::count(),
            ];

            $dados += $this->seriesAgregadas(null);
        } elseif (isset($dados['cursos_coordenados'])) {
            $cursosIds = Curso::where('coordenador_id', $utilizador->docente->id)->pluck('id');

            $dados += $this->seriesAgregadas($cursosIds->all());
        }

        return response()->json($dados);
    }

    /** @return array<string, mixed> */
    private function indicadoresEstudante(Estudante $estudante): array
    {
        /** @var Collection<int, Inscricao> $inscricoes */
        $inscricoes = $estudante->inscricoes()->where('estado', '<>', 'ANULADA')->get();

        return [
            'disciplinas_inscritas' => $estudante->inscricoes()->where('estado', 'ACTIVA')->count(),
            'disciplinas_aprovadas' => $estudante->inscricoes()->where('situacao_final', 'APROVADO')->count(),
            'estado' => $estudante->estado,

            // Parte-todo do percurso: quantas das disciplinas frequentadas
            // ja estao aprovadas, reprovadas ou ainda em curso.
            'situacao_disciplinas' => $this->contarPorClasse(
                $inscricoes->pluck('situacao_final'),
                self::SITUACOES_FINAIS
            ),

            // Notas ja lancadas, por disciplina -- a serie que sustenta o
            // grafico de barras do percurso academico (escala 0-20).
            'notas_por_disciplina' => $this->comNotaLancada($estudante)
                ->map(fn (Inscricao $i) => [
                    'codigo' => $i->turma->disciplina->codigo,
                    'disciplina' => $i->turma->disciplina->designacao,
                    'nota' => (float) $i->classificacao_final,
                    'situacao' => $i->situacao_final,
                ])
                ->sortByDesc('nota')
                ->values(),
        ];
    }

    /**
     * Inscricoes do estudante ja fechadas com classificacao final.
     *
     * @return Collection<int, Inscricao>
     */
    private function comNotaLancada(Estudante $estudante): Collection
    {
        /** @var Collection<int, Inscricao> $inscricoes */
        $inscricoes = $estudante->inscricoes()
            ->whereNotNull('classificacao_final')
            ->with('turma.disciplina')
            ->get();

        return $inscricoes;
    }

    /** @return array<string, mixed> */
    private function indicadoresDocente(Docente $docente): array
    {
        /** @var Collection<int, Turma> $turmas */
        $turmas = Turma::where('docente_id', $docente->id)->with('disciplina')->get();
        $turmasIds = $turmas->pluck('id');

        $dados = [
            'turmas_atribuidas' => $turmas->count(),
            'avaliacoes_por_publicar' => Avaliacao::whereIn('turma_id', $turmasIds)
                ->where('estado', '<>', 'PUBLICADA')
                ->count(),

            'avaliacoes_por_estado' => $this->contarPorClasse(
                Avaliacao::whereIn('turma_id', $turmasIds)->pluck('estado'),
                self::ESTADOS_AVALIACAO
            ),

            'ocupacao_minhas_turmas' => $this->ocupacao($turmas),
        ];

        // US-23: cursos coordenados pelo docente autenticado -- visao
        // agregada, distinta da lista de turmas que lecciona.
        $cursosCoordenados = Curso::where('coordenador_id', $docente->id)->get();

        if ($cursosCoordenados->isNotEmpty()) {
            $dados['cursos_coordenados'] = $cursosCoordenados->map(function (Curso $curso) {
                $estudantesIds = Estudante::where('curso_id', $curso->id)->pluck('id');

                return [
                    'curso' => $curso->designacao,
                    'total_estudantes' => $estudantesIds->count(),
                    'inscricoes_activas' => Inscricao::whereIn('estudante_id', $estudantesIds)->where('estado', 'ACTIVA')->count(),
                    'rectificacoes_pendentes' => PedidoRectificacao::whereHas(
                        'classificacao.inscricao.estudante',
                        fn ($q) => $q->where('curso_id', $curso->id)
                    )->where('estado', 'PENDENTE')->count(),
                ];
            })->values();
        }

        return $dados;
    }

    /**
     * Series de leitura institucional. $cursosIds a null significa toda a
     * instituicao (Administrador); uma lista restringe aos estudantes
     * desses cursos (Coordenador).
     *
     * @param  list<int>|null  $cursosIds
     * @return array<string, mixed>
     */
    private function seriesAgregadas(?array $cursosIds): array
    {
        $estudantes = Estudante::query()
            ->when($cursosIds !== null, fn (Builder $q) => $q->whereIn('curso_id', $cursosIds));

        $estudantesIds = (clone $estudantes)->pluck('id');

        $inscricoes = Inscricao::query()
            ->where('estado', '<>', 'ANULADA')
            ->when($cursosIds !== null, fn (Builder $q) => $q->whereIn('estudante_id', $estudantesIds));

        return [
            'estudantes_por_estado' => $this->contarPorClasse(
                (clone $estudantes)->pluck('estado'),
                self::ESTADOS_ESTUDANTE
            ),

            'inscricoes_por_situacao' => $this->contarPorClasse(
                (clone $inscricoes)->pluck('situacao_final'),
                self::SITUACOES_FINAIS
            ),

            'ocupacao_turmas' => $this->ocupacao(
                /** @var Collection<int, Turma> */
                Turma::query()
                    ->when($cursosIds !== null, fn (Builder $q) => $q->whereHas(
                        'disciplina.itensPlano.plano',
                        fn (Builder $p) => $p->whereIn('curso_id', $cursosIds)
                    ))
                    ->with('disciplina')
                    ->get()
            ),

            'aproveitamento_disciplinas' => $this->aproveitamentoPorDisciplina($cursosIds),
        ];
    }

    /**
     * Aprovados / reprovados / em curso por disciplina -- a barra empilhada
     * do painel. Limitada as dez disciplinas com mais inscritos: acima
     * disso o grafico deixa de ser legivel e o relatorio completo
     * (RF-55, /relatorios/aproveitamento-disciplina) e o sitio certo.
     *
     * @param  list<int>|null  $cursosIds
     * @return list<array<string, mixed>>
     */
    private function aproveitamentoPorDisciplina(?array $cursosIds): array
    {
        /** @var Collection<int, Inscricao> $inscricoes */
        $inscricoes = Inscricao::query()
            ->where('inscricoes.estado', '<>', 'ANULADA')
            ->with('turma.disciplina')
            ->when($cursosIds !== null, fn (Builder $q) => $q->whereHas(
                'estudante',
                fn (Builder $e) => $e->whereIn('curso_id', $cursosIds)
            ))
            ->get();

        return $inscricoes
            ->filter(fn (Inscricao $i) => $i->turma?->disciplina !== null)
            ->groupBy(fn (Inscricao $i) => $i->turma->disciplina->codigo)
            ->map(fn (Collection $grupo, string $codigo) => [
                'codigo' => $codigo,
                'disciplina' => $grupo->first()->turma->disciplina->designacao,
                'inscritos' => $grupo->count(),
                'aprovados' => $grupo->where('situacao_final', 'APROVADO')->count(),
                'reprovados' => $grupo->where('situacao_final', 'REPROVADO')->count(),
                'em_curso' => $grupo->where('situacao_final', 'EM_CURSO')->count(),
            ])
            ->sortByDesc('inscritos')
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * Lotacao de cada turma. Ordenada da mais cheia para a mais vazia --
     * a leitura util e "onde e que ja nao ha vagas" (RN-04).
     *
     * @param  Collection<int, Turma>  $turmas
     * @return list<array<string, mixed>>
     */
    private function ocupacao(Collection $turmas): array
    {
        $inscritosPorTurma = Inscricao::whereIn('turma_id', $turmas->pluck('id'))
            ->where('estado', 'ACTIVA')
            ->selectRaw('turma_id, count(*) as total')
            ->groupBy('turma_id')
            ->pluck('total', 'turma_id');

        return $turmas
            ->map(function (Turma $turma) use ($inscritosPorTurma) {
                $inscritos = (int) ($inscritosPorTurma[$turma->id] ?? 0);
                $capacidade = (int) $turma->capacidade_maxima;

                return [
                    'turma' => $turma->designacao,
                    'disciplina' => $turma->disciplina?->designacao,
                    'codigo' => $turma->disciplina?->codigo,
                    'inscritos' => $inscritos,
                    'capacidade' => $capacidade,
                    'ocupacao' => $capacidade > 0 ? round($inscritos / $capacidade * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('ocupacao')
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * Conta ocorrencias mantendo todas as classes do dominio, pela ordem
     * declarada -- ver a nota no cabecalho da classe sobre cor estavel.
     *
     * @param  Collection<int, string|null>  $valores
     * @param  list<string>  $classes
     * @return list<array{rotulo: string, total: int}>
     */
    private function contarPorClasse(Collection $valores, array $classes): array
    {
        $contagem = $valores->countBy();

        return collect($classes)
            ->map(fn (string $classe) => [
                'rotulo' => $classe,
                'total' => (int) ($contagem[$classe] ?? 0),
            ])
            ->all();
    }
}
