import { useState } from 'react'
import type { ColeccaoPaginada } from '@/tipos/api'

interface Props {
  coleccao: Pick<ColeccaoPaginada<unknown>, 'pagina' | 'por_pagina' | 'total' | 'total_paginas'>
  aoMudar: (pagina: number) => void
}

/** Navegação entre páginas de uma lista paginada pelo servidor (10 por página). */
export function Paginacao({ coleccao, aoMudar }: Props) {
  const { pagina, por_pagina, total, total_paginas } = coleccao

  if (total === 0) return null

  const primeiro = (pagina - 1) * por_pagina + 1
  const ultimo = Math.min(pagina * por_pagina, total)

  return (
    <nav aria-label="Paginação" className="mt-3 flex flex-wrap items-center justify-between gap-2 text-sm">
      <p className="text-aco">
        {primeiro}–{ultimo} de {total}
      </p>

      {total_paginas > 1 && (
        <div className="flex items-center gap-1">
          <button
            onClick={() => aoMudar(pagina - 1)}
            disabled={pagina <= 1}
            className="botao-fantasma px-3 py-1 disabled:opacity-40"
          >
            Anterior
          </button>
          {Array.from({ length: total_paginas }, (_, i) => i + 1).map((p) => (
            <button
              key={p}
              onClick={() => aoMudar(p)}
              aria-current={p === pagina ? 'page' : undefined}
              className={p === pagina ? 'botao px-3 py-1' : 'botao-fantasma px-3 py-1'}
            >
              {p}
            </button>
          ))}
          <button
            onClick={() => aoMudar(pagina + 1)}
            disabled={pagina >= total_paginas}
            className="botao-fantasma px-3 py-1 disabled:opacity-40"
          >
            Seguinte
          </button>
        </div>
      )}
    </nav>
  )
}

/**
 * Paginação feita no ecrã, para listas que já chegam completas do servidor
 * (notificações, utilizadores, pauta…): mostra 10 de cada vez.
 */
export function usePaginacaoLocal<T>(itens: T[] | null | undefined, porPagina = 10) {
  const [pagina, setPagina] = useState(1)
  const lista = itens ?? []
  const totalPaginas = Math.max(1, Math.ceil(lista.length / porPagina))
  // Se a lista encolher (filtro, eliminação), não ficar numa página vazia.
  const actual = Math.min(pagina, totalPaginas)

  return {
    visiveis: lista.slice((actual - 1) * porPagina, actual * porPagina),
    coleccao: { pagina: actual, por_pagina: porPagina, total: lista.length, total_paginas: totalPaginas },
    setPagina,
  }
}
