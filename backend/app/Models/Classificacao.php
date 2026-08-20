<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TemChaveUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Classificacao extends Model
{
    use TemChaveUuid;

    protected $table = 'classificacoes';

    public $timestamps = false;

    protected $fillable = [
        'avaliacao_id', 'inscricao_id', 'valor', 'faltou', 'lancada_por',
        'lancada_em', 'publicada_em', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'float',
            'faltou' => 'boolean',
            'lancada_em' => 'datetime',
            'publicada_em' => 'datetime',
        ];
    }

    public function avaliacao(): BelongsTo
    {
        return $this->belongsTo(Avaliacao::class, 'avaliacao_id');
    }

    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class, 'inscricao_id');
    }

    public function lancadaPor(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'lancada_por');
    }
}
