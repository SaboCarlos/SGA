<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tipos enumerados e tabelas de acesso (RF-01 a RF-07).
 * Convertido a partir do schema.sql produzido na Fase 2.
 */
return new class extends Migration
{
    private const TIPOS = [
        "sexo_enum AS ENUM ('MASCULINO', 'FEMININO')",
        "estado_estudante_enum AS ENUM ('ACTIVO', 'SUSPENSO', 'TRANSFERIDO', 'GRADUADO', 'DESISTENTE')",
        "tipo_disciplina_enum AS ENUM ('OBRIGATORIA', 'OPCIONAL')",
        "estado_ano_enum AS ENUM ('PLANEADO', 'ACTIVO', 'ENCERRADO')",
        "estado_inscricao_enum AS ENUM ('ACTIVA', 'ANULADA', 'CONCLUIDA')",
        "situacao_freq_enum AS ENUM ('EM_CURSO', 'DISPENSADO', 'ADMITIDO_EXAME', 'REPROVADO_FREQUENCIA')",
        "situacao_final_enum AS ENUM ('EM_CURSO', 'APROVADO', 'REPROVADO')",
        "tipo_avaliacao_enum AS ENUM ('TESTE', 'TRABALHO', 'PRATICA', 'EXAME')",
        "estado_avaliacao_enum AS ENUM ('RASCUNHO', 'ACTIVA', 'PUBLICADA')",
        "estado_classificacao_enum AS ENUM ('RASCUNHO', 'PUBLICADA', 'RECTIFICADA')",
        "estado_pedido_enum AS ENUM ('PENDENTE', 'APROVADO', 'RECUSADO')",
        "estado_pauta_enum AS ENUM ('PROVISORIA', 'EMITIDA', 'HOMOLOGADA')",
    ];

    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');

        foreach (self::TIPOS as $tipo) {
            DB::statement("CREATE TYPE {$tipo}");
        }

        Schema::create('utilizadores', function (Blueprint $t) {
            $t->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $t->string('nome', 150);
            $t->string('email', 150)->unique();
            $t->string('palavra_passe');          // hash bcrypt (RNF-06)
            $t->boolean('activo')->default(true);
            $t->timestampTz('ultimo_acesso')->nullable();
            $t->timestampsTz();
        });

        Schema::create('perfis', function (Blueprint $t) {
            $t->id();
            $t->string('nome', 30)->unique();
            $t->text('descricao')->nullable();
        });

        Schema::create('permissoes', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 60)->unique();   // ex.: classificacao.publicar
            $t->text('descricao')->nullable();
        });

        Schema::create('perfil_utilizador', function (Blueprint $t) {
            $t->foreignUuid('utilizador_id')->constrained('utilizadores')->cascadeOnDelete();
            $t->foreignId('perfil_id')->constrained('perfis')->cascadeOnDelete();
            $t->primary(['utilizador_id', 'perfil_id']);
        });

        Schema::create('permissao_perfil', function (Blueprint $t) {
            $t->foreignId('perfil_id')->constrained('perfis')->cascadeOnDelete();
            $t->foreignId('permissao_id')->constrained('permissoes')->cascadeOnDelete();
            $t->primary(['perfil_id', 'permissao_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissao_perfil');
        Schema::dropIfExists('perfil_utilizador');
        Schema::dropIfExists('permissoes');
        Schema::dropIfExists('perfis');
        Schema::dropIfExists('utilizadores');

        foreach (self::TIPOS as $tipo) {
            $nome = strtok($tipo, ' ');
            DB::statement("DROP TYPE IF EXISTS {$nome}");
        }
    }
};
