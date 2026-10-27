<?php

use App\Http\Middleware\ExigePerfil;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            HandleCors::class,
        ]);

        // Em producao o TLS termina no Apache do host, que encaminha em HTTP
        // puro para o contentor. Sem confiar no proxy, o Laravel julga estar
        // a servir em http:// e gera URLs absolutos errados (ex.: nos
        // e-mails de recuperacao de palavra-passe). O proxy e a rede Docker
        // local, nao a internet, por isso confiar em '*' e seguro aqui.
        $middleware->trustProxies(at: '*');

        $middleware->alias(['perfil' => ExigePerfil::class]);

        // O Laravel regista por omissao um redireccionamento de convidados
        // para a rota chamada "login" (ApplicationBuilder::withMiddleware).
        // Este backend e so API e nao tem essa rota, por isso um pedido nao
        // autenticado rebentava com RouteNotFoundException -- um 500 em vez
        // de um 401. Nao se notava enquanto o cliente enviava
        // "Accept: application/json" (o caso comum), mas o download de
        // pautas e relatorios nao envia esse cabecalho.
        // Com null, o handler devolve o 401 em JSON (shouldRenderJsonWhen).
        $middleware->redirectGuestsTo(null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
