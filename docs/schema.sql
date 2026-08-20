-- =============================================================================
-- Sistema de Gestao Academica (SGA) - Modelo fisico da base de dados
-- Projecto 2 - Engenharia de Software - Grupo 2 - ESCN, 2026
-- SGBD: PostgreSQL 14+
-- Fase 2 - Modelacao
-- =============================================================================
-- Convencoes:
--   * nomes de tabelas e colunas em snake_case, no plural para tabelas;
--   * chaves primarias UUID nas entidades com dados pessoais ou sensiveis
--     (evita enumeracao sequencial de recursos na API - ver RNF-12);
--   * chaves primarias SERIAL nas entidades de configuracao;
--   * datas e horas sempre em TIMESTAMPTZ (RNF-34);
--   * eliminacao logica (activo / estado) em vez de DELETE fisico (RF-12).
-- =============================================================================

CREATE EXTENSION IF NOT EXISTS "pgcrypto";  -- gen_random_uuid()

-- =============================================================================
-- 1. TIPOS ENUMERADOS
-- =============================================================================

CREATE TYPE sexo_enum                  AS ENUM ('MASCULINO', 'FEMININO');
CREATE TYPE estado_estudante_enum      AS ENUM ('ACTIVO', 'SUSPENSO', 'TRANSFERIDO', 'GRADUADO', 'DESISTENTE');
CREATE TYPE tipo_disciplina_enum       AS ENUM ('OBRIGATORIA', 'OPCIONAL');
CREATE TYPE estado_ano_enum            AS ENUM ('PLANEADO', 'ACTIVO', 'ENCERRADO');
CREATE TYPE estado_inscricao_enum      AS ENUM ('ACTIVA', 'ANULADA', 'CONCLUIDA');
CREATE TYPE situacao_freq_enum         AS ENUM ('EM_CURSO', 'DISPENSADO', 'ADMITIDO_EXAME', 'REPROVADO_FREQUENCIA');
CREATE TYPE situacao_final_enum        AS ENUM ('EM_CURSO', 'APROVADO', 'REPROVADO');
CREATE TYPE tipo_avaliacao_enum        AS ENUM ('TESTE', 'TRABALHO', 'PRATICA', 'EXAME');
CREATE TYPE estado_avaliacao_enum      AS ENUM ('RASCUNHO', 'ACTIVA', 'PUBLICADA');
CREATE TYPE estado_classificacao_enum  AS ENUM ('RASCUNHO', 'PUBLICADA', 'RECTIFICADA');
CREATE TYPE estado_pedido_enum         AS ENUM ('PENDENTE', 'APROVADO', 'RECUSADO');
CREATE TYPE estado_pauta_enum          AS ENUM ('PROVISORIA', 'EMITIDA', 'HOMOLOGADA');

-- =============================================================================
-- 2. ACESSO, PERFIS E PERMISSOES  (RF-01 a RF-07)
-- =============================================================================

CREATE TABLE utilizadores (
    id              UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    nome            VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    palavra_passe   VARCHAR(255) NOT NULL,          -- hash bcrypt/Argon2 (RNF-06)
    activo          BOOLEAN      NOT NULL DEFAULT TRUE,
    ultimo_acesso   TIMESTAMPTZ,
    criado_em       TIMESTAMPTZ  NOT NULL DEFAULT now(),
    actualizado_em  TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT uk_utilizadores_email UNIQUE (email)
);

CREATE TABLE perfis (
    id          SERIAL      PRIMARY KEY,
    nome        VARCHAR(30) NOT NULL,
    descricao   TEXT,
    CONSTRAINT uk_perfis_nome UNIQUE (nome)
);

CREATE TABLE permissoes (
    id          SERIAL      PRIMARY KEY,
    codigo      VARCHAR(60) NOT NULL,               -- ex.: 'classificacao.publicar'
    descricao   TEXT,
    CONSTRAINT uk_permissoes_codigo UNIQUE (codigo)
);

