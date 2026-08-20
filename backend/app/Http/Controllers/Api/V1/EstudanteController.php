<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\Perfil;
use App\Models\Utilizador;
use App\Servicos\ServicoAvaliacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** UC05, UC06, UC25 (US-05, US-06, US-22). */
class EstudanteController extends Controller
{
    public function __construct(private readonly ServicoAvaliacao $servicoAvaliacao) {}

    public function index(Request $request)
    {
        $query = Estudante::query()->with('curso');

        if ($request->filled('pesquisa')) {
            $termo = mb_strtolower((string) $request->string('pesquisa'));
            $query->where(function ($q) use ($termo) {
                $q->whereRaw('lower(nome_completo) LIKE ?', ["%{$termo}%"])
                    ->orWhere('numero_estudante', 'LIKE', "%{$termo}%");
            });
        }

        $query->when($request->filled('curso_id'), fn ($q) => $q->where('curso_id', $request->integer('curso_id')));
        $query->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')));

        $porPagina = min(50, $request->integer('por_pagina', 25));
        $pagina = $query->paginate($porPagina, page: $request->integer('pagina', 1));

        return response()->json([
            'pagina' => $pagina->currentPage(),
            'por_pagina' => $pagina->perPage(),
            'total' => $pagina->total(),
            'total_paginas' => $pagina->lastPage(),
            'dados' => $pagina->getCollection()->map(fn (Estudante $e) => $this->estudanteParaResposta($e))->all(),
        ]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome_completo' => ['required', 'string', 'max:150'],
            'documento_identificacao' => ['required', 'string', 'unique:estudantes,documento_identificacao'],
            'data_nascimento' => ['required', 'date', 'before:today'],
            'sexo' => ['nullable', 'in:MASCULINO,FEMININO'],
            'telefone' => ['nullable', 'string'],
            'email' => ['required', 'email', 'unique:utilizadores,email'],
            'curso_id' => ['required', 'integer', 'exists:cursos,id'],
            'ano_ingresso' => ['required', 'integer'],
        ]);

        $estudante = app('db')->transaction(function () use ($dados) {
            $utilizador = Utilizador::create([
                'nome' => $dados['nome_completo'],
                'email' => $dados['email'],
                'palavra_passe' => Hash::make(Str::random(16)),
            ]);

            $perfil = Perfil::firstOrCreate(['nome' => 'ESTUDANTE']);
            $utilizador->perfis()->attach($perfil->id);

            $numero = $dados['ano_ingresso'].'/'.str_pad(
                (string) (Estudante::where('ano_ingresso', $dados['ano_ingresso'])->count() + 1),
                4,
                '0',
                STR_PAD_LEFT
            );

            $curso = Curso::find($dados['curso_id']);

            return Estudante::create([
                ...$dados,
                'utilizador_id' => $utilizador->id,
                'numero_estudante' => $numero,
                'plano_id' => $curso?->planoActivo()?->id,
                'estado' => 'ACTIVO',
            ]);
        });

        return response()->json($this->estudanteParaResposta($estudante->fresh('curso')), 201);
    }

    public function show(Estudante $estudante)
    {
        return response()->json($this->estudanteParaResposta($estudante->load('curso')));
    }

    public function update(Request $request, Estudante $estudante)
    {
        $dados = $request->validate([
            'nome_completo' => ['sometimes', 'string', 'max:150'],
            'documento_identificacao' => ['sometimes', 'string', 'unique:estudantes,documento_identificacao,'.$estudante->id],
            'data_nascimento' => ['sometimes', 'date', 'before:today'],
            'sexo' => ['nullable', 'in:MASCULINO,FEMININO'],
            'telefone' => ['nullable', 'string'],
            'curso_id' => ['sometimes', 'integer', 'exists:cursos,id'],
            'ano_ingresso' => ['sometimes', 'integer'],
        ]);

        $estudante->update($dados);

        return response()->json($this->estudanteParaResposta($estudante->fresh('curso')));
    }

    public function destroy(Estudante $estudante)
    {
        if ($estudante->inscricoes()->exists()) {
            return response()->json([
                'mensagem' => 'Estudante com histórico académico não pode ser eliminado fisicamente.',
            ], 409);
        }

        $estudante->update(['estado' => 'DESISTENTE']);

        return response()->noContent();
    }

    public function aproveitamento(Request $request, Estudante $estudante)
    {
        $utilizador = $request->user();

        $podeVer = $utilizador->temPerfil('ADMINISTRADOR')
            || ($utilizador->estudante && $utilizador->estudante->id === $estudante->id)
            || ($utilizador->docente && $estudante->curso->coordenador_id === $utilizador->docente->id);

        abort_unless($podeVer, 403, 'Sem permissão para consultar o aproveitamento deste estudante.');

        ['media_ponderada' => $mediaPonderada, 'creditos_obtidos' => $creditosObtidos] =
            $this->servicoAvaliacao->calcularMediaCurso($estudante->id, $estudante->curso->regulamento_avaliacao);

        $inscricoesQuery = Inscricao::with(['turma.disciplina', 'turma.periodoLectivo.anoLectivo'])
            ->where('estudante_id', $estudante->id)
            ->where('estado', '<>', 'ANULADA');

        if ($request->filled('ano_lectivo')) {
            $inscricoesQuery->whereHas(
                'turma.periodoLectivo.anoLectivo',
                fn ($q) => $q->where('designacao', $request->string('ano_lectivo'))
            );
        }

        $porAno = $inscricoesQuery->get()->groupBy(fn (Inscricao $i) => $i->turma->periodoLectivo->anoLectivo->designacao.'|'.$i->turma->periodoLectivo->semestre);

        return response()->json([
            'estudante' => $estudante->nome_completo,
            'numero_estudante' => $estudante->numero_estudante,
            'curso' => $estudante->curso->designacao,
            'media_ponderada' => $mediaPonderada,
            'creditos_obtidos' => $creditosObtidos,
            'creditos_curso' => $estudante->curso->total_creditos,
            'percentagem_conclusao' => $estudante->curso->total_creditos > 0
                ? round(100 * $creditosObtidos / $estudante->curso->total_creditos, 1)
                : 0.0,
            'anos_lectivos' => $porAno->map(function ($grupo, $chave) {
                [$ano, $semestre] = explode('|', $chave);

                return [
                    'ano_lectivo' => $ano,
                    'semestre' => (int) $semestre,
                    'disciplinas' => $grupo->map(fn (Inscricao $i) => [
                        'id' => $i->id,
                        'disciplina' => $i->turma->disciplina->designacao,
                        'codigo' => $i->turma->disciplina->codigo,
                        'nota_frequencia' => $i->nota_frequencia,
                        'situacao_frequencia' => $i->situacao_frequencia,
                        'classificacao_final' => $i->classificacao_final,
                        'situacao_final' => $i->situacao_final,
                    ])->values(),
                ];
            })->values(),
        ]);
    }

    private function estudanteParaResposta(Estudante $estudante): array
    {
        return [
            'id' => $estudante->id,
            'numero_estudante' => $estudante->numero_estudante,
            'nome_completo' => $estudante->nome_completo,
            'curso' => $estudante->curso ? [
                'id' => $estudante->curso->id,
                'codigo' => $estudante->curso->codigo,
                'designacao' => $estudante->curso->designacao,
            ] : null,
            'ano_ingresso' => $estudante->ano_ingresso,
            'estado' => $estudante->estado,
        ];
    }
}
