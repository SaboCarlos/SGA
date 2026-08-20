<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// US-30: aviso diario de periodos de inscricao/notas prestes a fechar.
Schedule::command('sga:notificar-periodos')->dailyAt('06:00');
