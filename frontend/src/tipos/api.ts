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
