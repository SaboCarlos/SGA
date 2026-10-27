<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AnoLectivo;
use App\Models\Avaliacao;
use App\Models\Classificacao;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\ItemPlanoCurricular;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * "As minhas notas" (UC25 / US-22, lado do estudante): as disciplinas em que
 * o estudante autenticado esta inscrito, cada uma com as suas avaliacoes e a
 * nota que teve em cada. Nunca devolve notas de outros estudantes -- essas
 * estao na pauta, reservada a quem gere a turma.
 *
 * Uma nota so aparece depois de publicada: enquanto o docente a tem em
 * rascunho, o valor ainda pode mudar e nao e do conhecimento do estudante.
 */
class NotaController extends Controller
{
    private const ESTADOS_VISIVEIS = ['PUBLICADA', 'RECTIFICADA'];

    public function __invoke(Request $request)
    {
        $estudante = $request->user()->estudante;
        abort_unless($estudante, 403, 'Apenas estudantes têm notas para consultar.');

        $inscricoes = Inscricao::with([
            'turma.disciplina',
            'turma.docente',
            'turma.periodoLectivo.anoLectivo',
            'turma.avaliacoes',
            'classificacoes',
        ])
            ->where('estudante_id', $estudante->id)
            ->where('estado', '<>', 'ANULADA')
            ->get()
            ->sortByDesc(fn (Inscricao $i) => $i->turma->periodoLectivo->anoLectivo->designacao.'|'.$i->turma->periodoLectivo->semestre)
            ->values();

        // Ano curricular de cada disciplina, tal como esta no plano do estudante.
        $anoNoPlano = ItemPlanoCurricular::where('plano_id', $estudante->plano_id)
            ->pluck('ano_curricular', 'disciplina_id');

        $anoLectivoActual = AnoLectivo::where('estado', 'ACTIVO')->value('designacao');

        return response()->json([
            'estudante' => $estudante->nome_completo,
            'numero_estudante' => $estudante->numero_estudante,
            'curso' => $estudante->curso?->designacao,
            'ano_lectivo_actual' => $anoLectivoActual,
            'ano_curricular' => $this->anoCurricular($estudante, $inscricoes, $anoNoPlano, $anoLectivoActual),
            'disciplinas' => $inscricoes->map(fn (Inscricao $i) => [
                'inscricao_id' => $i->id,
                'ano_curricular' => $anoNoPlano[$i->turma->disciplina_id] ?? null,
                'codigo' => $i->turma->disciplina->codigo,
                'disciplina' => $i->turma->disciplina->designacao,
                'turma' => $i->turma->designacao,
                'docente' => $i->turma->docente?->nome_completo,
                'ano_lectivo' => $i->turma->periodoLectivo->anoLectivo->designacao,
                'semestre' => $i->turma->periodoLectivo->semestre,
                'nota_frequencia' => $i->nota_frequencia,
                'situacao_frequencia' => $i->situacao_frequencia,
                'classificacao_final' => $i->classificacao_final,
                'situacao_final' => $i->situacao_final,
                'avaliacoes' => $i->turma->avaliacoes
                    ->sortBy('data_prevista')
                    ->map(function (Avaliacao $a) use ($i) {
                        /** @var Classificacao|null $c */
                        $c = $i->classificacoes->firstWhere('avaliacao_id', $a->id);
                        $publicada = $c !== null && in_array($c->estado, self::ESTADOS_VISIVEIS, true);

                        return [
                            'id' => $a->id,
                            'designacao' => $a->designacao,
                            'tipo' => $a->tipo,
                            'peso' => $a->peso,
                            'data_prevista' => optional($a->data_prevista)->toDateString(),
                            'publicada' => $publicada,
                            'classificacao_id' => $publicada ? $c->id : null,
                            'valor' => $publicada ? $c->valor : null,
                            'faltou' => $publicada && $c->faltou,
                        ];
                    })->values(),
            ])->all(),
        ]);
    }

    /**
     * Ano em que o estudante esta: o maior ano curricular das disciplinas em
     * que esta inscrito no ano lectivo activo. Sem inscricoes nesse ano (por
     * exemplo, antes do periodo de inscricoes), estima-se pelo ano de
     * ingresso, limitado a duracao do curso.
     *
     * @param  Collection<int, Inscricao>  $inscricoes
     * @param  Collection<int, int>  $anoNoPlano
     */
    private function anoCurricular(Estudante $estudante, $inscricoes, $anoNoPlano, ?string $anoLectivoActual): ?int
    {
        $doAnoActual = $inscricoes
            ->filter(fn (Inscricao $i) => $i->turma->periodoLectivo->anoLectivo->designacao === $anoLectivoActual)
            ->map(fn (Inscricao $i) => $anoNoPlano[$i->turma->disciplina_id] ?? null)
            ->filter();

        if ($doAnoActual->isNotEmpty()) {
            return (int) $doAnoActual->max();
        }

        // Aceita "2026" e "2025/2026": conta o ultimo ano civil da designacao.
        if ($anoLectivoActual === null || ! preg_match_all('/\d{4}/', $anoLectivoActual, $anos) || ! $estudante->ano_ingresso) {
            return null;
        }

        $estimado = (int) end($anos[0]) - (int) $estudante->ano_ingresso + 1;
        $duracao = (int) ($estudante->curso->duracao_anos ?? $estimado);

        return max(1, min($estimado, $duracao));
    }
}
