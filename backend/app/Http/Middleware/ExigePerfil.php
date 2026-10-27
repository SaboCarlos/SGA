<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Recusa com 403 quem nao tem nenhum dos perfis indicados na rota
 * (ex.: ->middleware('perfil:ADMINISTRADOR,COORDENADOR')).
 *
 * Existe para as operacoes de gestao (cadastros, oferta formativa,
 * calendario): antes so o menu do frontend as escondia, e um estudante com
 * o token na mao conseguia criar cursos ou turmas chamando a API.
 */
class ExigePerfil
{
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        abort_unless($request->user()?->temPerfil(...$perfis), 403, 'O seu perfil não permite esta operação.');

        return $next($request);
    }
}
