<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Perfil extends Model
{
    protected $table = 'perfis';

    public $timestamps = false;

    protected $fillable = ['nome', 'descricao'];

    public function utilizadores(): BelongsToMany
    {
        return $this->belongsToMany(Utilizador::class, 'perfil_utilizador');
    }

    public function permissoes(): BelongsToMany
    {
        return $this->belongsToMany(Permissao::class, 'permissao_perfil');
    }
}
