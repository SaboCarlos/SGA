<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPlanoCurricular extends Model
{
    protected $table = 'itens_plano_curricular';

    public $timestamps = false;

    protected $fillable = ['plano_id', 'disciplina_id', 'ano_curricular', 'semestre', 'tipo'];

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PlanoCurricular::class, 'plano_id');
    }

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class, 'disciplina_id');
    }
}
