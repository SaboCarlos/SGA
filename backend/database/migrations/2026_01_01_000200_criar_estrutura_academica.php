<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Docentes, cursos, disciplinas, planos curriculares e estudantes (RF-08 a RF-25). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docentes', function (Blueprint $t) {
            $t->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $t->foreignUuid('utilizador_id')->unique()->constrained('utilizadores');
            $t->string('codigo', 20)->unique();
            $t->string('nome_completo', 150);
            $t->string('grau_academico', 40)->nullable();
            $t->string('categoria', 40)->nullable();
            $t->string('area_cientifica', 80)->nullable();
            $t->string('telefone', 20)->nullable();
            $t->boolean('activo')->default(true);
            $t->timestampsTz();
        });

        Schema::create('cursos', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 20)->unique();
            $t->string('designacao', 150);
            $t->string('grau', 30);
            $t->unsignedSmallInteger('duracao_anos');
            $t->unsignedSmallInteger('total_creditos');
            // codigo da estrategia de calculo (Strategy, ADR-004)
            $t->string('regulamento_avaliacao', 40)->default('PADRAO_ESCN');
            $t->foreignUuid('coordenador_id')->nullable()->constrained('docentes');
            $t->boolean('activo')->default(true);
        });

        Schema::create('disciplinas', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 20)->unique();
            $t->string('designacao', 150);
            $t->unsignedSmallInteger('creditos');
            $t->unsignedSmallInteger('carga_horaria');
            $t->boolean('activo')->default(true);
        });

        Schema::create('planos_curriculares', function (Blueprint $t) {
            $t->id();
            $t->foreignId('curso_id')->constrained('cursos');
            $t->string('versao', 20);
            $t->unsignedSmallInteger('ano_inicio_vigencia');
            $t->boolean('activo')->default(true);
            $t->unique(['curso_id', 'versao']);
        });

        Schema::create('itens_plano_curricular', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plano_id')->constrained('planos_curriculares')->cascadeOnDelete();
            $t->foreignId('disciplina_id')->constrained('disciplinas');
            $t->unsignedSmallInteger('ano_curricular');
            $t->unsignedSmallInteger('semestre');
            $t->unique(['plano_id', 'disciplina_id']);   // US-09
        });
        DB::statement("ALTER TABLE itens_plano_curricular ADD COLUMN tipo tipo_disciplina_enum NOT NULL DEFAULT 'OBRIGATORIA'");
        DB::statement('ALTER TABLE itens_plano_curricular ADD CONSTRAINT ck_item_semestre CHECK (semestre IN (1, 2))');

        // RN-02: pre-requisitos; a deteccao de ciclos e feita no dominio (US-10)
        Schema::create('pre_requisitos', function (Blueprint $t) {
            $t->foreignId('disciplina_id')->constrained('disciplinas')->cascadeOnDelete();
            $t->foreignId('pre_requisito_id')->constrained('disciplinas')->cascadeOnDelete();
            $t->primary(['disciplina_id', 'pre_requisito_id']);
        });
        DB::statement('ALTER TABLE pre_requisitos ADD CONSTRAINT ck_prereq_nao_reflexivo CHECK (disciplina_id <> pre_requisito_id)');

        Schema::create('estudantes', function (Blueprint $t) {
            $t->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $t->foreignUuid('utilizador_id')->unique()->constrained('utilizadores');
            $t->foreignId('curso_id')->constrained('cursos');
            $t->foreignId('plano_id')->nullable()->constrained('planos_curriculares');
            $t->string('numero_estudante', 12)->unique();       // AAAA/NNNN (RF-09)
            $t->string('nome_completo', 150);
            $t->string('documento_identificacao', 30)->unique();
            $t->date('data_nascimento');
            $t->string('telefone', 20)->nullable();
            $t->unsignedSmallInteger('ano_ingresso');
            $t->timestampsTz();

            $t->index(['curso_id']);
        });
        DB::statement('ALTER TABLE estudantes ADD COLUMN sexo sexo_enum');
        DB::statement("ALTER TABLE estudantes ADD COLUMN estado estado_estudante_enum NOT NULL DEFAULT 'ACTIVO'");
        DB::statement('ALTER TABLE estudantes ADD CONSTRAINT ck_estudantes_nascimento CHECK (data_nascimento < CURRENT_DATE)');
        // pesquisa insensivel a maiusculas (US-06, RNF-01)
        DB::statement('CREATE INDEX ix_estudantes_nome ON estudantes (lower(nome_completo))');
    }

    public function down(): void
    {
        Schema::dropIfExists('estudantes');
        Schema::dropIfExists('pre_requisitos');
        Schema::dropIfExists('itens_plano_curricular');
        Schema::dropIfExists('planos_curriculares');
        Schema::dropIfExists('disciplinas');
        Schema::dropIfExists('cursos');
        Schema::dropIfExists('docentes');
    }
};
