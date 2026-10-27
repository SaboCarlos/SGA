/**
 * Vocabulario partilhado pelos graficos do painel.
 *
 * As cores vivem em src/index.css (--serie-1..5) para mudarem de tema sem
 * JavaScript; aqui ficam apenas os *papeis*: que classe do dominio usa que
 * ranhura. A regra que isto serve e "a cor segue a entidade, nunca a sua
 * posicao": APROVADO e sempre --serie-3, esteja em primeiro ou em ultimo
 * lugar, esteja sozinho ou acompanhado. Sem isto, filtrar ou reordenar uma
 * serie repintava as restantes e o leitor que aprendeu "verde = aprovado"
 * passava a ser enganado.
 */

/** As cinco ranhuras, pela ordem em que foram validadas (ver index.css). */
export const SERIES = [
  'var(--serie-1)',
  'var(--serie-2)',
  'var(--serie-3)',
  'var(--serie-4)',
  'var(--serie-5)',
] as const

export function corDaSerie(indice: number): string {
  return SERIES[indice % SERIES.length]
}

/** Situacao final da inscricao (situacao_final_enum). */
export const COR_SITUACAO: Record<string, string> = {
  EM_CURSO: 'var(--serie-1)',
  APROVADO: 'var(--serie-3)',
  REPROVADO: 'var(--serie-2)',
}

/** Estado da matricula (estado_estudante_enum). */
export const COR_ESTADO_ESTUDANTE: Record<string, string> = {
  ACTIVO: 'var(--serie-1)',
  SUSPENSO: 'var(--serie-2)',
  TRANSFERIDO: 'var(--serie-3)',
  GRADUADO: 'var(--serie-4)',
  DESISTENTE: 'var(--serie-5)',
}

/** Estado da avaliacao (estado_avaliacao_enum). */
export const COR_ESTADO_AVALIACAO: Record<string, string> = {
  RASCUNHO: 'var(--serie-1)',
  ACTIVA: 'var(--serie-2)',
  PUBLICADA: 'var(--serie-3)',
}

/**
 * Rotulos legiveis para os valores dos enums. A API devolve o valor do
 * dominio (ACTIVO, EM_CURSO); a interface nunca o mostra em bruto.
 */
export const ROTULO: Record<string, string> = {
  ACTIVO: 'Activo',
  SUSPENSO: 'Suspenso',
  TRANSFERIDO: 'Transferido',
  GRADUADO: 'Graduado',
  DESISTENTE: 'Desistente',
  EM_CURSO: 'Em curso',
  APROVADO: 'Aprovado',
  REPROVADO: 'Reprovado',
  RASCUNHO: 'Rascunho',
  ACTIVA: 'Activa',
  PUBLICADA: 'Publicada',
}

export function rotular(valor: string): string {
  return ROTULO[valor] ?? valor
}

/** Formatacao pt-PT, com o separador decimal correcto (vírgula). */
export function numero(valor: number, casas = 0): string {
  return valor.toLocaleString('pt-PT', { minimumFractionDigits: casas, maximumFractionDigits: casas })
}

export function percentagem(parte: number, total: number): string {
  if (total <= 0) return '0%'

  return `${numero((parte / total) * 100, parte === total || parte === 0 ? 0 : 1)}%`
}
