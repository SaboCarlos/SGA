<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * UC29 (RF-55). Le a vista vw_aproveitamento_disciplina (Fase 2, migracao 4).
 *
 * app('db') em vez da fachada Illuminate\Support\Facades\DB: os controladores
 * nao podem depender dela directamente (tests/Arquitectura/CamadasTest.php).
 */
class RelatorioController extends Controller
{
    public function aproveitamentoPorDisciplina(Request $request)
    {
        $query = app('db')->table('vw_aproveitamento_disciplina');

        $query->when($request->filled('ano_lectivo'), fn ($q) => $q->where('ano_lectivo', $request->string('ano_lectivo')));
        $query->when($request->filled('semestre'), fn ($q) => $q->where('semestre', $request->integer('semestre')));

        $linhas = $query->get()->map(fn ($linha) => [
            'codigo' => $linha->codigo,
            'disciplina' => $linha->disciplina,
            'ano_lectivo' => $linha->ano_lectivo,
            'semestre' => $linha->semestre,
            'inscritos' => $linha->inscritos,
            'aprovados' => $linha->aprovados,
            'reprovados' => $linha->reprovados,
            'taxa_aprovacao' => $linha->taxa_aprovacao,
            'media_disciplina' => $linha->media_disciplina,
        ])->values()->all();

        return match ($request->query('formato')) {
            'pdf' => Pdf::loadView('relatorios.aproveitamento-disciplina', ['linhas' => $linhas])
                ->download('aproveitamento-por-disciplina.pdf'),
            'csv' => $this->comoCsv($linhas),
            default => response()->json($linhas),
        };
    }

    private function comoCsv(array $linhas): StreamedResponse
    {
        return response()->streamDownload(function () use ($linhas): void {
            $saida = fopen('php://output', 'w');
            fputcsv($saida, ['codigo', 'disciplina', 'ano_lectivo', 'semestre', 'inscritos', 'aprovados', 'reprovados', 'taxa_aprovacao', 'media_disciplina']);

            foreach ($linhas as $linha) {
                fputcsv($saida, $linha);
            }

            fclose($saida);
        }, 'aproveitamento-por-disciplina.csv', ['Content-Type' => 'text/csv']);
    }
}
