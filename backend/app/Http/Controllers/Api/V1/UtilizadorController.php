<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UtilizadorResource;
use App\Models\Perfil;
use App\Models\Utilizador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** UC02, UC03 (US-02 gestão de contas, US-03 atribuição de perfis). */
class UtilizadorController extends Controller
{
    public function index(Request $request)
    {
        $this->autorizarAdministrador($request);

        return UtilizadorResource::collection(Utilizador::with('perfis.permissoes')->get());
    }

    public function store(Request $request)
    {
        $this->autorizarAdministrador($request);

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:utilizadores,email'],
            'palavra_passe' => ['required', 'string', 'min:8'],
            'perfis' => ['sometimes', 'array'],
            'perfis.*' => ['string', 'exists:perfis,nome'],
        ]);

        $utilizador = Utilizador::create([
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'palavra_passe' => Hash::make($dados['palavra_passe']),
            'activo' => true,
        ]);

        $ids = Perfil::whereIn('nome', $dados['perfis'] ?? [])->pluck('id');
        $utilizador->perfis()->sync($ids);

        return (new UtilizadorResource($utilizador->fresh('perfis.permissoes')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Utilizador $utilizador)
    {
        $this->autorizarAdministrador($request);

        return new UtilizadorResource($utilizador->load('perfis.permissoes'));
    }

    public function update(Request $request, Utilizador $utilizador)
    {
        $this->autorizarAdministrador($request);

        $dados = $request->validate([
            'nome' => ['sometimes', 'string', 'max:150'],
            'activo' => ['sometimes', 'boolean'],
            'perfis' => ['sometimes', 'array'],
            'perfis.*' => ['string', 'exists:perfis,nome'],
        ]);

        $utilizador->update(collect($dados)->except('perfis')->all());

        if (array_key_exists('perfis', $dados)) {
            $utilizador->perfis()->sync(Perfil::whereIn('nome', $dados['perfis'])->pluck('id'));
        }

        return new UtilizadorResource($utilizador->fresh('perfis.permissoes'));
    }

    public function destroy(Request $request, Utilizador $utilizador)
    {
        $this->autorizarAdministrador($request);

        $utilizador->update(['activo' => false]);
        $utilizador->tokens()->delete();

        return response()->noContent();
    }

    private function autorizarAdministrador(Request $request): void
    {
        abort_unless($request->user()->temPerfil('ADMINISTRADOR'), 403, 'Apenas o Administrador gere utilizadores.');
    }
}
