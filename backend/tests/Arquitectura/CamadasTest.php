<?php

declare(strict_types=1);

/**
 * Testes de arquitectura: garantem que a regra de dependencia definida na Fase 3
 * (1 -> 2 -> 3 -> 4 <- 5) nao e violada.
 *
 * Uma violacao faz o pipeline de CI falhar, em vez de ser descoberta na defesa.
 */
arch('o dominio nao depende da infra-estrutura nem do framework')
    ->expect('App\Dominio')
    ->not->toUse([
        'Illuminate\Database',
        'Illuminate\Http',
        'Illuminate\Support\Facades',
        'App\Models',
        'App\Http',
        'App\Infraestrutura',
    ]);

arch('os controladores nao contem regras de calculo')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Dominio\Regras\Regulamento');

arch('os controladores nao acedem directamente aos modelos de persistencia')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('os servicos de aplicacao dependem de abstraccoes de repositorio')
    ->expect('App\Servicos')
    ->not->toUse('App\Infraestrutura\Repositorios');

arch('as excepcoes de dominio transportam o codigo da regra')
    ->expect('App\Dominio\Excepcoes')
    ->toExtend('App\Dominio\Excepcoes\RegraNegocioViolada')
    ->ignoring('App\Dominio\Excepcoes\RegraNegocioViolada');

arch('nao ha codigo de depuracao esquecido')
    ->expect(['dd', 'dump', 'var_dump', 'ray', 'die'])
    ->not->toBeUsed();

arch('as classes de dominio sao declaradas com tipos estritos')
    ->expect('App\Dominio')
    ->toUseStrictTypes();
