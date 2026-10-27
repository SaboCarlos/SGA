import { numero, percentagem } from './paleta'

export interface SerieEmpilhada {
  chave: string
  rotulo: string
  cor: string
}

export interface LinhaEmpilhada {
  rotulo: string
  detalhe?: string
  valores: Record<string, number>
}

interface Props {
  series: SerieEmpilhada[]
  linhas: LinhaEmpilhada[]
}

/**
 * Barras empilhadas horizontais — a forma certa para parte-todo repetido
 * por categoria (aqui: como se reparte cada disciplina entre aprovados, em
 * curso e reprovados).
 *
 * A escala é absoluta e partilhada (a barra mais longa é a disciplina com
 * mais inscritos), não 100% por linha: assim uma disciplina com 5 inscritos
 * não aparece do mesmo tamanho que uma com 25. A percentagem, que é a outra
 * leitura útil, fica no fim de cada barra.
 *
 * Os segmentos não são rotulados por dentro: a maior parte deles é estreita
 * demais para caber texto, e um rótulo cortado a meio é pior do que rótulo
 * nenhum. A identidade vem da legenda, o valor exacto vem do cursor e da
 * tabela por baixo — nenhum valor fica refém do rato.
 */
export function GraficoBarrasEmpilhadas({ series, linhas }: Props) {
  const maximo = Math.max(
    1,
    ...linhas.map((l) => series.reduce((soma, s) => soma + (l.valores[s.chave] ?? 0), 0)),
  )

  return (
    <div>
      <ul className="mb-4 flex flex-wrap gap-x-4 gap-y-1">
        {series.map((serie) => (
          <li key={serie.chave} className="flex items-center gap-1.5 text-xs text-aco">
            <span aria-hidden className="h-2.5 w-2.5 shrink-0" style={{ background: serie.cor }} />
            {serie.rotulo}
          </li>
        ))}
      </ul>

      <ul className="space-y-2.5">
        {linhas.map((linha) => {
          const total = series.reduce((soma, s) => soma + (linha.valores[s.chave] ?? 0), 0)
          const aprovados = linha.valores[series[0].chave] ?? 0

          return (
            <li
              key={`${linha.rotulo}-${linha.detalhe ?? ''}`}
              className="grid grid-cols-[minmax(0,9rem)_1fr] items-center gap-3"
            >
              <div className="min-w-0">
                <p className="truncate text-sm leading-tight text-texto" title={linha.rotulo}>
                  {linha.rotulo}
                </p>
                {linha.detalhe && (
                  <p className="truncate text-xs leading-tight text-aco-suave">{linha.detalhe}</p>
                )}
              </div>

              <div className="flex min-w-0 items-center gap-2">
                <div className="flex h-4 min-w-0 flex-1 bg-superficie-alt">
                  {series.map((serie, indice) => {
                    const valor = linha.valores[serie.chave] ?? 0
                    if (valor <= 0) return null

                    return (
                      <div
                        key={serie.chave}
                        className="h-full"
                        style={{
                          width: `${(valor / maximo) * 100}%`,
                          background: serie.cor,
                          // Folga de 2px na cor da superfície entre segmentos
                          // encostados — separa sem acrescentar contorno.
                          marginRight: indice < series.length - 1 ? 2 : 0,
                        }}
                        title={`${linha.rotulo} · ${serie.rotulo}: ${numero(valor)} de ${numero(total)}`}
                      />
                    )
                  })}
                </div>

                <span
                  className="w-14 shrink-0 text-right text-sm tabular-nums text-texto"
                  title={`${numero(aprovados)} aprovados em ${numero(total)} inscritos`}
                >
                  {percentagem(aprovados, total)}
                </span>
              </div>
            </li>
          )
        })}
      </ul>

      <p className="mt-3 text-xs text-aco-suave">
        A percentagem à direita é a taxa de aprovação sobre os inscritos.
      </p>
    </div>
  )
}
