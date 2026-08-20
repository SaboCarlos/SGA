<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // "migrate:fresh" no Postgres remove tabelas mas nao os tipos ENUM
    // customizados (RN das migracoes da Fase 2); sem isto, a 2a corrida de
    // testes falha com "type already exists".
    protected $dropTypes = true;
}
