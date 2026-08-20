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
  id: string
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
}

export type EstadoPedido = 'PENDENTE' | 'APROVADO' | 'RECUSADO'

export interface PedidoRectificacao {
  id: string
  classificacao_id: string
  estudante: string
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
  linhas: InscritoTurma[]
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
