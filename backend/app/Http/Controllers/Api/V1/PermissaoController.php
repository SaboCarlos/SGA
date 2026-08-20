<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Permissao;
use Illuminate\Http\Request;

/** Catálogo de permissões (US-03). Só o Administrador gere. */
class PermissaoController extends Controller
{
    public function index(Request $request)
    {
        $this->autorizarAdministrador($request);

        return response()->json(Permissao::orderBy('codigo')->get()->map(fn (Permissao $p) => $this->paraResposta($p))->all());
    }

    public function store(Request $request)
    {
        $this->autorizarAdministrador($request);

        $dados = $request->validate([
            'codigo' => ['required', 'string', 'max:60', 'unique:permissoes,codigo'],
            'descricao' => ['nullable', 'string'],
        ]);

        $permissao = Permissao::create($dados);

        return response()->json($this->paraResposta($permissao), 201);
    }

    private function autorizarAdministrador(Request $request): void
    {
        abort_unless($request->user()->temPerfil('ADMINISTRADOR'), 403, 'Apenas o Administrador gere permissões.');
    }

    private function paraResposta(Permissao $permissao): array
    {
        return [
            'id' => $permissao->id,
            'codigo' => $permissao->codigo,
            'descricao' => $permissao->descricao,
        ];
    }
}
