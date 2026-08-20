<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    // CACHE_STORE=array em testing nao e reposta entre testes como a base
    // de dados (RefreshDatabase so cobre a ligacao SQL): sem isto, um
    // RateLimiter::hit() num teste (ex.: tentativas de login falhadas)
    // sobreviveria para o teste seguinte que reutilizasse a mesma chave.
    ->afterEach(fn () => Cache::flush())
    ->in('Feature');
