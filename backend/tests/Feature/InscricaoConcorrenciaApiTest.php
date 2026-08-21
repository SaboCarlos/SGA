<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;

/**
 * UC14-A7 (US-13): duas inscricoes simultaneas disputam a ultima vaga de
 * uma turma. Defeito conhecido #2 em docs/fase5-testes.md secao 7.3.
 *
 * A defesa real esta na base de dados (trigger tg_valida_capacidade_turma,
 * BEFORE INSERT ON inscricoes, com "SELECT ... FOR UPDATE" sobre a turma --
 * ver a migration 2026_01_01_000400 e ADR-008); a validacao no dominio
 * (RN-04 em ValidadorInscricao) nao protege contra a corrida porque cada
 * pedido le a contagem de inscritos antes de qualquer um escrever.
 *
 * Para provar isto e preciso duas ligacoes reais e simultaneas a base de
 * dados -- impossivel dentro da transaccao unica que o RefreshDatabase
 * envolve a cada teste (Pest.php). Este teste sai desse isolamento de
 * proposito: cria os dados fixos e corre a corrida em dois subprocessos
 * "php artisan tinker", cada um com a sua propria ligacao Postgres, e
 * garante a limpeza no final porque os dados ficam mesmo escritos (commit
 * real, nao a transaccao que o Pest desfaz).
 */
function envBaseDados(): array
{
    return [
        'DB_CONNECTION' => 'pgsql',
        'DB_HOST' => config('database.connections.pgsql.host'),
        'DB_PORT' => (string) config('database.connections.pgsql.port'),
        'DB_DATABASE' => config('database.connections.pgsql.database'),
        'DB_USERNAME' => config('database.connections.pgsql.username'),
        'DB_PASSWORD' => config('database.connections.pgsql.password'),
    ];
}

function tinkerSincrono(string $codigo): string
{
    return Process::env(envBaseDados())
        ->timeout(15)
        ->path(base_path())
        ->run(['php', 'artisan', 'tinker', '--execute', $codigo])
        ->output();
}

