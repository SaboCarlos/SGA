<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TemChaveUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudante extends Model
{
    use HasFactory, TemChaveUuid;

    protected $table = 'estudantes';

    protected $fillable = [
        'utilizador_id', 'curso_id', 'plano_id', 'numero_estudante', 'nome_completo',
        'documento_identificacao', 'data_nascimento', 'telefone', 'ano_ingresso',
        'sexo', 'estado',
    ];

    protected function casts(): array
    {
        return ['data_nascimento' => 'date'];
    }

    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'utilizador_id');
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PlanoCurricular::class, 'plano_id');
    }

    public function inscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class, 'estudante_id');
    }

    /** Estudantes sem nenhuma disciplina aprovada (RN-02 nao satisfeita por definicao). */
    public function scopeSemAprovacoes(Builder $query): Builder
    {
        return $query->whereDoesntHave('inscricoes', function (Builder $q): void {
            $q->where('situacao_final', 'APROVADO');
        });
    }

    public function scopeComAprovacoes(Builder $query): Builder
    {
        return $query->whereHas('inscricoes', function (Builder $q): void {
            $q->where('situacao_final', 'APROVADO');
        });
    }
}
