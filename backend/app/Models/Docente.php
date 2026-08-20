<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TemChaveUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Docente extends Model
{
    use HasFactory, TemChaveUuid;

    protected $table = 'docentes';

    protected $fillable = [
        'utilizador_id', 'codigo', 'nome_completo', 'grau_academico',
        'categoria', 'area_cientifica', 'telefone', 'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'utilizador_id');
    }

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class, 'docente_id');
    }

    public function cursosCoordenados(): HasMany
    {
        return $this->hasMany(Curso::class, 'coordenador_id');
    }
}
