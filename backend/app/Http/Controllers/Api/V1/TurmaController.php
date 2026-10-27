<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Turma;
use Illuminate\Http\Request;

/** UC12, UC13 (US-11 turmas, US-12 ano lectivo). */
class TurmaController extends Controller
{
    public function index(Request $request)
    {
        $query = Turma::query()->with(['disciplina', 'docente']);
        $utilizador = $request->user();

        // A direccao ve a oferta toda; o docente so as turmas que lecciona; o
        // estudante so aquelas em que esta inscrito (as que pode escolher vem
        // de GET /inscricoes/disponiveis).
        if (! $utilizador->ehDireccao()) {
            if ($utilizador->docente) {
                $query->where('docente_id', $utilizador->docente->id);
            } elseif ($utilizador->estudante) {
                $query->whereHas('inscricoes', fn ($q) => $q
                    ->where('estudante_id', $utilizador->estudante->id)
                    ->where('estado', '<>', 'ANULADA'));
            } else {
                abort(403, 'Sem permissão para consultar turmas.');
            }
        }

        $query->when($request->filled('periodo_lectivo_id'), fn ($q) => $q->where('periodo_lectivo_id', $request->integer('periodo_lectivo_id')));
        $query->when($request->filled('docente_id'), fn ($q) => $q->where('docente_id', $request->string('docente_id')));
        $query->when($request->filled('disciplina_id'), fn ($q) => $q->where('disciplina_id', $request->integer('disciplina_id')));

        $turmas = $query->orderBy('id')->get();

        if ($request->boolean('com_vagas')) {
            $turmas = $turmas->filter(fn (Turma $t) => $t->vagasDisponiveis() > 0)->values();
        }

        return $this->listar($request, $turmas->map(fn (Turma $t) => $this->paraResposta($t)));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'disciplina_id' => ['required', 'integer', 'exists:disciplinas,id'],
            'periodo_lectivo_id' => ['required', 'integer', 'exists:periodos_lectivos,id'],
            'docente_id' => ['required', 'uuid', 'exists:docentes,id'],
            'designacao' => ['sometimes', 'string', 'max:20'],
            'capacidade_maxima' => ['required', 'integer', 'min:1'],
        ]);

        $turma = Turma::create(['designacao' => 'A', ...$dados]);

        return response()->json($this->paraResposta($turma->load(['disciplina', 'docente'])), 201);
    }

    public function show(Request $request, Turma $turma)
    {
        $utilizador = $request->user();
        $estaInscrito = $utilizador->estudante
            && $turma->inscricoes()->where('estudante_id', $utilizador->estudante->id)->exists();
        abort_unless($utilizador->gereTurma($turma) || $estaInscrito, 403, 'Sem acesso a esta turma.');

        return response()->json($this->paraResposta($turma->load(['disciplina', 'docente'])));
    }

    public function inscritos(Request $request, Turma $turma)
    {
        abort_unless($request->user()->gereTurma($turma), 403, 'Sem acesso à lista de inscritos desta turma.');

        return response()->json(
            $turma->inscricoes()
                ->where('estado', 'ACTIVA')
                ->with('estudante')
                ->get()
                ->sortBy('estudante.numero_estudante')
                ->map(fn ($i) => [
                    'inscricao_id' => $i->id,
                    'numero_estudante' => $i->estudante->numero_estudante,
                    'nome_completo' => $i->estudante->nome_completo,
                    'nota_frequencia' => $i->nota_frequencia,
                    'situacao_frequencia' => $i->situacao_frequencia,
                ])
                ->values()
        );
    }

    private function paraResposta(Turma $turma): array
    {
        return [
            'id' => $turma->id,
            'designacao' => $turma->designacao,
            'disciplina' => [
                'id' => $turma->disciplina->id,
                'codigo' => $turma->disciplina->codigo,
                'designacao' => $turma->disciplina->designacao,
            ],
            'docente' => $turma->docente?->nome_completo,
            'capacidade_maxima' => $turma->capacidade_maxima,
            'inscritos' => $turma->inscritosActivos(),
            'vagas' => $turma->vagasDisponiveis(),
        ];
    }
}
