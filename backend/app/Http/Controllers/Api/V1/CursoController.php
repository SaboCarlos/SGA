<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\ItemPlanoCurricular;
use Illuminate\Http\Request;

/** UC08, UC09 (US-08, US-09). */
class CursoController extends Controller
{
    public function index()
    {
        return response()->json(Curso::where('activo', true)->get()->map(fn (Curso $c) => $this->paraResposta($c))->all());
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'codigo' => ['required', 'string', 'unique:cursos,codigo'],
            'designacao' => ['required', 'string', 'max:150'],
            'grau' => ['required', 'string'],
            'duracao_anos' => ['required', 'integer', 'min:1'],
            'total_creditos' => ['required', 'integer', 'min:1'],
            'regulamento_avaliacao' => ['sometimes', 'string'],
            'coordenador_id' => ['nullable', 'uuid', 'exists:docentes,id'],
        ]);

        $curso = Curso::create([...$dados, 'activo' => true]);

        return response()->json($this->paraResposta($curso), 201);
    }

    public function show(Curso $curso)
    {
        return response()->json($this->paraResposta($curso));
    }

    public function update(Request $request, Curso $curso)
    {
        $dados = $request->validate([
            'designacao' => ['sometimes', 'string', 'max:150'],
            'grau' => ['sometimes', 'string'],
            'duracao_anos' => ['sometimes', 'integer', 'min:1'],
            'total_creditos' => ['sometimes', 'integer', 'min:1'],
            'regulamento_avaliacao' => ['sometimes', 'string'],
            'coordenador_id' => ['nullable', 'uuid', 'exists:docentes,id'],
        ]);

        $curso->update($dados);

        return response()->json($this->paraResposta($curso));
    }

    public function planoCurricular(Curso $curso)
    {
        $plano = $curso->planoActivo();
        abort_if(! $plano, 404, 'O curso não tem um plano curricular activo.');

        return response()->json(
            $plano->itens()->with('disciplina')->get()->map(fn (ItemPlanoCurricular $i) => [
                'id' => $i->id,
                'disciplina_id' => $i->disciplina_id,
                'disciplina' => $i->disciplina->designacao,
                'ano_curricular' => $i->ano_curricular,
                'semestre' => $i->semestre,
                'tipo' => $i->tipo,
            ])->all()
        );
    }

    public function acrescentarAoPlano(Request $request, Curso $curso)
    {
        $dados = $request->validate([
            'disciplina_id' => ['required', 'integer', 'exists:disciplinas,id'],
            'ano_curricular' => ['required', 'integer', 'min:1'],
            'semestre' => ['required', 'integer', 'in:1,2'],
            'tipo' => ['sometimes', 'in:OBRIGATORIA,OPCIONAL'],
        ]);

        $plano = $curso->planoActivo();
        abort_if(! $plano, 404, 'O curso não tem um plano curricular activo.');

        if ($dados['ano_curricular'] > $curso->duracao_anos) {
            return response()->json([
                'mensagem' => 'O ano curricular indicado excede a duração do curso.',
            ], 422);
        }

        if ($plano->itens()->where('disciplina_id', $dados['disciplina_id'])->exists()) {
            return response()->json(['mensagem' => 'A disciplina já consta do plano curricular.'], 422);
        }

        $item = $plano->itens()->create([...$dados, 'plano_id' => $plano->id]);

        return response()->json(['id' => $item->id], 201);
    }

    private function paraResposta(Curso $curso): array
    {
        return [
            'id' => $curso->id,
            'codigo' => $curso->codigo,
            'designacao' => $curso->designacao,
            'grau' => $curso->grau,
            'duracao_anos' => $curso->duracao_anos,
            'total_creditos' => $curso->total_creditos,
            'regulamento_avaliacao' => $curso->regulamento_avaliacao,
        ];
    }
}
