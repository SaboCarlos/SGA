<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TemChaveUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inscricao extends Model
{
    use HasFactory, TemChaveUuid;

    protected $table = 'inscricoes';

    public $timestamps = false;

    protected $fillable = [
        'estudante_id', 'turma_id', 'data_inscricao', 'nota_frequencia', 'nota_exame',
        'classificacao_final', 'registada_por', 'observacoes', 'estado',
        'situacao_frequencia', 'situacao_final',
    ];

    protected function casts(): array
    {
        return [
            'data_inscricao' => 'datetime',
            'nota_frequencia' => 'float',
            'nota_exame' => 'float',
            'classificacao_final' => 'float',
        ];
    }

    public function estudante(): BelongsTo
    {
        return $this->belongsTo(Estudante::class, 'estudante_id');
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    public function registadaPor(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'registada_por');
    }

    public function classificacoes(): HasMany
    {
        return $this->hasMany(Classificacao::class, 'inscricao_id');
    }
}
