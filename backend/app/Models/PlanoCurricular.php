<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanoCurricular extends Model
{
    protected $table = 'planos_curriculares';

    public $timestamps = false;

    protected $fillable = ['curso_id', 'versao', 'ano_inicio_vigencia', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemPlanoCurricular::class, 'plano_id');
    }
}
