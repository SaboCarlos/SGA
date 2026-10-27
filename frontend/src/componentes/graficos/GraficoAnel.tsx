import { useState } from 'react'
import { numero, percentagem } from './paleta'

export interface FatiaAnel {
  rotulo: string
  total: number
  cor: string
}

interface Props {
  fatias: FatiaAnel[]
  /** Legenda do valor ao centro quando nada está sob o cursor. */
  legendaCentro: string
}

const RAIO = 38
const ESPESSURA = 13
const PERIMETRO = 2 * Math.PI * RAIO

/** Folga de 2px entre fatias, na mesma cor da superfície (ver nota abaixo). */
const FOLGA = 2

/**
 * Anel (gráfico circular) para relações parte-todo com poucas classes.
 *
 * Porquê anel e não fatias cheias: o buraco no meio é onde vive o total, e
 * o total é a pergunta mais frequente sobre um parte-todo ("de quantos
 * estamos a falar?"). Com o círculo cheio esse número teria de ir para
 * fora do gráfico.
 *
 * As fatias são separadas por uma folga de 2px em vez de um contorno: um
 * contorno acrescenta tinta que não é dado e escurece o gráfico todo;
 * o vazio separa igualmente bem.
 *
 * Passar o cursor (ou o teclado, pela legenda) por uma fatia troca o
 * número do centro pelo dessa classe. A legenda mostra sempre o valor e a
 * percentagem de todas as classes — o gráfico nunca esconde um valor atrás
 * do cursor, e nunca depende só da cor para dizer o que é o quê.
 */
export function GraficoAnel({ fatias, legendaCentro }: Props) {
  const [activa, setActiva] = useState<number | null>(null)

  const total = fatias.reduce((soma, f) => soma + f.total, 0)
  const visiveis = fatias.filter((f) => f.total > 0)
  const emDestaque = activa !== null ? fatias[activa] : null

  let percorrido = 0

  return (
    <div className="flex flex-col items-center gap-5 sm:flex-row sm:items-center sm:gap-6">
      <div className="relative shrink-0" style={{ width: 168, height: 168 }}>
        <svg
          viewBox="0 0 100 100"
          className="h-full w-full -rotate-90"
          role="img"
          aria-label={`${legendaCentro}: ${fatias.map((f) => `${f.rotulo} ${f.total}`).join(', ')}`}
        >
          {/* Carril: mantém o anel com forma mesmo quando só há uma classe. */}
          <circle
            cx="50"
            cy="50"
            r={RAIO}
            fill="none"
            stroke="var(--superficie-alt)"
            strokeWidth={ESPESSURA}
          />

          {fatias.map((fatia, indice) => {
            if (fatia.total <= 0) return null

            const comprimento = (fatia.total / total) * PERIMETRO
            // Uma classe sozinha ocupa o anel inteiro: aí a folga não separa
            // nada de nada e só abriria um golpe no círculo.
            const folga = visiveis.length > 1 ? Math.min(FOLGA, comprimento / 2) : 0
            const traco = Math.max(0.5, comprimento - folga)
            const deslocamento = -percorrido

            percorrido += comprimento

            const esbatida = activa !== null && activa !== indice

            return (
              <circle
                key={fatia.rotulo}
                cx="50"
                cy="50"
                r={RAIO}
                fill="none"
                stroke={fatia.cor}
                strokeWidth={ESPESSURA}
                strokeDasharray={`${traco} ${PERIMETRO - traco}`}
                strokeDashoffset={deslocamento}
                opacity={esbatida ? 0.3 : 1}
                className="cursor-default transition-opacity"
                onMouseEnter={() => setActiva(indice)}
                onMouseLeave={() => setActiva(null)}
              >
                <title>{`${fatia.rotulo}: ${numero(fatia.total)} (${percentagem(fatia.total, total)})`}</title>
              </circle>
            )
          })}
        </svg>

        {/* O valor ao centro é texto HTML, não <text> do SVG: assim herda a
            tipografia da aplicação em vez de ser esticado pelo viewBox. */}
        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center px-6 text-center">
          <span className="text-2xl font-semibold leading-none text-texto">
            {numero(emDestaque ? emDestaque.total : total)}
          </span>
          <span className="mt-1 text-[0.7rem] leading-tight text-aco">
            {emDestaque ? emDestaque.rotulo : legendaCentro}
          </span>
        </div>
      </div>

      <ul className="w-full min-w-0 space-y-1">
        {fatias.map((fatia, indice) => (
          <li key={fatia.rotulo}>
            <button
              type="button"
              onMouseEnter={() => setActiva(indice)}
              onMouseLeave={() => setActiva(null)}
              onFocus={() => setActiva(indice)}
              onBlur={() => setActiva(null)}
              className={`flex w-full items-center gap-2 px-1.5 py-1 text-left transition ${
                activa === indice ? 'bg-superficie-alt' : ''
              }`}
            >
              <span
                aria-hidden
                className="h-2.5 w-2.5 shrink-0"
                style={{ background: fatia.cor, opacity: fatia.total > 0 ? 1 : 0.35 }}
              />
              <span className="min-w-0 flex-1 truncate text-sm text-texto">{fatia.rotulo}</span>
              <span className="shrink-0 text-sm tabular-nums text-texto">{numero(fatia.total)}</span>
              <span className="w-12 shrink-0 text-right text-xs tabular-nums text-aco-suave">
                {percentagem(fatia.total, total)}
              </span>
            </button>
          </li>
        ))}
      </ul>
    </div>
  )
}