CREATE TABLE perfil_utilizador (
    utilizador_id   UUID    NOT NULL REFERENCES utilizadores (id) ON DELETE CASCADE,
    perfil_id       INTEGER NOT NULL REFERENCES perfis (id)       ON DELETE CASCADE,
    PRIMARY KEY (utilizador_id, perfil_id)
);

CREATE TABLE permissao_perfil (
    perfil_id       INTEGER NOT NULL REFERENCES perfis (id)      ON DELETE CASCADE,
    permissao_id    INTEGER NOT NULL REFERENCES permissoes (id)  ON DELETE CASCADE,
    PRIMARY KEY (perfil_id, permissao_id)
);

-- =============================================================================
-- 3. CURSOS, PLANOS CURRICULARES E DISCIPLINAS  (RF-18 a RF-25)
-- =============================================================================

CREATE TABLE docentes (
    id               UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    utilizador_id    UUID         NOT NULL REFERENCES utilizadores (id),
    codigo           VARCHAR(20)  NOT NULL,
    nome_completo    VARCHAR(150) NOT NULL,
    grau_academico   VARCHAR(40),
    categoria        VARCHAR(40),
    area_cientifica  VARCHAR(80),
    telefone         VARCHAR(20),
    activo           BOOLEAN      NOT NULL DEFAULT TRUE,
    criado_em        TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT uk_docentes_utilizador UNIQUE (utilizador_id),
    CONSTRAINT uk_docentes_codigo     UNIQUE (codigo)
);

CREATE TABLE cursos (
    id                      SERIAL       PRIMARY KEY,
    codigo                  VARCHAR(20)  NOT NULL,
    designacao              VARCHAR(150) NOT NULL,
    grau                    VARCHAR(30)  NOT NULL,
    duracao_anos            INTEGER      NOT NULL,
    total_creditos          INTEGER      NOT NULL,
    -- identifica a estrategia de calculo aplicavel (padrao Strategy, RN-06 a RN-12)
    regulamento_avaliacao   VARCHAR(40)  NOT NULL DEFAULT 'PADRAO_ESCN',
    coordenador_id          UUID         REFERENCES docentes (id),
    activo                  BOOLEAN      NOT NULL DEFAULT TRUE,
    CONSTRAINT uk_cursos_codigo    UNIQUE (codigo),
    CONSTRAINT ck_cursos_duracao   CHECK (duracao_anos BETWEEN 1 AND 8),
    CONSTRAINT ck_cursos_creditos  CHECK (total_creditos > 0)
);

CREATE TABLE disciplinas (
    id             SERIAL       PRIMARY KEY,
    codigo         VARCHAR(20)  NOT NULL,
    designacao     VARCHAR(150) NOT NULL,
    creditos       INTEGER      NOT NULL,
    carga_horaria  INTEGER      NOT NULL,
    activo         BOOLEAN      NOT NULL DEFAULT TRUE,
    CONSTRAINT uk_disciplinas_codigo   UNIQUE (codigo),
    CONSTRAINT ck_disciplinas_creditos CHECK (creditos > 0)
);

CREATE TABLE planos_curriculares (
    id                   SERIAL      PRIMARY KEY,
    curso_id             INTEGER     NOT NULL REFERENCES cursos (id),
    versao               VARCHAR(20) NOT NULL,
    ano_inicio_vigencia  INTEGER     NOT NULL,
    activo               BOOLEAN     NOT NULL DEFAULT TRUE,
    CONSTRAINT uk_planos_curso_versao UNIQUE (curso_id, versao)
);

CREATE TABLE itens_plano_curricular (
    id              SERIAL               PRIMARY KEY,
    plano_id        INTEGER              NOT NULL REFERENCES planos_curriculares (id) ON DELETE CASCADE,
    disciplina_id   INTEGER              NOT NULL REFERENCES disciplinas (id),
    ano_curricular  INTEGER              NOT NULL,
    semestre        INTEGER              NOT NULL,
    tipo            tipo_disciplina_enum NOT NULL DEFAULT 'OBRIGATORIA',
    -- a mesma disciplina nao pode figurar duas vezes no mesmo plano (US-09)
    CONSTRAINT uk_item_plano_disciplina UNIQUE (plano_id, disciplina_id),
    CONSTRAINT ck_item_semestre         CHECK (semestre IN (1, 2)),
    CONSTRAINT ck_item_ano              CHECK (ano_curricular BETWEEN 1 AND 8)
);

