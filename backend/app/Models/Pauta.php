<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pauta extends Model
{
    protected $table = 'pautas';

    public $timestamps = false;

    protected $fillable = [
        'turma_id', 'numero', 'versao', 'total_inscritos', 'total_aprovados',
        'emitida_por', 'emitida_em', 'homologada_por', 'homologada_em', 'estado',
    ];

    protected function casts(): array
    {
        return ['emitida_em' => 'datetime', 'homologada_em' => 'datetime'];
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    public function emitidaPor(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'emitida_por');
    }

    public function homologadaPor(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'homologada_por');
    }
}
