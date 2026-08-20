<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pauta;
use App\Models\Turma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/** UC26, UC27 (emissão e homologação de pautas). */
class PautaController extends Controller
{
    public function show(Request $request, Turma $turma)
    {
        $dados = $this->dadosDaPauta($turma);

        if ($request->query('formato') === 'pdf') {
            $pdf = Pdf::loadView('pautas.pdf', ['pauta' => $dados]);
            // O numero da pauta (ex.: PAUTA/2026/0042) tem "/", invalido num
            // nome de ficheiro / cabecalho Content-Disposition.
            $ficheiro = str_replace('/', '-', $dados['numero']).'.pdf';

            return $pdf->download($ficheiro);
        }

        return response()->json($dados);
    }

    public function homologar(Pauta $pauta)
    {
        $incompletos = $pauta->turma->inscricoes()
            ->where('estado', 'ACTIVA')
            ->where('situacao_final', 'EM_CURSO')
            ->whereNull('classificacao_final')
            ->count();

        if ($incompletos > 0) {
            return response()->json([
                'mensagem' => "Existem {$incompletos} estudantes sem classificação nem marca de falta.",
            ], 409);
        }

        $pauta->update(['estado' => 'HOMOLOGADA', 'homologada_por' => request()->user()->id, 'homologada_em' => now()]);

        return response()->json(['mensagem' => 'Pauta homologada e marcada como definitiva.']);
    }

    private function dadosDaPauta(Turma $turma): array
    {
        $inscricoes = $turma->inscricoes()->where('estado', '<>', 'ANULADA')->with(['estudante', 'turma.disciplina'])->get();
        $aprovados = $inscricoes->where('situacao_final', 'APROVADO')->count();

        $pauta = Pauta::firstOrCreate(
            ['turma_id' => $turma->id, 'versao' => 1],
            [
                'numero' => 'PAUTA/'.now()->year.'/'.str_pad((string) (Pauta::count() + 1), 4, '0', STR_PAD_LEFT),
                'total_inscritos' => $inscricoes->count(),
                'total_aprovados' => $aprovados,
                'estado' => 'PROVISORIA',
            ]
        );

        return [
            'numero' => $pauta->numero,
            'versao' => $pauta->versao,
            'estado' => $pauta->estado,
            'turma' => ['id' => $turma->id, 'designacao' => $turma->designacao, 'disciplina' => $turma->disciplina->designacao],
            'emitida_em' => optional($pauta->emitida_em)->toIso8601String(),
            'total_inscritos' => $inscricoes->count(),
            'total_aprovados' => $aprovados,
            'taxa_aprovacao' => $inscricoes->count() > 0 ? round(100 * $aprovados / $inscricoes->count(), 1) : 0.0,
            'linhas' => $inscricoes->sortBy('estudante.numero_estudante')->map(fn ($i) => [
                'inscricao_id' => $i->id,
                'numero_estudante' => $i->estudante->numero_estudante,
                'nome_completo' => $i->estudante->nome_completo,
                'nota_frequencia' => $i->nota_frequencia,
                'situacao_frequencia' => $i->situacao_frequencia,
            ])->values()->all(),
        ];
    }
}
