<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permissao extends Model
{
    protected $table = 'permissoes';

    public $timestamps = false;

    protected $fillable = ['codigo', 'descricao'];

    public function perfis(): BelongsToMany
    {
        return $this->belongsToMany(Perfil::class, 'permissao_perfil');
    }
}
