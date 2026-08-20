<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    protected $table = 'cursos';

    public $timestamps = false;

    protected $fillable = [
        'codigo', 'designacao', 'grau', 'duracao_anos', 'total_creditos',
        'regulamento_avaliacao', 'coordenador_id', 'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function coordenador(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'coordenador_id');
    }

    public function planosCurriculares(): HasMany
    {
        return $this->hasMany(PlanoCurricular::class, 'curso_id');
    }

    public function planoActivo(): ?PlanoCurricular
    {
        return $this->planosCurriculares()->where('activo', true)->first();
    }

    public function estudantes(): HasMany
    {
        return $this->hasMany(Estudante::class, 'curso_id');
    }
}