-- RN-02: pre-requisitos entre disciplinas. A deteccao de ciclos e feita na
-- camada de dominio (US-10); a BD garante apenas a nao auto-referencia.
CREATE TABLE pre_requisitos (
    disciplina_id     INTEGER NOT NULL REFERENCES disciplinas (id) ON DELETE CASCADE,
    pre_requisito_id  INTEGER NOT NULL REFERENCES disciplinas (id) ON DELETE CASCADE,
    PRIMARY KEY (disciplina_id, pre_requisito_id),
    CONSTRAINT ck_prereq_nao_reflexivo CHECK (disciplina_id <> pre_requisito_id)
);

-- =============================================================================
-- 4. ESTUDANTES  (RF-08 a RF-13)
-- =============================================================================

CREATE TABLE estudantes (
    id                       UUID                  PRIMARY KEY DEFAULT gen_random_uuid(),
    utilizador_id            UUID                  NOT NULL REFERENCES utilizadores (id),
    curso_id                 INTEGER               NOT NULL REFERENCES cursos (id),
    plano_id                 INTEGER               REFERENCES planos_curriculares (id),
    numero_estudante         VARCHAR(12)           NOT NULL,
    nome_completo            VARCHAR(150)          NOT NULL,
    documento_identificacao  VARCHAR(30)           NOT NULL,
    data_nascimento          DATE                  NOT NULL,
    sexo                     sexo_enum,
    telefone                 VARCHAR(20),
    ano_ingresso             INTEGER               NOT NULL,
    estado                   estado_estudante_enum NOT NULL DEFAULT 'ACTIVO',
    criado_em                TIMESTAMPTZ           NOT NULL DEFAULT now(),
    CONSTRAINT uk_estudantes_utilizador UNIQUE (utilizador_id),
    CONSTRAINT uk_estudantes_numero     UNIQUE (numero_estudante),
    CONSTRAINT uk_estudantes_documento  UNIQUE (documento_identificacao),
    CONSTRAINT ck_estudantes_nascimento CHECK (data_nascimento < CURRENT_DATE)
);

CREATE INDEX ix_estudantes_curso  ON estudantes (curso_id, estado);
CREATE INDEX ix_estudantes_nome   ON estudantes (lower(nome_completo));  -- US-06

-- =============================================================================
-- 5. OFERTA LECTIVA: ANOS, PERIODOS E TURMAS  (RF-26 a RF-28)
-- =============================================================================

CREATE TABLE anos_lectivos (
    id           SERIAL           PRIMARY KEY,
    designacao   VARCHAR(15)      NOT NULL,          -- ex.: '2026'
    data_inicio  DATE             NOT NULL,
    data_fim     DATE             NOT NULL,
    estado       estado_ano_enum  NOT NULL DEFAULT 'PLANEADO',
    CONSTRAINT uk_anos_designacao UNIQUE (designacao),
    CONSTRAINT ck_anos_datas      CHECK (data_fim > data_inicio)
);

-- existe no maximo um ano lectivo ACTIVO (US-12)
CREATE UNIQUE INDEX uk_ano_lectivo_activo
    ON anos_lectivos ((estado = 'ACTIVO')) WHERE estado = 'ACTIVO';

CREATE TABLE periodos_lectivos (
    id                 SERIAL  PRIMARY KEY,
    ano_lectivo_id     INTEGER NOT NULL REFERENCES anos_lectivos (id) ON DELETE CASCADE,
    semestre           INTEGER NOT NULL,
    inscricao_inicio   DATE    NOT NULL,
    inscricao_fim      DATE    NOT NULL,
    notas_inicio       DATE    NOT NULL,
    notas_fim          DATE    NOT NULL,
    CONSTRAINT uk_periodo_ano_semestre UNIQUE (ano_lectivo_id, semestre),
    CONSTRAINT ck_periodo_semestre     CHECK (semestre IN (1, 2)),
    CONSTRAINT ck_periodo_inscricao    CHECK (inscricao_fim >= inscricao_inicio),
    CONSTRAINT ck_periodo_notas        CHECK (notas_fim >= notas_inicio)
);

