<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $designacao
 * @property int $capacidade_maxima
 * @property int $disciplina_id
 * @property int $periodo_lectivo_id
 * @property string|null $docente_id
 * @property-read Disciplina|null $disciplina
 */
class Turma extends Model
{
    use HasFactory;

    protected $table = 'turmas';

    protected $fillable = [
        'disciplina_id', 'periodo_lectivo_id', 'docente_id', 'designacao', 'capacidade_maxima',
    ];

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class, 'disciplina_id');
    }

    public function periodoLectivo(): BelongsTo
    {
        return $this->belongsTo(PeriodoLectivo::class, 'periodo_lectivo_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function inscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class, 'turma_id');
    }

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class, 'turma_id');
    }

    public function inscritosActivos(): int
    {
        return $this->inscricoes()->where('estado', 'ACTIVA')->count();
    }

    public function vagasDisponiveis(): int
    {
        return max(0, $this->capacidade_maxima - $this->inscritosActivos());
    }

    /** Turmas com pelo menos uma vaga livre. */
    public function scopeComVagas(Builder $query): Builder
    {
        return $query->whereRaw(
            'capacidade_maxima > (select count(*) from inscricoes where inscricoes.turma_id = turmas.id and inscricoes.estado = ?)',
            ['ACTIVA']
        );
    }

    /** Turmas na capacidade maxima (RN-04). */
    public function scopeEsgotada(Builder $query): Builder
    {
        return $query->whereRaw(
            'capacidade_maxima <= (select count(*) from inscricoes where inscricoes.turma_id = turmas.id and inscricoes.estado = ?)',
            ['ACTIVA']
        );
    }

    /** Turmas cuja disciplina exige pelo menos um pre-requisito (RN-02). */
    public function scopeComPreRequisitos(Builder $query): Builder
    {
        return $query->whereHas('disciplina.preRequisitos');
    }
}
