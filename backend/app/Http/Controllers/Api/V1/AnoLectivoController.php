<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AnoLectivo;
use App\Models\PeriodoLectivo;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** UC12 (US-12). */
class AnoLectivoController extends Controller
{
    public function index()
    {
        return response()->json(AnoLectivo::orderByDesc('designacao')->get()->map(fn (AnoLectivo $a) => $this->paraResposta($a))->all());
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'designacao' => ['required', 'string', 'max:15', 'unique:anos_lectivos,designacao'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after:data_inicio'],
        ]);

        $anoLectivo = AnoLectivo::create([...$dados, 'estado' => 'PLANEADO']);

        return response()->json($this->paraResposta($anoLectivo), 201);
    }

    public function show(AnoLectivo $anoLectivo)
    {
        return response()->json($this->paraResposta($anoLectivo));
    }

    /**
     * Activa o ano lectivo (transita de PLANEADO para ACTIVO). A base de
     * dados so admite um ACTIVO de cada vez (indice unico parcial, Fase 2);
     * aqui converte-se essa violacao numa resposta 409 legivel, em vez de a
     * deixar rebentar como erro 500.
     */
    public function activar(AnoLectivo $anoLectivo)
    {
        try {
            $anoLectivo->update(['estado' => 'ACTIVO']);
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                return response()->json([
                    'mensagem' => 'Já existe um ano lectivo activo. Encerre-o antes de activar outro (US-12).',
                ], 409);
            }

            throw $e;
        }

        return response()->json($this->paraResposta($anoLectivo->fresh()));
    }

    public function encerrar(AnoLectivo $anoLectivo)
    {
        $anoLectivo->update(['estado' => 'ENCERRADO']);

        return response()->json($this->paraResposta($anoLectivo->fresh()));
    }

    public function periodos(AnoLectivo $anoLectivo)
    {
        return response()->json($anoLectivo->periodos->map(fn (PeriodoLectivo $p) => $this->periodoParaResposta($p))->all());
    }

    public function criarPeriodo(Request $request, AnoLectivo $anoLectivo)
    {
        $dados = $request->validate([
            'semestre' => ['required', 'integer', 'in:1,2'],
            'inscricao_inicio' => ['required', 'date'],
            'inscricao_fim' => ['required', 'date', 'after:inscricao_inicio'],
            'notas_inicio' => ['required', 'date'],
            'notas_fim' => ['required', 'date', 'after:notas_inicio'],
        ]);

        if ($anoLectivo->periodos()->where('semestre', $dados['semestre'])->exists()) {
            throw ValidationException::withMessages([
                'semestre' => ['Este ano lectivo já tem um período definido para este semestre.'],
            ]);
        }

        $periodo = $anoLectivo->periodos()->create($dados);

        return response()->json($this->periodoParaResposta($periodo), 201);
    }

    private function paraResposta(AnoLectivo $anoLectivo): array
    {
        return [
            'id' => $anoLectivo->id,
            'designacao' => $anoLectivo->designacao,
            'data_inicio' => optional($anoLectivo->data_inicio)->toDateString(),
            'data_fim' => optional($anoLectivo->data_fim)->toDateString(),
            'estado' => $anoLectivo->estado,
        ];
    }

    private function periodoParaResposta(PeriodoLectivo $periodo): array
    {
        return [
            'id' => $periodo->id,
            'semestre' => $periodo->semestre,
            'inscricao_inicio' => optional($periodo->inscricao_inicio)->toDateString(),
            'inscricao_fim' => optional($periodo->inscricao_fim)->toDateString(),
            'notas_inicio' => optional($periodo->notas_inicio)->toDateString(),
            'notas_fim' => optional($periodo->notas_fim)->toDateString(),
            'inscricoes_abertas' => $periodo->inscricoesAbertas(),
        ];
    }
}