-- Entidade central da modelacao: a oferta de uma disciplina num periodo lectivo.
-- Sem ela nao seria possivel manter historico academico por ano lectivo.
CREATE TABLE turmas (
    id                  SERIAL      PRIMARY KEY,
    disciplina_id       INTEGER     NOT NULL REFERENCES disciplinas (id),
    periodo_lectivo_id  INTEGER     NOT NULL REFERENCES periodos_lectivos (id),
    docente_id          UUID        NOT NULL REFERENCES docentes (id),
    designacao          VARCHAR(20) NOT NULL DEFAULT 'A',
    capacidade_maxima   INTEGER     NOT NULL,
    criado_em           TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uk_turmas_oferta     UNIQUE (disciplina_id, periodo_lectivo_id, designacao),
    CONSTRAINT ck_turmas_capacidade CHECK (capacidade_maxima > 0)
);

CREATE INDEX ix_turmas_docente ON turmas (docente_id, periodo_lectivo_id);
CREATE INDEX ix_turmas_periodo ON turmas (periodo_lectivo_id);

-- =============================================================================
-- 6. INSCRICOES  (RF-29 a RF-34, RF-44 a RF-47)
-- =============================================================================

CREATE TABLE inscricoes (
    id                    UUID                   PRIMARY KEY DEFAULT gen_random_uuid(),
    estudante_id          UUID                   NOT NULL REFERENCES estudantes (id),
    turma_id              INTEGER                NOT NULL REFERENCES turmas (id),
    data_inscricao        TIMESTAMPTZ            NOT NULL DEFAULT now(),
    estado                estado_inscricao_enum  NOT NULL DEFAULT 'ACTIVA',
    -- resultado consolidado, calculado pelo ServicoAvaliacao (nunca introduzido a mao)
    nota_frequencia       NUMERIC(4,1),
    situacao_frequencia   situacao_freq_enum     NOT NULL DEFAULT 'EM_CURSO',
    nota_exame            NUMERIC(4,1),
    classificacao_final   NUMERIC(4,1),
    situacao_final        situacao_final_enum    NOT NULL DEFAULT 'EM_CURSO',
    registada_por         UUID                   REFERENCES utilizadores (id),
    observacoes           TEXT,
    -- RN-03: um estudante nao se inscreve duas vezes na mesma turma
    CONSTRAINT uk_inscricao_estudante_turma UNIQUE (estudante_id, turma_id),
    CONSTRAINT ck_inscricao_frequencia CHECK (nota_frequencia     IS NULL OR nota_frequencia     BETWEEN 0 AND 20),
    CONSTRAINT ck_inscricao_exame      CHECK (nota_exame          IS NULL OR nota_exame          BETWEEN 0 AND 20),
    CONSTRAINT ck_inscricao_final      CHECK (classificacao_final IS NULL OR classificacao_final BETWEEN 0 AND 20)
);

CREATE INDEX ix_inscricoes_estudante ON inscricoes (estudante_id, estado);
CREATE INDEX ix_inscricoes_turma     ON inscricoes (turma_id, estado);

-- =============================================================================
-- 7. AVALIACOES E CLASSIFICACOES  (RF-35 a RF-43)
-- =============================================================================

CREATE TABLE avaliacoes (
    id             SERIAL                 PRIMARY KEY,
    turma_id       INTEGER                NOT NULL REFERENCES turmas (id) ON DELETE CASCADE,
    designacao     VARCHAR(60)            NOT NULL,
    tipo           tipo_avaliacao_enum    NOT NULL,
    peso           NUMERIC(5,2)           NOT NULL,      -- percentagem; soma = 100 (RN-06)
    data_prevista  DATE,
    estado         estado_avaliacao_enum  NOT NULL DEFAULT 'RASCUNHO',
    CONSTRAINT ck_avaliacoes_peso CHECK (peso > 0 AND peso <= 100)
);

