<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Disciplina;
use Illuminate\Http\Request;

/** UC10, UC11 (US-08 disciplinas, US-10 pré-requisitos). */
class DisciplinaController extends Controller
{
    public function index()
    {
        return response()->json(Disciplina::where('activo', true)->get()->map(fn (Disciplina $d) => $this->paraResposta($d))->all());
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'codigo' => ['required', 'string', 'unique:disciplinas,codigo'],
            'designacao' => ['required', 'string', 'max:150'],
            'creditos' => ['required', 'integer', 'min:1'],
            'carga_horaria' => ['required', 'integer', 'min:1'],
        ]);

        $disciplina = Disciplina::create([...$dados, 'activo' => true]);

        return response()->json($this->paraResposta($disciplina), 201);
    }

    public function show(Disciplina $disciplina)
    {
        return response()->json($this->paraResposta($disciplina));
    }

    public function update(Request $request, Disciplina $disciplina)
    {
        $dados = $request->validate([
            'designacao' => ['sometimes', 'string', 'max:150'],
            'creditos' => ['sometimes', 'integer', 'min:1'],
            'carga_horaria' => ['sometimes', 'integer', 'min:1'],
        ]);

        $disciplina->update($dados);

        return response()->json($this->paraResposta($disciplina));
    }

    public function preRequisitos(Disciplina $disciplina)
    {
        return response()->json($disciplina->preRequisitos->map(fn (Disciplina $d) => $this->paraResposta($d))->all());
    }

    public function definirPreRequisito(Request $request, Disciplina $disciplina)
    {
        $dados = $request->validate([
            'pre_requisito_id' => ['required', 'integer', 'exists:disciplinas,id'],
        ]);

        if ($dados['pre_requisito_id'] === $disciplina->id || $this->criaCiclo($disciplina->id, $dados['pre_requisito_id'])) {
            return response()->json([
                'codigo' => 'US-10',
                'mensagem' => 'Dependência circular detectada entre as disciplinas.',
            ], 422);
        }

        $disciplina->preRequisitos()->syncWithoutDetaching([$dados['pre_requisito_id']]);

        return response()->json(status: 201);
    }

    /** Verifica se acrescentar $novoPreRequisito como pre-requisito de $disciplinaId fecharia um ciclo. */
    private function criaCiclo(int $disciplinaId, int $novoPreRequisito): bool
    {
        $visitados = [];
        $pilha = [$novoPreRequisito];

        while ($pilha !== []) {
            $actual = array_pop($pilha);

            if ($actual === $disciplinaId) {
                return true;
            }

            if (in_array($actual, $visitados, true)) {
                continue;
            }
            $visitados[] = $actual;

            $pilha = [...$pilha, ...Disciplina::find($actual)?->preRequisitos->pluck('id')->all() ?? []];
        }

        return false;
    }

    private function paraResposta(Disciplina $disciplina): array
    {
        return [
            'id' => $disciplina->id,
            'codigo' => $disciplina->codigo,
            'designacao' => $disciplina->designacao,
            'creditos' => $disciplina->creditos,
            'carga_horaria' => $disciplina->carga_horaria,
        ];
    }
}
