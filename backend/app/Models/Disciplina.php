<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disciplina extends Model
{
    protected $table = 'disciplinas';

    public $timestamps = false;

    protected $fillable = ['codigo', 'designacao', 'creditos', 'carga_horaria', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /** Disciplinas exigidas como pre-requisito desta. */
    public function preRequisitos(): BelongsToMany
    {
        return $this->belongsToMany(
            Disciplina::class,
            'pre_requisitos',
            'disciplina_id',
            'pre_requisito_id'
        );
    }

    /** Disciplinas para as quais esta e pre-requisito. */
    public function disciplinasDependentes(): BelongsToMany
    {
        return $this->belongsToMany(
            Disciplina::class,
            'pre_requisitos',
            'pre_requisito_id',
            'disciplina_id'
        );
    }

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class, 'disciplina_id');
    }

    public function itensPlano(): HasMany
    {
        return $this->hasMany(ItemPlanoCurricular::class, 'disciplina_id');
    }
}
