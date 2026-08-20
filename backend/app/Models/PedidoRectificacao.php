<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TemChaveUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoRectificacao extends Model
{
    use TemChaveUuid;

    protected $table = 'pedidos_rectificacao';

    public $timestamps = false;

    protected $fillable = [
        'classificacao_id', 'solicitado_por', 'valor_anterior', 'valor_novo',
        'justificacao', 'criado_em', 'decidido_por', 'decidido_em', 'motivo_decisao', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'valor_anterior' => 'float',
            'valor_novo' => 'float',
            'criado_em' => 'datetime',
            'decidido_em' => 'datetime',
        ];
    }

    public function classificacao(): BelongsTo
    {
        return $this->belongsTo(Classificacao::class, 'classificacao_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'solicitado_por');
    }

    public function decisor(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'decidido_por');
    }
}
