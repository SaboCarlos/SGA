<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TemChaveUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistoAuditoria extends Model
{
    use TemChaveUuid;

    protected $table = 'registos_auditoria';

    public $timestamps = false;

    protected $fillable = [
        'utilizador_id', 'acao', 'entidade', 'entidade_id', 'valor_anterior',
        'valor_novo', 'justificacao', 'endereco_ip', 'criado_em',
    ];

    protected function casts(): array
    {
        return ['valor_anterior' => 'array', 'valor_novo' => 'array', 'criado_em' => 'datetime'];
    }

    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'utilizador_id');
    }
}