CREATE INDEX ix_avaliacoes_turma ON avaliacoes (turma_id);

CREATE TABLE classificacoes (
    id             UUID                       PRIMARY KEY DEFAULT gen_random_uuid(),
    avaliacao_id   INTEGER                    NOT NULL REFERENCES avaliacoes (id) ON DELETE CASCADE,
    inscricao_id   UUID                       NOT NULL REFERENCES inscricoes (id) ON DELETE CASCADE,
    valor          NUMERIC(4,1),
    faltou         BOOLEAN                    NOT NULL DEFAULT FALSE,   -- RF-43
    estado         estado_classificacao_enum  NOT NULL DEFAULT 'RASCUNHO',
    lancada_por    UUID                       REFERENCES utilizadores (id),
    lancada_em     TIMESTAMPTZ                NOT NULL DEFAULT now(),
    publicada_em   TIMESTAMPTZ,
    CONSTRAINT uk_classificacao_avaliacao_inscricao UNIQUE (avaliacao_id, inscricao_id),
    CONSTRAINT ck_classificacao_valor  CHECK (valor IS NULL OR valor BETWEEN 0 AND 20),   -- RF-38
    -- ou tem valor, ou esta marcada como falta
    CONSTRAINT ck_classificacao_coerente CHECK (faltou = TRUE OR valor IS NOT NULL OR estado = 'RASCUNHO')
);

CREATE INDEX ix_classificacoes_inscricao ON classificacoes (inscricao_id);
CREATE INDEX ix_classificacoes_avaliacao ON classificacoes (avaliacao_id, estado);

-- RN-11: alteracao de nota publicada exige pedido autorizado pelo Coordenador
CREATE TABLE pedidos_rectificacao (
    id                UUID                PRIMARY KEY DEFAULT gen_random_uuid(),
    classificacao_id  UUID                NOT NULL REFERENCES classificacoes (id),
    solicitado_por    UUID                NOT NULL REFERENCES utilizadores (id),
    valor_anterior    NUMERIC(4,1),
    valor_novo        NUMERIC(4,1)        NOT NULL,
    justificacao      TEXT                NOT NULL,
    estado            estado_pedido_enum  NOT NULL DEFAULT 'PENDENTE',
    criado_em         TIMESTAMPTZ         NOT NULL DEFAULT now(),
    decidido_por      UUID                REFERENCES utilizadores (id),
    decidido_em       TIMESTAMPTZ,
    motivo_decisao    TEXT,
    CONSTRAINT ck_rectificacao_justificacao CHECK (char_length(justificacao) >= 20),
    CONSTRAINT ck_rectificacao_valor        CHECK (valor_novo BETWEEN 0 AND 20)
);

CREATE INDEX ix_rectificacoes_estado ON pedidos_rectificacao (estado, criado_em);

-- =============================================================================
-- 8. PAUTAS  (RF-51 a RF-54)
-- =============================================================================

CREATE TABLE pautas (
    id              SERIAL             PRIMARY KEY,
    turma_id        INTEGER            NOT NULL REFERENCES turmas (id),
    numero          VARCHAR(30)        NOT NULL,
    versao          INTEGER            NOT NULL DEFAULT 1,
    estado          estado_pauta_enum  NOT NULL DEFAULT 'PROVISORIA',
    total_inscritos INTEGER,
    total_aprovados INTEGER,
    emitida_por     UUID               REFERENCES utilizadores (id),
    emitida_em      TIMESTAMPTZ        NOT NULL DEFAULT now(),
    homologada_por  UUID               REFERENCES utilizadores (id),
    homologada_em   TIMESTAMPTZ,
    CONSTRAINT uk_pautas_numero        UNIQUE (numero),
    CONSTRAINT uk_pautas_turma_versao  UNIQUE (turma_id, versao)
);

