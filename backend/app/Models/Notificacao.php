<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TemChaveUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacao extends Model
{
    use TemChaveUuid;

    protected $table = 'notificacoes';

    public $timestamps = false;

    protected $fillable = ['utilizador_id', 'tipo', 'titulo', 'mensagem', 'referencia', 'lida_em', 'criada_em'];

    protected function casts(): array
    {
        return ['lida_em' => 'datetime', 'criada_em' => 'datetime'];
    }

    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'utilizador_id');
    }
}
