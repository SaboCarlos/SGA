/**
 * Tipos partilhados pelo frontend, espelhando os esquemas de
 * docs/openapi.yaml (Fase 3). Mantidos à mão nesta fase inicial; podem vir
 * a ser gerados automaticamente a partir da especificação mais tarde.
 */

export type Perfil = 'ADMINISTRADOR' | 'COORDENADOR' | 'DOCENTE' | 'ESTUDANTE'

export interface Utilizador {
  id: string
  nome: string
  email: string
  perfis: Perfil[]
  permissoes: string[]
}

export interface Turma {
  id: number
  designacao: string
  disciplina: { id: number; codigo: string; designacao: string; creditos: number }
  ano_lectivo: string
  semestre: number
  docente: string
  capacidade_maxima: number
  inscritos: number
  vagas: number
}

export type SituacaoFrequencia =
  | 'EM_CURSO'
  | 'DISPENSADO'
  | 'ADMITIDO_EXAME'
  | 'REPROVADO_FREQUENCIA'

export type SituacaoFinal = 'EM_CURSO' | 'APROVADO' | 'REPROVADO'

export interface Inscricao {
  id: string
  turma: Turma
  data_inscricao: string
  estado: 'ACTIVA' | 'ANULADA' | 'CONCLUIDA'
  nota_frequencia: number | null
  situacao_frequencia: SituacaoFrequencia
  classificacao_final: number | null
  situacao_final: SituacaoFinal
}

export interface ComprovativoInscricao {
  emitido_em: string
  estudante: string
  ano_lectivo: string
  semestre: number
  disciplinas: Array<{ codigo: string; designacao: string; turma: string }>
}

/** Erro de regra de negócio, como definido em docs/openapi.yaml (ErroNegocio). */
export interface ErroNegocioApi {
  codigo: string
  mensagem: string
  campo?: string
}

export interface ColeccaoPaginada<T> {
  pagina: number
  por_pagina: number
  total: number
  total_paginas: number
  dados: T[]
}

export type EstadoEstudante = 'ACTIVO' | 'SUSPENSO' | 'TRANSFERIDO' | 'GRADUADO' | 'DESISTENTE'

export interface Curso {
  id: number
  codigo: string
  designacao: string
  grau: string
  duracao_anos: number
  total_creditos: number
  regulamento_avaliacao: string
}

export interface Estudante {
  id: string
  numero_estudante: string
  nome_completo: string
  curso: Curso | null
  ano_ingresso: number
  estado: EstadoEstudante
}

export interface Disciplina {
  id: number
  codigo: string
  designacao: string
  creditos: number
  carga_horaria: number
}

export interface ItemPlano {
  id: number
  disciplina_id: number
  disciplina: string
  ano_curricular: number
  semestre: number
  tipo: 'OBRIGATORIA' | 'OPCIONAL'
}

export interface Docente {
  id: string
  codigo: string
  nome_completo: string
  grau_academico: string | null
  categoria: string | null
  area_cientifica: string | null
  activo: boolean
}

export interface InscritoTurma {
  inscricao_id: string
  numero_estudante: string
  nome_completo: string
  nota_frequencia: number | null
  situacao_frequencia: SituacaoFrequencia
}

export type TipoAvaliacao = 'TESTE' | 'TRABALHO' | 'PRATICA' | 'EXAME'
export type EstadoAvaliacao = 'RASCUNHO' | 'ACTIVA' | 'PUBLICADA'

export interface Avaliacao {
  id: number
  designacao: string
  tipo: TipoAvaliacao
  peso: number
  data_prevista: string | null
  estado: EstadoAvaliacao
}

export interface EstruturaAvaliacao {
  soma_pesos: number
  estrutura_valida: boolean
  avaliacoes: Avaliacao[]
}

export interface Classificacao {
  /** null enquanto ainda não foi lançada nota para este estudante. */
  id: string | null
  inscricao_id: string
  numero_estudante: string
  nome_completo: string
  valor: number | null
  faltou: boolean
  estado: 'RASCUNHO' | 'PUBLICADA' | 'RECTIFICADA'
}

export interface ResumoPublicacao {
  publicadas: number
  dispensados: number
  admitidos_exame: number
  reprovados_frequencia: number
  media_turma: number | null
  /** Falso enquanto houver testes/trabalhos por publicar: a frequência ainda não se calcula. */
  frequencia_calculada: boolean
}

export type EstadoPedido = 'PENDENTE' | 'APROVADO' | 'RECUSADO'

export interface PedidoRectificacao {
  id: string
  classificacao_id: string
  estudante: string
  disciplina: string
  avaliacao: string
  /** O utilizador autenticado pode aprovar/recusar este pedido (Coordenador do curso ou Administrador). */
  pode_decidir: boolean
  valor_anterior: number
  valor_novo: number
  justificacao: string
  estado: EstadoPedido
  solicitado_por: string | null
  decidido_por: string | null
  decidido_em: string | null
}

export interface Pauta {
  id: number
  numero: string
  versao: number
  estado: 'PROVISORIA' | 'EMITIDA' | 'HOMOLOGADA'
  turma: { id: number; designacao: string; disciplina?: string }
  emitida_em: string | null
  total_inscritos: number
  total_aprovados: number
  taxa_aprovacao: number
  avaliacoes: Array<Pick<Avaliacao, 'id' | 'designacao' | 'tipo' | 'peso' | 'estado'>>
  linhas: LinhaPauta[]
}

export interface LinhaPauta extends InscritoTurma {
  /** Nota de cada avaliação, pelo id da avaliação; publicada=false é rascunho do docente. */
  notas: Record<string, { valor: number | null; faltou: boolean; publicada: boolean }>
  nota_exame: number | null
  classificacao_final: number | null
  situacao_final: SituacaoFinal
}

