<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Utilizador;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Utilizador */
class UtilizadorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing('perfis.permissoes');

        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'perfis' => $this->perfis->pluck('nome')->values(),
            'permissoes' => $this->perfis
                ->flatMap(fn ($perfil) => $perfil->permissoes->pluck('codigo'))
                ->unique()
                ->values(),
        ];
    }
}