it('apenas uma de duas inscricoes simultaneas ganha a ultima vaga da turma (UC14-A7)', function () {
    $marca = 'RACE'.random_int(100000, 999999);

    $configuracao = tinkerSincrono(<<<PHP
        \$al = \App\Models\AnoLectivo::create(['designacao'=>'{$marca}-AL','data_inicio'=>'2020-01-01','data_fim'=>'2020-12-31']);
        \$p = \App\Models\PeriodoLectivo::create(['ano_lectivo_id'=>\$al->id,'semestre'=>1,'inscricao_inicio'=>'2020-01-01','inscricao_fim'=>'2020-06-01','notas_inicio'=>'2020-06-02','notas_fim'=>'2020-07-01']);
        \$c = \App\Models\Curso::create(['codigo'=>'{$marca}-C','designacao'=>'Curso de teste','grau'=>'Licenciatura','duracao_anos'=>4,'total_creditos'=>240,'activo'=>true]);
        \$d = \App\Models\Disciplina::create(['codigo'=>'{$marca}-D','designacao'=>'Disciplina de teste','creditos'=>6,'carga_horaria'=>90,'activo'=>true]);
        \$uDoc = \App\Models\Utilizador::create(['nome'=>'Docente','email'=>strtolower('{$marca}').'-doc@teste.local','palavra_passe'=>\Illuminate\Support\Facades\Hash::make('x'),'activo'=>true]);
        \$doc = \App\Models\Docente::create(['utilizador_id'=>\$uDoc->id,'codigo'=>'{$marca}-DOC','nome_completo'=>'Docente','activo'=>true]);
        \$t = \App\Models\Turma::create(['disciplina_id'=>\$d->id,'periodo_lectivo_id'=>\$p->id,'docente_id'=>\$doc->id,'designacao'=>'A','capacidade_maxima'=>1]);
        \$uA = \App\Models\Utilizador::create(['nome'=>'Racer A','email'=>strtolower('{$marca}').'-a@teste.local','palavra_passe'=>\Illuminate\Support\Facades\Hash::make('x'),'activo'=>true]);
        \$eA = \App\Models\Estudante::create(['utilizador_id'=>\$uA->id,'curso_id'=>\$c->id,'numero_estudante'=>'{$marca}/A','nome_completo'=>'Racer A','documento_identificacao'=>'{$marca}-DA','data_nascimento'=>'2000-01-01','ano_ingresso'=>2026,'estado'=>'ACTIVO']);
        \$uB = \App\Models\Utilizador::create(['nome'=>'Racer B','email'=>strtolower('{$marca}').'-b@teste.local','palavra_passe'=>\Illuminate\Support\Facades\Hash::make('x'),'activo'=>true]);
        \$eB = \App\Models\Estudante::create(['utilizador_id'=>\$uB->id,'curso_id'=>\$c->id,'numero_estudante'=>'{$marca}/B','nome_completo'=>'Racer B','documento_identificacao'=>'{$marca}-DB','data_nascimento'=>'2000-01-01','ano_ingresso'=>2026,'estado'=>'ACTIVO']);
        echo json_encode(['turma'=>\$t->id,'a'=>\$eA->id,'b'=>\$eB->id]);
        PHP);

    $dados = json_decode($configuracao, true);
    expect($dados)->not->toBeNull("Falha ao preparar os dados fixos da corrida. Saída do tinker: {$configuracao}");

    $turmaId = $dados['turma'];
    $estudanteA = $dados['a'];
    $estudanteB = $dados['b'];

    try {
        // A: insere e mantém a transacção aberta por 0.8s (pg_sleep) para
        // segurar o bloqueio de linha que o trigger adquire com FOR UPDATE.
        $processoA = Process::env(envBaseDados())->timeout(15)->path(base_path())->start(['php', 'artisan', 'tinker', '--execute', <<<PHP
            try {
                \Illuminate\Support\Facades\DB::transaction(function () {
                    \App\Models\Inscricao::create(['estudante_id'=>'{$estudanteA}','turma_id'=>{$turmaId},'estado'=>'ACTIVA']);
                    \Illuminate\Support\Facades\DB::statement('select pg_sleep(0.8)');
                });
                echo 'OK';
            } catch (\Throwable \$e) { echo 'ERRO:'.\$e->getMessage(); }
            PHP]);

        // B arranca depois de A já ter inserido e estar a segurar o
        // bloqueio (dá tempo a A para lá chegar primeiro).
        usleep(250_000);

        $processoB = Process::env(envBaseDados())->timeout(15)->path(base_path())->start(['php', 'artisan', 'tinker', '--execute', <<<PHP
            try {
                \App\Models\Inscricao::create(['estudante_id'=>'{$estudanteB}','turma_id'=>{$turmaId},'estado'=>'ACTIVA']);
                echo 'OK';
            } catch (\Throwable \$e) { echo 'ERRO:'.\$e->getMessage(); }
            PHP]);

        $resultadoA = $processoA->wait();
        $resultadoB = $processoB->wait();

        $saidas = [$resultadoA->output(), $resultadoB->output()];
        $sucessos = array_filter($saidas, fn ($s) => str_starts_with($s, 'OK'));
        $falhas = array_filter($saidas, fn ($s) => str_starts_with($s, 'ERRO'));

        // Exactamente um dos dois pedidos concorrentes ganha a vaga; o
        // outro tem de ser recusado pelo trigger (RN-04), nunca aceite.
        expect($sucessos)->toHaveCount(1, 'Esperava exactamente uma inscrição bem sucedida. Saídas: '.implode(' | ', $saidas));
        expect($falhas)->toHaveCount(1);
        expect(array_values($falhas)[0])->toContain('esgotada');

        $contagem = (int) trim(tinkerSincrono(
            "echo \App\Models\Inscricao::where('turma_id',{$turmaId})->where('estado','ACTIVA')->count();"
        ));
        expect($contagem)->toBe(1, 'A turma com capacidade 1 não pode ter mais de uma inscrição activa.');
    } finally {
        // Limpeza real (commit fora da transacção do Pest): tem de correr
        // pela mesma via, senão os dados ficam a poluir a base de teste.
        tinkerSincrono(<<<PHP
            \App\Models\Inscricao::where('turma_id',{$turmaId})->delete();
            \App\Models\Turma::find({$turmaId})?->delete();
            \App\Models\Docente::where('codigo','like','{$marca}%')->delete();
            \App\Models\Disciplina::where('codigo','like','{$marca}%')->delete();
            \App\Models\PeriodoLectivo::whereHas('anoLectivo', fn(\$q)=>\$q->where('designacao','like','{$marca}%'))->delete();
            \App\Models\AnoLectivo::where('designacao','like','{$marca}%')->delete();
            \App\Models\Estudante::whereIn('id',['{$estudanteA}','{$estudanteB}'])->delete();
            \App\Models\Curso::where('codigo','like','{$marca}%')->delete();
            \App\Models\Utilizador::where('email','like',strtolower('{$marca}').'%')->delete();
            echo 'ok';
            PHP);
    }
})->group('concorrencia');
