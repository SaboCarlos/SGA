<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnoLectivo extends Model
{
    protected $table = 'anos_lectivos';

    public $timestamps = false;

    protected $fillable = ['designacao', 'data_inicio', 'data_fim', 'estado'];

    protected function casts(): array
    {
        return ['data_inicio' => 'date', 'data_fim' => 'date'];
    }

    public function periodos(): HasMany
    {
        return $this->hasMany(PeriodoLectivo::class, 'ano_lectivo_id');
    }
}