export interface LinhaRelatorioDisciplina {
  codigo: string
  disciplina: string
  ano_lectivo: string
  semestre: number
  inscritos: number
  aprovados: number
  reprovados: number
  taxa_aprovacao: number
  media_disciplina: number | null
}

export type EstadoAnoLectivo = 'PLANEADO' | 'ACTIVO' | 'ENCERRADO'

export interface AnoLectivo {
  id: number
  designacao: string
  data_inicio: string
  data_fim: string
  estado: EstadoAnoLectivo
}

export interface PeriodoLectivo {
  id: number
  semestre: number
  inscricao_inicio: string
  inscricao_fim: string
  notas_inicio: string
  notas_fim: string
  inscricoes_abertas: boolean
}

export interface Notificacao {
  id: string
  tipo: string
  titulo: string
  mensagem: string | null
  criada_em: string
  lida_em: string | null
}

export interface PermissaoCatalogo {
  id: number
  codigo: string
  descricao: string | null
}

/**
 * O perfil como recurso geríve (catálogo + permissões atribuídas) — distinto
 * do tipo `Perfil` acima, que é só a etiqueta usada para filtrar a interface
 * por perfil do utilizador autenticado.
 */
export interface PerfilDetalhe {
  id: number
  nome: string
  descricao: string | null
  permissoes: Array<{ id: number; codigo: string }>
}

export interface Aproveitamento {
  estudante: string
  numero_estudante: string
  curso: string
  media_ponderada: number | null
  creditos_obtidos: number
  creditos_curso: number
  percentagem_conclusao: number
  anos_lectivos: Array<{
    ano_lectivo: string
    semestre: number
    disciplinas: Array<{
      id: string
      disciplina: string
      codigo: string
      nota_frequencia: number | null
      situacao_frequencia: SituacaoFrequencia
      classificacao_final: number | null
      situacao_final: SituacaoFinal
    }>
  }>
}

/** Resposta de GET /notas: só as notas do estudante autenticado. */
export interface NotasEstudante {
  estudante: string
  numero_estudante: string
  curso: string | null
  ano_lectivo_actual: string | null
  /** Ano do curso em que o estudante está (1.º, 2.º…). */
  ano_curricular: number | null
  disciplinas: Array<{
    inscricao_id: string
    ano_curricular: number | null
    codigo: string
    disciplina: string
    turma: string
    docente: string | null
    ano_lectivo: string
    semestre: number
    nota_frequencia: number | null
    situacao_frequencia: SituacaoFrequencia
    classificacao_final: number | null
    situacao_final: SituacaoFinal
    avaliacoes: Array<{
      id: number
      designacao: string
      tipo: Avaliacao['tipo']
      peso: number
      data_prevista: string | null
      /** Falso enquanto o docente não publicar: o valor ainda não é conhecido. */
      publicada: boolean
      classificacao_id: string | null
      valor: number | null
      faltou: boolean
    }>
  }>
}

export interface RegistoAuditoria {
  id: string
  utilizador: string | null
  acao: string
  entidade: string
  entidade_id: string
  valor_anterior: Record<string, unknown> | null
  valor_novo: Record<string, unknown> | null
  justificacao: string | null
  criado_em: string
}

/* ------------------------------------------------------------------ */
/* Painel inicial (GET /painel, UC30)                                   */
/* ------------------------------------------------------------------ */

/** Uma classe de uma distribuição — o formato de todas as séries de anel. */
export interface ContagemPorClasse {
  /** Valor do enum do domínio (ACTIVO, EM_CURSO, …), não texto de interface. */
  rotulo: string
  total: number
}

export interface OcupacaoTurma {
  turma: string
  disciplina: string | null
  codigo: string | null
  inscritos: number
  capacidade: number
  /** Percentagem já calculada no servidor, para não divergir do relatório. */
  ocupacao: number
}

export interface AproveitamentoDisciplina {
  codigo: string
  disciplina: string
  inscritos: number
  aprovados: number
  reprovados: number
  em_curso: number
}

export interface NotaDisciplina {
  codigo: string
  disciplina: string
  nota: number
  situacao: SituacaoFinal
}

export interface CursoCoordenado {
  curso: string
  total_estudantes: number
  inscricoes_activas: number
  rectificacoes_pendentes: number
}

/**
 * Resposta de GET /painel. Praticamente tudo é opcional: o servidor devolve
 * apenas as chaves que fazem sentido para os perfis do utilizador
 * autenticado, e um utilizador com vários perfis recebe a união delas.
 */
export interface DadosPainel {
  // Estudante
  disciplinas_inscritas?: number
  disciplinas_aprovadas?: number
  estado?: EstadoEstudante
  situacao_disciplinas?: ContagemPorClasse[]
  notas_por_disciplina?: NotaDisciplina[]

  // Docente
  turmas_atribuidas?: number
  avaliacoes_por_publicar?: number
  avaliacoes_por_estado?: ContagemPorClasse[]
  ocupacao_minhas_turmas?: OcupacaoTurma[]

  // Coordenador (US-23)
  cursos_coordenados?: CursoCoordenado[]

  // Administrador e Coordenador (âmbito institucional ou dos cursos coordenados)
  total_estudantes?: number
  total_inscricoes_activas?: number
  total_turmas?: number
  estudantes_por_estado?: ContagemPorClasse[]
  inscricoes_por_situacao?: ContagemPorClasse[]
  ocupacao_turmas?: OcupacaoTurma[]
  aproveitamento_disciplinas?: AproveitamentoDisciplina[]
}
