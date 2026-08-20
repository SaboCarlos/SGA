<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Avaliacao extends Model
{
    protected $table = 'avaliacoes';

    public $timestamps = false;

    protected $fillable = ['turma_id', 'designacao', 'peso', 'data_prevista', 'tipo', 'estado'];

    protected function casts(): array
    {
        return ['peso' => 'float', 'data_prevista' => 'date'];
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    public function classificacoes(): HasMany
    {
        return $this->hasMany(Classificacao::class, 'avaliacao_id');
    }
}
