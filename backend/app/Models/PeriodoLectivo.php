<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoLectivo extends Model
{
    protected $table = 'periodos_lectivos';

    public $timestamps = false;

    protected $fillable = [
        'ano_lectivo_id', 'semestre', 'inscricao_inicio', 'inscricao_fim',
        'notas_inicio', 'notas_fim',
    ];

    protected function casts(): array
    {
        return [
            'inscricao_inicio' => 'date',
            'inscricao_fim' => 'date',
            'notas_inicio' => 'date',
            'notas_fim' => 'date',
        ];
    }

    public function anoLectivo(): BelongsTo
    {
        return $this->belongsTo(AnoLectivo::class, 'ano_lectivo_id');
    }

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class, 'periodo_lectivo_id');
    }

    /** RN-05: periodo de inscricoes aberto hoje. */
    public function inscricoesAbertas(): bool
    {
        $hoje = now()->toDateString();

        return $hoje >= $this->inscricao_inicio->toDateString()
            && $hoje <= $this->inscricao_fim->toDateString();
    }
}
