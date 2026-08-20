<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Regras garantidas na base de dados e vistas de apoio.
 *
 * A duplicacao face as validacoes do dominio e deliberada (ADR-008): protege
 * contra pedidos concorrentes e contra acesso directo a base de dados.
 */
return new class extends Migration
{
    public function up(): void
    {
        // RN-04: capacidade da turma, com bloqueio de linha para o caso concorrente
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_valida_capacidade_turma()
            RETURNS TRIGGER AS $$
            DECLARE
                v_inscritos INTEGER;
                v_capacidade INTEGER;
            BEGIN
                SELECT capacidade_maxima INTO v_capacidade
                FROM turmas WHERE id = NEW.turma_id FOR UPDATE;

                SELECT count(*) INTO v_inscritos
                FROM inscricoes
                WHERE turma_id = NEW.turma_id AND estado = 'ACTIVA';

                IF v_inscritos >= v_capacidade THEN
                    RAISE EXCEPTION 'Capacidade da turma % esgotada (RN-04)', NEW.turma_id;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER tg_valida_capacidade_turma
                BEFORE INSERT ON inscricoes
                FOR EACH ROW EXECUTE FUNCTION fn_valida_capacidade_turma();
        SQL);

        // RN-11: nota publicada so muda por rectificacao autorizada
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_protege_nota_publicada()
            RETURNS TRIGGER AS $$
            BEGIN
                IF OLD.estado = 'PUBLICADA'
                   AND NEW.valor IS DISTINCT FROM OLD.valor
                   AND NEW.estado <> 'RECTIFICADA' THEN
                    RAISE EXCEPTION 'Nota publicada so pode ser alterada por rectificacao autorizada (RN-11)';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER tg_protege_nota_publicada
                BEFORE UPDATE ON classificacoes
                FOR EACH ROW EXECUTE FUNCTION fn_protege_nota_publicada();
        SQL);

        // RNF-32: auditoria imutavel
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_auditoria_imutavel()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'Os registos de auditoria sao imutaveis (RNF-32)';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER tg_auditoria_imutavel
                BEFORE UPDATE OR DELETE ON registos_auditoria
                FOR EACH ROW EXECUTE FUNCTION fn_auditoria_imutavel();
        SQL);

        // Vistas de apoio (RF-48, RF-55, RN-12)
        DB::unprepared(<<<'SQL'
            CREATE VIEW vw_aproveitamento AS
            SELECT e.id AS estudante_id, e.numero_estudante, e.nome_completo,
                   c.designacao AS curso, al.designacao AS ano_lectivo, pl.semestre,
                   d.codigo AS codigo_disciplina, d.designacao AS disciplina, d.creditos,
                   i.nota_frequencia, i.situacao_frequencia,
                   i.classificacao_final, i.situacao_final
            FROM inscricoes i
            JOIN estudantes e ON e.id = i.estudante_id
            JOIN cursos c ON c.id = e.curso_id
            JOIN turmas t ON t.id = i.turma_id
            JOIN disciplinas d ON d.id = t.disciplina_id
            JOIN periodos_lectivos pl ON pl.id = t.periodo_lectivo_id
            JOIN anos_lectivos al ON al.id = pl.ano_lectivo_id
            WHERE i.estado <> 'ANULADA';

            CREATE VIEW vw_media_curso AS
            SELECT i.estudante_id,
                   round(sum(i.classificacao_final * d.creditos)::numeric
                         / nullif(sum(d.creditos), 0), 1) AS media_ponderada,
                   sum(d.creditos) AS creditos_obtidos,
                   count(*) AS disciplinas_aprovadas
            FROM inscricoes i
            JOIN turmas t ON t.id = i.turma_id
            JOIN disciplinas d ON d.id = t.disciplina_id
            WHERE i.situacao_final = 'APROVADO'
            GROUP BY i.estudante_id;

            CREATE VIEW vw_aproveitamento_disciplina AS
            SELECT al.designacao AS ano_lectivo, pl.semestre, d.codigo,
                   d.designacao AS disciplina,
                   count(*) AS inscritos,
                   count(*) FILTER (WHERE i.situacao_final = 'APROVADO') AS aprovados,
                   count(*) FILTER (WHERE i.situacao_final = 'REPROVADO') AS reprovados,
                   round(100.0 * count(*) FILTER (WHERE i.situacao_final = 'APROVADO')
                         / nullif(count(*), 0), 1) AS taxa_aprovacao,
                   round(avg(i.classificacao_final), 1) AS media_disciplina
            FROM inscricoes i
            JOIN turmas t ON t.id = i.turma_id
            JOIN disciplinas d ON d.id = t.disciplina_id
            JOIN periodos_lectivos pl ON pl.id = t.periodo_lectivo_id
            JOIN anos_lectivos al ON al.id = pl.ano_lectivo_id
            WHERE i.estado <> 'ANULADA'
            GROUP BY al.designacao, pl.semestre, d.codigo, d.designacao;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP VIEW IF EXISTS vw_aproveitamento_disciplina;
            DROP VIEW IF EXISTS vw_media_curso;
            DROP VIEW IF EXISTS vw_aproveitamento;
            DROP TRIGGER IF EXISTS tg_auditoria_imutavel ON registos_auditoria;
            DROP TRIGGER IF EXISTS tg_protege_nota_publicada ON classificacoes;
            DROP TRIGGER IF EXISTS tg_valida_capacidade_turma ON inscricoes;
            DROP FUNCTION IF EXISTS fn_auditoria_imutavel;
            DROP FUNCTION IF EXISTS fn_protege_nota_publicada;
            DROP FUNCTION IF EXISTS fn_valida_capacidade_turma;
        SQL);
    }
};
