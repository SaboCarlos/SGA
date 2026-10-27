<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Dominio\Regras\ValidadorInscricao;
use App\Dominio\Repositorios\RepositorioInscricao;
use App\Dominio\Valores\ContextoInscricao;
use App\Http\Controllers\Controller;
use App\Models\Disciplina;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\ItemPlanoCurricular;
use App\Models\PeriodoLectivo;
use App\Models\Turma;
use App\Servicos\ServicoAuditoria;
use Illuminate\Http\Request;

/** UC14, UC16, UC17, UC18 (US-13, US-14, US-15). */
class InscricaoController extends Controller
{
    public function __construct(
        private readonly RepositorioInscricao $inscricoes,
        private readonly ValidadorInscricao $validador,
        private readonly ServicoAuditoria $auditoria,
    ) {}

    public function disponiveis(Request $request)
    {
        $estudante = $request->user()->estudante;
        abort_if(! $estudante, 403, 'Apenas estudantes têm turmas disponíveis para inscrição.');

        $periodo = $this->periodoActual();

        if (! $periodo || ! $periodo->inscricoesAbertas()) {
            return response()->json([
                'codigo' => 'RN-05',
                'mensagem' => 'O período de inscrições não está aberto.',
            ], 409);
        }

        $disciplinasDoPlano = ItemPlanoCurricular::where('plano_id', $estudante->plano_id)
            ->pluck('disciplina_id');

        $aprovadas = $this->inscricoes->disciplinasAprovadas($estudante->id);

        $turmas = Turma::with(['disciplina.preRequisitos'])
            ->where('periodo_lectivo_id', $periodo->id)
            ->whereIn('disciplina_id', $disciplinasDoPlano)
            ->get()
            ->filter(function (Turma $turma) use ($aprovadas) {
                $preReq = $turma->disciplina->preRequisitos->pluck('id')->all();

                return $turma->vagasDisponiveis() > 0
                    && array_diff($preReq, $aprovadas) === [];
            })
            ->values();

        return response()->json($turmas->map(fn (Turma $t) => $this->turmaParaResposta($t))->all());
    }

