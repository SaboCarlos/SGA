<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * As tabelas UUID tem gen_random_uuid() como default na base de dados (Fase 2),
 * mas o id e gerado aqui tambem para estar disponivel de imediato apos
 * create(), sem precisar de refresh() (RepositorioInscricao::guardar() depende
 * disto para devolver o id ao chamador na mesma chamada).
 */
trait TemChaveUuid
{
    public function initializeTemChaveUuid(): void
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }

    protected static function bootTemChaveUuid(): void
    {
        static::creating(function ($model): void {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
