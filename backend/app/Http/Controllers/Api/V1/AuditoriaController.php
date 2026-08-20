<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RegistoAuditoria;
use Illuminate\Http\Request;

/** UC31. Recurso apenas de leitura (RNF-32). */
class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()->temPerfil('ADMINISTRADOR', 'COORDENADOR'),
            403,
            'Sem permissão para consultar a auditoria.'
        );

        $query = RegistoAuditoria::query()->with('utilizador')->orderByDesc('criado_em');

        $query->when($request->filled('utilizador_id'), fn ($q) => $q->where('utilizador_id', $request->string('utilizador_id')));
        $query->when($request->filled('entidade'), fn ($q) => $q->where('entidade', $request->string('entidade')));
        $query->when($request->filled('acao'), fn ($q) => $q->where('acao', $request->string('acao')));
        $query->when($request->filled('de'), fn ($q) => $q->whereDate('criado_em', '>=', $request->date('de')));
        $query->when($request->filled('ate'), fn ($q) => $q->whereDate('criado_em', '<=', $request->date('ate')));

        $porPagina = min(50, $request->integer('por_pagina', 25));

        return response()->json(
            $query->paginate($porPagina, page: $request->integer('pagina', 1))
                ->getCollection()
                ->map(fn (RegistoAuditoria $r) => [
                    'id' => $r->id,
                    'utilizador' => $r->utilizador?->nome,
                    'acao' => $r->acao,
                    'entidade' => $r->entidade,
                    'entidade_id' => $r->entidade_id,
                    'valor_anterior' => $r->valor_anterior,
                    'valor_novo' => $r->valor_novo,
                    'justificacao' => $r->justificacao,
                    'criado_em' => optional($r->criado_em)->toIso8601String(),
                ])
        );
    }

    public function metodoNaoPermitido()
    {
        return response()->json([
            'mensagem' => 'Os registos de auditoria são apenas de leitura.',
        ], 405);
    }
}
