<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Estudante;
use App\Models\Notificacao;
use App\Models\PeriodoLectivo;
use Illuminate\Console\Command;

/**
 * US-30: avisa os estudantes quando o período de inscrições ou de
 * lançamento de notas do seu curso está prestes a fechar.
 *
 * Idempotente por desenho: a tabela "notificacoes" tem uma UNIQUE em
 * (utilizador_id, tipo, referencia) — Notificacao::firstOrCreate() nunca
 * duplica o aviso do mesmo período no mesmo dia, mesmo que o comando corra
 * mais de uma vez.
 */
class NotificarPeriodosProximos extends Command
{
    protected $signature = 'sga:notificar-periodos {--dias=3 : Antecedência, em dias, para avisar}';

    protected $description = 'Notifica os estudantes sobre períodos de inscrição/notas a fechar em breve (US-30)';

    public function handle(): int
    {
        $dias = (int) $this->option('dias');
        $limite = now()->addDays($dias)->toDateString();
        $hoje = now()->toDateString();

        $periodos = PeriodoLectivo::whereBetween('inscricao_fim', [$hoje, $limite])
            ->orWhereBetween('notas_fim', [$hoje, $limite])
            ->get();

        $enviadas = 0;

        foreach ($periodos as $periodo) {
            $tipo = $periodo->inscricao_fim->toDateString() <= $limite && $periodo->inscricao_fim->toDateString() >= $hoje
                ? 'periodo.inscricoes_a_fechar'
                : 'periodo.notas_a_fechar';

            // O calendario lectivo (periodos_lectivos) e unico para toda a
            // instituicao -- nao ha um por curso -- por isso o aviso vai
            // para todos os estudantes activos.
            $estudantes = Estudante::where('estado', 'ACTIVO')->get();

            foreach ($estudantes as $estudante) {
                $criada = Notificacao::firstOrCreate(
                    [
                        'utilizador_id' => $estudante->utilizador_id,
                        'tipo' => $tipo,
                        'referencia' => "periodo:{$periodo->id}:{$hoje}",
                    ],
                    [
                        'titulo' => $tipo === 'periodo.inscricoes_a_fechar'
                            ? 'O período de inscrições fecha em breve'
                            : 'O período de lançamento de notas fecha em breve',
                        'mensagem' => "Semestre {$periodo->semestre} — prazo em {$periodo->inscricao_fim->toDateString()}.",
                    ]
                );

                if ($criada->wasRecentlyCreated) {
                    $enviadas++;
                }
            }
        }

        $this->info("Notificações de período criadas: {$enviadas}.");

        return self::SUCCESS;
    }
}