-- =============================================================================
-- 9. NOTIFICACOES (extensao opcional) E AUDITORIA  (RF-61 a RF-67)
-- =============================================================================

CREATE TABLE notificacoes (
    id             UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    utilizador_id  UUID         NOT NULL REFERENCES utilizadores (id) ON DELETE CASCADE,
    tipo           VARCHAR(60)  NOT NULL,
    titulo         VARCHAR(150) NOT NULL,
    mensagem       TEXT,
    referencia     VARCHAR(120),                 -- ex.: 'turma:42'
    lida_em        TIMESTAMPTZ,
    criada_em      TIMESTAMPTZ  NOT NULL DEFAULT now(),
    -- evita notificacoes duplicadas para o mesmo evento e destinatario (US-30)
    CONSTRAINT uk_notificacao_evento UNIQUE (utilizador_id, tipo, referencia)
);

CREATE INDEX ix_notificacoes_nao_lidas ON notificacoes (utilizador_id) WHERE lida_em IS NULL;

-- Tabela apenas de escrita: nao existem endpoints de UPDATE/DELETE (RNF-32)
CREATE TABLE registos_auditoria (
    id              UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    utilizador_id   UUID         REFERENCES utilizadores (id),
    acao            VARCHAR(60)  NOT NULL,       -- ex.: 'classificacao.rectificada'
    entidade        VARCHAR(60)  NOT NULL,
    entidade_id     VARCHAR(60)  NOT NULL,
    valor_anterior  JSONB,
    valor_novo      JSONB,
    justificacao    TEXT,
    endereco_ip     INET,
    criado_em       TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX ix_auditoria_entidade ON registos_auditoria (entidade, entidade_id);
CREATE INDEX ix_auditoria_autor    ON registos_auditoria (utilizador_id, criado_em DESC);

-- =============================================================================
-- 10. VISTAS DE APOIO
-- =============================================================================

-- Aproveitamento academico por inscricao (RF-48, RF-49)
CREATE VIEW vw_aproveitamento AS
SELECT
    e.id                    AS estudante_id,
    e.numero_estudante,
    e.nome_completo,
    c.designacao            AS curso,
    al.designacao           AS ano_lectivo,
    pl.semestre,
    d.codigo                AS codigo_disciplina,
    d.designacao            AS disciplina,
    d.creditos,
    i.nota_frequencia,
    i.situacao_frequencia,
    i.classificacao_final,
    i.situacao_final
FROM inscricoes i
JOIN estudantes         e  ON e.id  = i.estudante_id
JOIN cursos             c  ON c.id  = e.curso_id
JOIN turmas             t  ON t.id  = i.turma_id
JOIN disciplinas        d  ON d.id  = t.disciplina_id
JOIN periodos_lectivos  pl ON pl.id = t.periodo_lectivo_id
JOIN anos_lectivos      al ON al.id = pl.ano_lectivo_id
WHERE i.estado <> 'ANULADA';

-- Media do curso ponderada por creditos (RN-12) e creditos obtidos
CREATE VIEW vw_media_curso AS
SELECT
    i.estudante_id,
    round(
        sum(i.classificacao_final * d.creditos)::numeric
        / nullif(sum(d.creditos), 0), 1
    )                                   AS media_ponderada,
    sum(d.creditos)                     AS creditos_obtidos,
    count(*)                            AS disciplinas_aprovadas
FROM inscricoes i
JOIN turmas      t ON t.id = i.turma_id
JOIN disciplinas d ON d.id = t.disciplina_id
WHERE i.situacao_final = 'APROVADO'
GROUP BY i.estudante_id;

-- Taxa de aprovacao por disciplina e ano lectivo (RF-55)
CREATE VIEW vw_aproveitamento_disciplina AS
SELECT
    al.designacao                                            AS ano_lectivo,
    pl.semestre,
    d.codigo,
    d.designacao                                             AS disciplina,
    count(*)                                                 AS inscritos,
    count(*) FILTER (WHERE i.situacao_final = 'APROVADO')     AS aprovados,
    count(*) FILTER (WHERE i.situacao_final = 'REPROVADO')    AS reprovados,
    round(100.0 * count(*) FILTER (WHERE i.situacao_final = 'APROVADO')
          / nullif(count(*), 0), 1)                          AS taxa_aprovacao,
    round(avg(i.classificacao_final), 1)                     AS media_disciplina
FROM inscricoes i
JOIN turmas             t  ON t.id  = i.turma_id
JOIN disciplinas        d  ON d.id  = t.disciplina_id
JOIN periodos_lectivos  pl ON pl.id = t.periodo_lectivo_id
JOIN anos_lectivos      al ON al.id = pl.ano_lectivo_id
WHERE i.estado <> 'ANULADA'
GROUP BY al.designacao, pl.semestre, d.codigo, d.designacao;

-- =============================================================================
-- 11. RESTRICOES ACTIVAS (TRIGGERS)
-- =============================================================================

-- RN-04: a inscricao nao pode exceder a capacidade da turma.
-- Verificada tambem na camada de dominio; aqui garante-se a integridade em caso
-- de pedidos concorrentes (dois estudantes na ultima vaga).
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

-- RN-11: bloqueia a alteracao directa do valor de uma classificacao publicada.
-- A rectificacao autorizada actualiza o estado para 'RECTIFICADA' na mesma
-- instrucao, unico caso admitido.
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

-- RNF-32: impede alteracao e eliminacao de registos de auditoria
CREATE OR REPLACE FUNCTION fn_auditoria_imutavel()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Os registos de auditoria sao imutaveis (RNF-32)';
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tg_auditoria_imutavel
    BEFORE UPDATE OR DELETE ON registos_auditoria
    FOR EACH ROW EXECUTE FUNCTION fn_auditoria_imutavel();

-- =============================================================================
-- 12. DADOS INICIAIS (perfis e permissoes base)
-- =============================================================================

INSERT INTO perfis (nome, descricao) VALUES
    ('ADMINISTRADOR', 'Gestao global do sistema e dos cadastros'),
    ('COORDENADOR',   'Coordenacao de curso, homologacao de pautas e rectificacoes'),
    ('DOCENTE',       'Gestao de avaliacoes e lancamento de classificacoes'),
    ('ESTUDANTE',     'Inscricao em disciplinas e consulta do aproveitamento');

INSERT INTO permissoes (codigo, descricao) VALUES
    ('utilizador.gerir',        'Criar, editar e desactivar contas'),
    ('perfil.atribuir',         'Atribuir perfis a utilizadores'),
    ('estudante.gerir',         'Gerir cadastro de estudantes'),
    ('docente.gerir',           'Gerir cadastro de docentes'),
    ('curso.gerir',             'Gerir cursos e planos curriculares'),
    ('disciplina.gerir',        'Gerir disciplinas e pre-requisitos'),
    ('turma.gerir',             'Criar e editar turmas'),
    ('inscricao.criar',         'Inscrever-se em disciplinas'),
    ('inscricao.gerir',         'Inscrever ou desinscrever estudantes'),
    ('avaliacao.gerir',         'Definir avaliacoes da turma'),
    ('classificacao.lancar',    'Lancar classificacoes'),
    ('classificacao.publicar',  'Publicar classificacoes'),
    ('rectificacao.solicitar',  'Solicitar rectificacao de nota publicada'),
    ('rectificacao.decidir',    'Aprovar ou recusar pedidos de rectificacao'),
    ('pauta.emitir',            'Emitir pautas'),
    ('pauta.homologar',         'Homologar pautas'),
    ('aproveitamento.ver.proprio', 'Consultar o proprio aproveitamento'),
    ('aproveitamento.ver.curso',   'Consultar aproveitamento dos estudantes do curso'),
    ('relatorio.gerar',         'Gerar relatorios de gestao'),
    ('auditoria.consultar',     'Consultar o registo de auditoria');

-- =============================================================================
-- FIM DO SCRIPT
-- =============================================================================
