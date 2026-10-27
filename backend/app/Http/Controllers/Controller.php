<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

abstract class Controller
{
    /**
     * Lista paginada, no mesmo formato de GET /estudantes:
     * {pagina, por_pagina, total, total_paginas, dados}.
     *
     * So pagina quando o pedido traz "pagina": sem ela devolve a lista
     * completa, que e o que os campos de escolha dos formularios precisam
     * (por exemplo, todos os docentes ao criar uma turma).
     *
     * @param  Collection<int, mixed>  $itens  ja convertidos para a resposta
     */
    protected function listar(Request $request, Collection $itens): JsonResponse
    {
        if (! $request->filled('pagina')) {
            return response()->json($itens->values()->all());
        }

        $porPagina = max(1, min(50, $request->integer('por_pagina', 10)));
        $total = $itens->count();
        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $pagina = max(1, min($totalPaginas, $request->integer('pagina', 1)));

        return response()->json([
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total' => $total,
            'total_paginas' => $totalPaginas,
            'dados' => $itens->values()->forPage($pagina, $porPagina)->values()->all(),
        ]);
    }
}
