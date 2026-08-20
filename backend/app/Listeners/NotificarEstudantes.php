<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\NotasPublicadas;
use App\Models\Notificacao;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Ouvinte assincrono: a notificacao corre na fila (contentor sga-worker).
 *
 * A notificacao identifica a disciplina e a avaliacao, mas nunca inclui a
 * classificacao (US-29).
 */
final class NotificarEstudantes implements ShouldQueue
{
    public function handle(NotasPublicadas $evento): void
    {
        $turma = $evento->avaliacao->turma;

        foreach ($turma->inscricoes as $inscricao) {
            Notificacao::firstOrCreate(
                [
                    'utilizador_id' => $inscricao->estudante->utilizador_id,
                    'tipo' => 'notas.publicadas',
                    'referencia' => "avaliacao:{$evento->avaliacao->id}",
                ],
                [
                    'titulo' => "Notas publicadas: {$turma->disciplina->designacao}",
                    'mensagem' => "As classificações de {$evento->avaliacao->designacao} já estão disponíveis.",
                ]
            );
        }
    }
}
