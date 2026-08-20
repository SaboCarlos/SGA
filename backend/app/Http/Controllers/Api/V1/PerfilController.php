<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Perfil;
use App\Models\Permissao;
use Illuminate\Http\Request;

/**
 * Gestão de perfis e das suas permissões (US-03).
 *
 * Distinto de UtilizadorController::update, que atribui perfis JÁ
 * EXISTENTES a um utilizador — este controlador gere o catálogo em si:
 * criar perfis, e ligar/desligar permissões a cada um.
 */
class PerfilController extends Controller
{
    public function index(Request $request)
    {
        $this->autorizarAdministrador($request);

        return response()->json(Perfil::with('permissoes')->get()->map(fn (Perfil $p) => $this->paraResposta($p))->all());
    }

    public function store(Request $request)
    {
        $this->autorizarAdministrador($request);

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:30', 'unique:perfis,nome'],
            'descricao' => ['nullable', 'string'],
        ]);

        $perfil = Perfil::create($dados);

        return response()->json($this->paraResposta($perfil->load('permissoes')), 201);
    }

    public function update(Request $request, Perfil $perfil)
    {
        $this->autorizarAdministrador($request);

        $dados = $request->validate([
            'descricao' => ['nullable', 'string'],
        ]);

        $perfil->update($dados);

        return response()->json($this->paraResposta($perfil->fresh('permissoes')));
    }

    public function atribuirPermissao(Request $request, Perfil $perfil)
    {
        $this->autorizarAdministrador($request);

        $dados = $request->validate([
            'permissao_id' => ['required', 'integer', 'exists:permissoes,id'],
        ]);

        $perfil->permissoes()->syncWithoutDetaching([$dados['permissao_id']]);

        return response()->json($this->paraResposta($perfil->fresh('permissoes')), 201);
    }

    public function retirarPermissao(Request $request, Perfil $perfil, Permissao $permissao)
    {
        $this->autorizarAdministrador($request);

        $perfil->permissoes()->detach($permissao->id);

        return response()->noContent();
    }

    private function autorizarAdministrador(Request $request): void
    {
        abort_unless($request->user()->temPerfil('ADMINISTRADOR'), 403, 'Apenas o Administrador gere perfis e permissões.');
    }

    private function paraResposta(Perfil $perfil): array
    {
        return [
            'id' => $perfil->id,
            'nome' => $perfil->nome,
            'descricao' => $perfil->descricao,
            'permissoes' => $perfil->permissoes->map(fn (Permissao $p) => ['id' => $p->id, 'codigo' => $p->codigo])->values(),
        ];
    }
}
