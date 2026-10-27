import type { ReactNode } from 'react'

interface Props {
  titulo: string
  descricao?: string
  /** Contagem total; quando é 0 mostra-se o estado vazio em vez do gráfico. */
  total?: number
  accao?: ReactNode
  children: ReactNode
}

/**
 * Moldura comum a todos os gráficos: título, uma linha de contexto e o
 * estado vazio.
 *
 * O estado vazio é tratado aqui, e não em cada gráfico, porque um gráfico
 * sem dados não deve desenhar eixos e uma legenda vazios — isso lê-se como
 * "o valor é zero" quando o que se passa é "ainda não há nada registado".
 */
export function CartaoGrafico({ titulo, descricao, total, accao, children }: Props) {
  const vazio = total !== undefined && total <= 0

  return (
    <section className="cartao flex flex-col">
      <header className="flex items-start justify-between gap-3 border-b border-linha px-4 py-3">
        <div className="min-w-0">
          <h2 className="font-display text-base font-semibold text-texto">{titulo}</h2>
          {descricao && <p className="mt-0.5 text-xs text-aco">{descricao}</p>}
        </div>
        {accao}
      </header>

      <div className="flex flex-1 flex-col p-4">
        {vazio ? (
          <p className="py-8 text-center text-sm text-aco">Ainda não há dados para mostrar aqui.</p>
        ) : (
          children
        )}
      </div>
    </section>
  )
}
