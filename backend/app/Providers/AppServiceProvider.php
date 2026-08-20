<?php

namespace App\Providers;

use App\Dominio\Repositorios\RepositorioClassificacao;
use App\Dominio\Repositorios\RepositorioInscricao;
use App\Infraestrutura\Repositorios\RepositorioClassificacaoEloquent;
use App\Infraestrutura\Repositorios\RepositorioInscricaoEloquent;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RepositorioInscricao::class, RepositorioInscricaoEloquent::class);
        $this->app->bind(RepositorioClassificacao::class, RepositorioClassificacaoEloquent::class);
    }

    public function boot(): void
    {
        // O contrato de docs/openapi.yaml nao usa envelope "data": os schemas
        // (Utilizador, etc.) sao devolvidos directamente.
        JsonResource::withoutWrapping();
    }
}