    public function index(Request $request)
    {
        $query = Inscricao::query()->with(['turma.disciplina', 'estudante']);
        $utilizador = $request->user();

        // Traz notas: a direccao ve todas, o docente as das suas turmas e o
        // estudante so as proprias, seja qual for o filtro pedido.
        if ($utilizador->ehDireccao() || $utilizador->docente) {
            if (! $utilizador->ehDireccao()) {
                $query->whereHas('turma', fn ($q) => $q->where('docente_id', $utilizador->docente->id));
            }
            if ($request->filled('estudante_id')) {
                $query->where('estudante_id', $request->string('estudante_id'));
            }
        } else {
            abort_unless($utilizador->estudante, 403, 'Sem permissão para consultar inscrições.');
            $query->where('estudante_id', $utilizador->estudante->id);
        }

        if ($request->filled('ano_lectivo') || $request->filled('semestre')) {
            $query->whereHas('turma.periodoLectivo', function ($q) use ($request) {
                if ($request->filled('ano_lectivo')) {
                    $q->whereHas('anoLectivo', fn ($qq) => $qq->where('designacao', $request->string('ano_lectivo')));
                }
                if ($request->filled('semestre')) {
                    $q->where('semestre', $request->integer('semestre'));
                }
            });
        }

        return response()->json($query->get()->map(fn (Inscricao $i) => $this->inscricaoParaResposta($i))->all());
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'turmas' => ['required', 'array', 'min:1'],
            'turmas.*' => ['integer', 'exists:turmas,id'],
            'estudante_id' => ['nullable', 'uuid', 'exists:estudantes,id'],
            'dispensar_pre_requisitos' => ['boolean'],
            'justificacao' => ['nullable', 'string'],
        ]);

        $utilizador = $request->user();
        $dispensar = $dados['dispensar_pre_requisitos'] ?? false;

        if (! empty($dados['estudante_id'])) {
            abort_unless($utilizador->temPerfil('ADMINISTRADOR'), 403, 'Apenas o Administrador pode inscrever em nome de outro utilizador.');
            $estudante = Estudante::findOrFail($dados['estudante_id']);
        } else {
            $estudante = $utilizador->estudante;
            abort_if(! $estudante, 403, 'Apenas estudantes podem inscrever-se.');
        }

        if ($dispensar) {
            abort_unless($utilizador->temPerfil('ADMINISTRADOR'), 403, 'Apenas o Administrador pode dispensar pré-requisitos.');
        }

        $turmas = Turma::with(['disciplina.preRequisitos', 'periodoLectivo.anoLectivo'])
            ->whereIn('id', $dados['turmas'])
            ->get();

        $itensPlano = ItemPlanoCurricular::where('plano_id', $estudante->plano_id)->pluck('disciplina_id')->all();
        $aprovadas = $this->inscricoes->disciplinasAprovadas($estudante->id);

        foreach ($turmas as $turma) {
            $periodo = $turma->periodoLectivo;
            $jaInscritas = $this->inscricoes->disciplinasInscritasNoPeriodo($estudante->id, $periodo->id);
            $preReqIds = $turma->disciplina->preRequisitos->pluck('id')->all();

            $designacoes = Disciplina::whereIn('id', [...$preReqIds, $turma->disciplina_id])
                ->pluck('designacao', 'id')
                ->all();

            $contexto = new ContextoInscricao(
                disciplinaPretendidaId: $turma->disciplina_id,
                disciplinasDoPlano: $itensPlano,
                disciplinasAprovadas: $aprovadas,
                preRequisitosDaDisciplina: $preReqIds,
                disciplinasJaInscritas: $jaInscritas,
                inscritosNaTurma: $turma->inscritosActivos(),
                capacidadeDaTurma: $turma->capacidade_maxima,
                periodoInscricoesAberto: $periodo->inscricoesAbertas(),
                designacaoDisciplina: $turma->disciplina->designacao,
                designacoes: $designacoes,
                dispensarPreRequisitos: $dispensar,
            );

            $resultado = $this->validador->validar($contexto);

            if (! $resultado->valido()) {
                return response()->json($resultado->primeira()->paraResposta(), 422);
            }
        }

        app('db')->transaction(function () use ($turmas, $estudante, $utilizador, $dispensar, $dados): void {
            foreach ($turmas as $turma) {
                $inscricao = new Inscricao([
                    'estudante_id' => $estudante->id,
                    'turma_id' => $turma->id,
                    'registada_por' => $utilizador->id,
                ]);
                $this->inscricoes->guardar($inscricao);

                if ($dispensar) {
                    $this->auditoria->registar(
                        'inscricao.dispensa_pre_requisitos',
                        'inscricao',
                        $inscricao->id,
                        null,
                        null,
                        $dados['justificacao'] ?? null
                    );
                }
            }
        });

        $periodo = $turmas->first()?->periodoLectivo;

        return response()->json([
            'emitido_em' => now()->toIso8601String(),
            'estudante' => $estudante->nome_completo,
            'ano_lectivo' => $periodo?->anoLectivo?->designacao,
            'semestre' => $periodo?->semestre,
            'disciplinas' => $turmas->map(fn (Turma $t) => [
                'codigo' => $t->disciplina->codigo,
                'designacao' => $t->disciplina->designacao,
                'turma' => $t->designacao,
            ])->values(),
        ], 201);
    }

    public function destroy(Request $request, Inscricao $inscricao)
    {
        $utilizador = $request->user();
        $estudante = $utilizador->estudante;

        abort_unless(
            $utilizador->temPerfil('ADMINISTRADOR') || ($estudante && $estudante->id === $inscricao->estudante_id),
            403
        );

        $periodo = $inscricao->turma->periodoLectivo;

        if (! $periodo->inscricoesAbertas()) {
            return response()->json([
                'codigo' => 'RN-05',
                'mensagem' => 'O período de inscrições está encerrado; a anulação já não é permitida.',
            ], 409);
        }

        if ($inscricao->classificacoes()->whereIn('estado', ['PUBLICADA', 'RECTIFICADA'])->exists()) {
            return response()->json([
                'mensagem' => 'Já existem classificações publicadas para esta inscrição.',
            ], 409);
        }

        $inscricao->update(['estado' => 'ANULADA']);

        return response()->noContent();
    }

    private function periodoActual(): ?PeriodoLectivo
    {
        return PeriodoLectivo::whereHas(
            'anoLectivo',
            fn ($q) => $q->where('estado', 'ACTIVO')
        )
            ->orderByDesc('id')
            ->first();
    }

    private function turmaParaResposta(Turma $turma): array
    {
        return [
            'id' => $turma->id,
            'designacao' => $turma->designacao,
            'disciplina' => [
                'id' => $turma->disciplina->id,
                'codigo' => $turma->disciplina->codigo,
                'designacao' => $turma->disciplina->designacao,
                'creditos' => $turma->disciplina->creditos,
                'carga_horaria' => $turma->disciplina->carga_horaria,
            ],
            'capacidade_maxima' => $turma->capacidade_maxima,
            'inscritos' => $turma->inscritosActivos(),
            'vagas' => $turma->vagasDisponiveis(),
        ];
    }

    private function inscricaoParaResposta(Inscricao $inscricao): array
    {
        return [
            'id' => $inscricao->id,
            'turma' => $this->turmaParaResposta($inscricao->turma),
            'data_inscricao' => optional($inscricao->data_inscricao)->toIso8601String(),
            'estado' => $inscricao->estado,
            'nota_frequencia' => $inscricao->nota_frequencia,
            'situacao_frequencia' => $inscricao->situacao_frequencia,
            'classificacao_final' => $inscricao->classificacao_final,
            'situacao_final' => $inscricao->situacao_final,
        ];
    }
}
