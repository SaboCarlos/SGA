<?php

declare(strict_types=1);

use App\Models\Notificacao;
use Database\Seeders\CenarioAcademicoSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
});

it('notifica os estudantes activos quando o periodo de inscricoes esta prestes a fechar (US-30)', function () {
    // O periodo do seeder fecha as inscricoes daqui a 30 dias; --dias=35
    // garante que a janela do comando o apanha, sem depender da data em
    // que o teste corre.
    Artisan::call('sga:notificar-periodos', ['--dias' => 35]);

    expect(Notificacao::where('tipo', 'periodo.inscricoes_a_fechar')->count())->toBe(2);
});

it('e idempotente: correr duas vezes no mesmo dia nao duplica a notificacao', function () {
    Artisan::call('sga:notificar-periodos', ['--dias' => 35]);
    Artisan::call('sga:notificar-periodos', ['--dias' => 35]);

    expect(Notificacao::where('tipo', 'periodo.inscricoes_a_fechar')->count())->toBe(2);
});

it('nao notifica quando nenhum periodo esta dentro da janela pedida', function () {
    Artisan::call('sga:notificar-periodos', ['--dias' => 1]);

    expect(Notificacao::count())->toBe(0);
});
