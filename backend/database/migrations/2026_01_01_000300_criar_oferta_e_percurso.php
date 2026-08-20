<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Oferta lectiva e percurso academico (RF-26 a RF-54, RF-61 a RF-67). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anos_lectivos', function (Blueprint $t) {
            $t->id();
            $t->string('designacao', 15)->unique();
            $t->date('data_inicio');
            $t->date('data_fim');
        });
        DB::statement("ALTER TABLE anos_lectivos ADD COLUMN estado estado_ano_enum NOT NULL DEFAULT 'PLANEADO'");
        DB::statement('ALTER TABLE anos_lectivos ADD CONSTRAINT ck_anos_datas CHECK (data_fim > data_inicio)');
        // US-12: existe no maximo um ano lectivo ACTIVO
        DB::statement("CREATE UNIQUE INDEX uk_ano_lectivo_activo ON anos_lectivos ((estado = 'ACTIVO')) WHERE estado = 'ACTIVO'");

        Schema::create('periodos_lectivos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ano_lectivo_id')->constrained('anos_lectivos')->cascadeOnDelete();
            $t->unsignedSmallInteger('semestre');
            $t->date('inscricao_inicio');
            $t->date('inscricao_fim');
            $t->date('notas_inicio');
            $t->date('notas_fim');
            $t->unique(['ano_lectivo_id', 'semestre']);
        });
        DB::statement('ALTER TABLE periodos_lectivos ADD CONSTRAINT ck_periodo_semestre CHECK (semestre IN (1, 2))');

        // Entidade central da modelacao (decisao D1 da Fase 2)
        Schema::create('turmas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('disciplina_id')->constrained('disciplinas');
            $t->foreignId('periodo_lectivo_id')->constrained('periodos_lectivos');
            $t->foreignUuid('docente_id')->constrained('docentes');
            $t->string('designacao', 20)->default('A');
            $t->unsignedSmallInteger('capacidade_maxima');
            $t->timestampsTz();

            $t->unique(['disciplina_id', 'periodo_lectivo_id', 'designacao']);
            $t->index(['docente_id', 'periodo_lectivo_id']);
        });

        Schema::create('inscricoes', function (Blueprint $t) {
            $t->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $t->foreignUuid('estudante_id')->constrained('estudantes');
            $t->foreignId('turma_id')->constrained('turmas');
            $t->timestampTz('data_inscricao')->useCurrent();
            // resultado consolidado, calculado pelo ServicoAvaliacao (decisao D2 / ADR-007)
            $t->decimal('nota_frequencia', 4, 1)->nullable();
            $t->decimal('nota_exame', 4, 1)->nullable();
            $t->decimal('classificacao_final', 4, 1)->nullable();
            $t->foreignUuid('registada_por')->nullable()->constrained('utilizadores');
            $t->text('observacoes')->nullable();

            $t->unique(['estudante_id', 'turma_id']);   // RN-03
            $t->index(['turma_id']);
        });
        DB::statement("ALTER TABLE inscricoes ADD COLUMN estado estado_inscricao_enum NOT NULL DEFAULT 'ACTIVA'");
        DB::statement("ALTER TABLE inscricoes ADD COLUMN situacao_frequencia situacao_freq_enum NOT NULL DEFAULT 'EM_CURSO'");
        DB::statement("ALTER TABLE inscricoes ADD COLUMN situacao_final situacao_final_enum NOT NULL DEFAULT 'EM_CURSO'");
        foreach (['nota_frequencia', 'nota_exame', 'classificacao_final'] as $coluna) {
            DB::statement("ALTER TABLE inscricoes ADD CONSTRAINT ck_inscricao_{$coluna} CHECK ({$coluna} IS NULL OR {$coluna} BETWEEN 0 AND 20)");
        }

        Schema::create('avaliacoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();
            $t->string('designacao', 60);
            $t->decimal('peso', 5, 2);
            $t->date('data_prevista')->nullable();
        });
        DB::statement('ALTER TABLE avaliacoes ADD COLUMN tipo tipo_avaliacao_enum NOT NULL');
        DB::statement("ALTER TABLE avaliacoes ADD COLUMN estado estado_avaliacao_enum NOT NULL DEFAULT 'RASCUNHO'");
        DB::statement('ALTER TABLE avaliacoes ADD CONSTRAINT ck_avaliacoes_peso CHECK (peso > 0 AND peso <= 100)');

        Schema::create('classificacoes', function (Blueprint $t) {
            $t->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('avaliacao_id')->constrained('avaliacoes')->cascadeOnDelete();
            $t->foreignUuid('inscricao_id')->constrained('inscricoes')->cascadeOnDelete();
            $t->decimal('valor', 4, 1)->nullable();
            $t->boolean('faltou')->default(false);      // RF-43
            $t->foreignUuid('lancada_por')->nullable()->constrained('utilizadores');
            $t->timestampTz('lancada_em')->useCurrent();
            $t->timestampTz('publicada_em')->nullable();

            $t->unique(['avaliacao_id', 'inscricao_id']);
            $t->index(['inscricao_id']);
        });
        DB::statement("ALTER TABLE classificacoes ADD COLUMN estado estado_classificacao_enum NOT NULL DEFAULT 'RASCUNHO'");
        DB::statement('ALTER TABLE classificacoes ADD CONSTRAINT ck_classificacao_valor CHECK (valor IS NULL OR valor BETWEEN 0 AND 20)');

        Schema::create('pedidos_rectificacao', function (Blueprint $t) {
            $t->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $t->foreignUuid('classificacao_id')->constrained('classificacoes');
            $t->foreignUuid('solicitado_por')->constrained('utilizadores');
            $t->decimal('valor_anterior', 4, 1)->nullable();
            $t->decimal('valor_novo', 4, 1);
            $t->text('justificacao');
            $t->timestampTz('criado_em')->useCurrent();
            $t->foreignUuid('decidido_por')->nullable()->constrained('utilizadores');
            $t->timestampTz('decidido_em')->nullable();
            $t->text('motivo_decisao')->nullable();
        });
        DB::statement("ALTER TABLE pedidos_rectificacao ADD COLUMN estado estado_pedido_enum NOT NULL DEFAULT 'PENDENTE'");
        DB::statement('ALTER TABLE pedidos_rectificacao ADD CONSTRAINT ck_rectificacao_justificacao CHECK (char_length(justificacao) >= 20)');

        Schema::create('pautas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('turma_id')->constrained('turmas');
            $t->string('numero', 30)->unique();
            $t->unsignedSmallInteger('versao')->default(1);
            $t->unsignedSmallInteger('total_inscritos')->nullable();
            $t->unsignedSmallInteger('total_aprovados')->nullable();
            $t->foreignUuid('emitida_por')->nullable()->constrained('utilizadores');
            $t->timestampTz('emitida_em')->useCurrent();
            $t->foreignUuid('homologada_por')->nullable()->constrained('utilizadores');
            $t->timestampTz('homologada_em')->nullable();
            $t->unique(['turma_id', 'versao']);
        });
        DB::statement("ALTER TABLE pautas ADD COLUMN estado estado_pauta_enum NOT NULL DEFAULT 'PROVISORIA'");

        Schema::create('notificacoes', function (Blueprint $t) {
            $t->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $t->foreignUuid('utilizador_id')->constrained('utilizadores')->cascadeOnDelete();
            $t->string('tipo', 60);
            $t->string('titulo', 150);
            $t->text('mensagem')->nullable();
            $t->string('referencia', 120)->nullable();
            $t->timestampTz('lida_em')->nullable();
            $t->timestampTz('criada_em')->useCurrent();

            $t->unique(['utilizador_id', 'tipo', 'referencia']);   // US-30
        });
        DB::statement('CREATE INDEX ix_notificacoes_nao_lidas ON notificacoes (utilizador_id) WHERE lida_em IS NULL');

        // Tabela apenas de escrita (RNF-32)
        Schema::create('registos_auditoria', function (Blueprint $t) {
            $t->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $t->foreignUuid('utilizador_id')->nullable()->constrained('utilizadores');
            $t->string('acao', 60);
            $t->string('entidade', 60);
            $t->string('entidade_id', 60);
            $t->jsonb('valor_anterior')->nullable();
            $t->jsonb('valor_novo')->nullable();
            $t->text('justificacao')->nullable();
            $t->ipAddress('endereco_ip')->nullable();
            $t->timestampTz('criado_em')->useCurrent();

            $t->index(['entidade', 'entidade_id']);
            $t->index(['utilizador_id', 'criado_em']);
        });
    }

    public function down(): void
    {
        foreach ([
            'registos_auditoria', 'notificacoes', 'pautas', 'pedidos_rectificacao',
            'classificacoes', 'avaliacoes', 'inscricoes', 'turmas',
            'periodos_lectivos', 'anos_lectivos',
        ] as $tabela) {
            Schema::dropIfExists($tabela);
        }
    }
};
