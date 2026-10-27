<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Avaliacao;
use App\Models\Pauta;
use App\Models\Turma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/** UC26, UC27 (emissão e homologação de pautas). */
class PautaController extends Controller
{
    public function show(Request $request, Turma $turma)
    {
        abort_unless($request->user()->gereTurma($turma), 403, 'A pauta só pode ser consultada pelo docente da turma, pela coordenação ou pela administração.');

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

    public function homologar(Request $request, Pauta $pauta)
    {
        abort_unless($request->user()->gereTurma($pauta->turma), 403, 'Sem permissão para homologar esta pauta.');

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

        $pauta->update(['estado' => 'HOMOLOGADA', 'homologada_por' => $request->user()->id, 'homologada_em' => now()]);

        return response()->json(['mensagem' => 'Pauta homologada e marcada como definitiva.']);
    }

    private function dadosDaPauta(Turma $turma): array
    {
        $inscricoes = $turma->inscricoes()->where('estado', '<>', 'ANULADA')->with(['estudante', 'turma.disciplina', 'classificacoes'])->get();
        $avaliacoes = $turma->avaliacoes()->orderBy('id')->get();
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
            'id' => $pauta->id,
            'numero' => $pauta->numero,
            'versao' => $pauta->versao,
            'estado' => $pauta->estado,
            'turma' => ['id' => $turma->id, 'designacao' => $turma->designacao, 'disciplina' => $turma->disciplina->designacao],
            'emitida_em' => optional($pauta->emitida_em)->toIso8601String(),
            'total_inscritos' => $inscricoes->count(),
            'total_aprovados' => $aprovados,
            'taxa_aprovacao' => $inscricoes->count() > 0 ? round(100 * $aprovados / $inscricoes->count(), 1) : 0.0,
            // Colunas da pauta: uma por avaliacao, pela ordem em que foram definidas.
            'avaliacoes' => $avaliacoes->map(fn (Avaliacao $a) => [
                'id' => $a->id,
                'designacao' => $a->designacao,
                'tipo' => $a->tipo,
                'peso' => $a->peso,
                'estado' => $a->estado,
            ])->values()->all(),
            'linhas' => $inscricoes->sortBy('estudante.numero_estudante')->map(fn ($i) => [
                'inscricao_id' => $i->id,
                'numero_estudante' => $i->estudante->numero_estudante,
                'nome_completo' => $i->estudante->nome_completo,
                // As notas tal como o docente as lancou, por avaliacao.
                // "publicada" distingue o que os alunos ja veem do rascunho.
                'notas' => $avaliacoes->mapWithKeys(function (Avaliacao $a) use ($i) {
                    $c = $i->classificacoes->firstWhere('avaliacao_id', $a->id);

                    return [$a->id => [
                        'valor' => $c?->valor,
                        'faltou' => (bool) $c?->faltou,
                        'publicada' => $c !== null && in_array($c->estado, ['PUBLICADA', 'RECTIFICADA'], true),
                    ]];
                })->all(),
                'nota_frequencia' => $i->nota_frequencia,
                'situacao_frequencia' => $i->situacao_frequencia,
                'nota_exame' => $i->nota_exame,
                'classificacao_final' => $i->classificacao_final,
                'situacao_final' => $i->situacao_final,
            ])->values()->all(),
        ];
    }
}
