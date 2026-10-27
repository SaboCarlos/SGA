import type { ReactNode } from 'react'
import { numero } from './paleta'

export interface LinhaBarra {
  rotulo: string
  /** Segunda linha do rótulo (código da disciplina, turma, …). */
  detalhe?: string
  valor: number
  /** Texto que substitui o valor na ponta da barra (ex.: "20 / 25"). */
  texto?: string
  /** Selo à direita, para o que a barra não consegue dizer (ex.: "Esgotada"). */
  selo?: ReactNode
}

interface Props {
  linhas: LinhaBarra[]
  /** Topo da escala. Por omissão, o maior valor da série. */
  maximo?: number
  /** Linha de referência vertical (ex.: nota mínima de aprovação). */
  referencia?: { valor: number; rotulo: string }
  /** Unidade acrescentada ao valor na ponta da barra. */
  unidade?: string
}

/**
 * Barras horizontais, uma série, uma cor.
 *
 * Horizontais e não verticais porque os rótulos são nomes de disciplinas e
 * de turmas: na vertical seriam inclinados ou truncados.
 *
 * Uma série leva uma cor só. Pintar cada barra de um tom diferente conforme
 * o tamanho gastaria o único canal livre a repetir aquilo que o comprimento
 * da barra já diz — e a legenda resultante não acrescentaria nada.
 *
 * Sem grelha: cada barra traz o valor na ponta, e rótulos directos tornam a
 * grelha redundante.
 */
export function GraficoBarras({ linhas, maximo, referencia, unidade = '' }: Props) {
  const topo = Math.max(maximo ?? 0, ...linhas.map((l) => l.valor), 1)

  return (
    <ul className="space-y-2.5">
      {linhas.map((linha) => {
        const largura = Math.max(0, Math.min(100, (linha.valor / topo) * 100))

        return (
          <li key={`${linha.rotulo}-${linha.detalhe ?? ''}`} className="grid grid-cols-[minmax(0,9rem)_1fr] items-center gap-3">
            <div className="min-w-0">
              <p className="truncate text-sm leading-tight text-texto" title={linha.rotulo}>
                {linha.rotulo}
              </p>
              {linha.detalhe && (
                <p className="truncate text-xs leading-tight text-aco-suave">{linha.detalhe}</p>
              )}
            </div>

            <div className="flex min-w-0 items-center gap-2">
              {/* O carril é o fundo da escala, não um valor: fica um passo
                  acima da superfície para se ler como "o resto". */}
              <div className="relative h-4 min-w-0 flex-1 bg-superficie-alt">
                <div
                  className="h-full transition-[width]"
                  style={{
                    width: `${largura}%`,
                    background: 'var(--serie-1)',
                    // Ponta arredondada, base esquerda a direito: a barra
                    // cresce a partir de uma linha de base, e essa linha é
                    // recta.
                    borderRadius: '0 4px 4px 0',
                  }}
                />
                {referencia && (
                  <span
                    aria-hidden
                    className="absolute inset-y-[-3px] w-px bg-aco-suave"
                    style={{ left: `${Math.min(100, (referencia.valor / topo) * 100)}%` }}
                    title={referencia.rotulo}
                  />
                )}
              </div>

              <span className="w-16 shrink-0 text-right text-sm tabular-nums text-texto">
                {linha.texto ?? `${numero(linha.valor, Number.isInteger(linha.valor) ? 0 : 1)}${unidade}`}
              </span>

              {linha.selo && <span className="shrink-0">{linha.selo}</span>}
            </div>
          </li>
        )
      })}

      {referencia && (
        <li className="flex items-center gap-2 pt-1 text-xs text-aco-suave">
          <span aria-hidden className="h-3 w-px bg-aco-suave" />
          {referencia.rotulo}
        </li>
      )}
    </ul>
  )
}
