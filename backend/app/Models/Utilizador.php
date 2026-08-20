<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TemChaveUuid;
use App\Notifications\RedefinirPalavraPasse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Utilizador extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, TemChaveUuid;

    protected $table = 'utilizadores';

    protected $fillable = [
        'nome', 'email', 'palavra_passe', 'activo', 'ultimo_acesso',
    ];

    protected $hidden = ['palavra_passe'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'ultimo_acesso' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->palavra_passe;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RedefinirPalavraPasse($token));
    }

    public function perfis(): BelongsToMany
    {
        return $this->belongsToMany(Perfil::class, 'perfil_utilizador');
    }

    public function estudante(): HasOne
    {
        return $this->hasOne(Estudante::class, 'utilizador_id');
    }

    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class, 'utilizador_id');
    }

    public function notificacoes(): HasMany
    {
        return $this->hasMany(Notificacao::class, 'utilizador_id');
    }

    public function temPerfil(string ...$nomes): bool
    {
        return $this->perfis->pluck('nome')->intersect($nomes)->isNotEmpty();
    }

    public function temPermissao(string $codigo): bool
    {
        return $this->perfis->flatMap(fn (Perfil $p) => $p->permissoes->pluck('codigo'))->contains($codigo);
    }

    public function scopeAdministrador(Builder $query): Builder
    {
        return $query->whereHas('perfis', fn (Builder $q) => $q->where('nome', 'ADMINISTRADOR'));
    }

    public function scopeComPerfil(Builder $query, string $nome): Builder
    {
        return $query->whereHas('perfis', fn (Builder $q) => $q->where('nome', $nome));
    }
}
