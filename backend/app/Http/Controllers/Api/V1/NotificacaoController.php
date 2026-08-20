<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notificacao;
use Illuminate\Http\Request;

/** RF-67. */
class NotificacaoController extends Controller
{
    public function index(Request $request)
    {
        $query = Notificacao::where('utilizador_id', $request->user()->id)->orderByDesc('criada_em');

        if ($request->has('nao_lidas') && $request->boolean('nao_lidas')) {
            $query->whereNull('lida_em');
        }

        return response()->json($query->get()->map(fn (Notificacao $n) => [
            'id' => $n->id,
            'tipo' => $n->tipo,
            'titulo' => $n->titulo,
            'mensagem' => $n->mensagem,
            'criada_em' => optional($n->criada_em)->toIso8601String(),
            'lida_em' => optional($n->lida_em)->toIso8601String(),
        ]));
    }

    public function marcarLida(Request $request, Notificacao $notificacao)
    {
        abort_unless($notificacao->utilizador_id === $request->user()->id, 403);

        $notificacao->update(['lida_em' => now()]);

        return response()->noContent();
    }
}
