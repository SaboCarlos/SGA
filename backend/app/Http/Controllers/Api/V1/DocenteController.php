<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Perfil;
use App\Models\Utilizador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** UC07 (US-07). */
class DocenteController extends Controller
{
    public function index(Request $request)
    {
        return $this->listar($request, Docente::where('activo', true)->orderBy('nome_completo')->get()->map(
            fn (Docente $d) => $this->paraResposta($d)
        ));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome_completo' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:utilizadores,email'],
            'codigo' => ['required', 'string', 'unique:docentes,codigo'],
            'grau_academico' => ['nullable', 'string'],
            'categoria' => ['nullable', 'string'],
            'area_cientifica' => ['nullable', 'string'],
            'telefone' => ['nullable', 'string'],
        ]);

        $docente = app('db')->transaction(function () use ($dados) {
            $utilizador = Utilizador::create([
                'nome' => $dados['nome_completo'],
                'email' => $dados['email'],
                'palavra_passe' => Hash::make(Str::random(16)),
            ]);
            $utilizador->perfis()->attach(Perfil::firstOrCreate(['nome' => 'DOCENTE'])->id);

            return Docente::create([...$dados, 'utilizador_id' => $utilizador->id, 'activo' => true]);
        });

        return response()->json($this->paraResposta($docente), 201);
    }

    public function show(Docente $docente)
    {
        return response()->json($this->paraResposta($docente));
    }

    public function update(Request $request, Docente $docente)
    {
        $dados = $request->validate([
            'nome_completo' => ['sometimes', 'string', 'max:150'],
            'grau_academico' => ['nullable', 'string'],
            'categoria' => ['nullable', 'string'],
            'area_cientifica' => ['nullable', 'string'],
            'telefone' => ['nullable', 'string'],
        ]);

        $docente->update($dados);

        return response()->json($this->paraResposta($docente));
    }

    public function destroy(Docente $docente)
    {
        $docente->update(['activo' => false]);

        return response()->noContent();
    }

    private function paraResposta(Docente $docente): array
    {
        return [
            'id' => $docente->id,
            'codigo' => $docente->codigo,
            'nome_completo' => $docente->nome_completo,
            'grau_academico' => $docente->grau_academico,
            'categoria' => $docente->categoria,
            'area_cientifica' => $docente->area_cientifica,
            'activo' => $docente->activo,
        ];
    }
}
