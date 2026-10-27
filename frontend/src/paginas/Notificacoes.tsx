import { useEffect, useState } from 'react'
import { api } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import { Paginacao, usePaginacaoLocal } from '@/componentes/Paginacao'
import type { Notificacao } from '@/tipos/api'

/** Notificações do utilizador autenticado (RF-67, US-29). */
export function Notificacoes() {
  const [notificacoes, setNotificacoes] = useState<Notificacao[] | null>(null)
  const [erro, setErro] = useState<string | null>(null)

  const pgNotificacoes = usePaginacaoLocal(notificacoes)
  useEffect(() => {
    carregar()
  }, [])

  function carregar() {
    api.get<Notificacao[]>('/notificacoes').then(setNotificacoes).catch(() => setErro('Não foi possível carregar as notificações.'))
  }

  async function marcarLida(notificacao: Notificacao) {
    if (notificacao.lida_em) return
    await api.patch(`/notificacoes/${notificacao.id}/lida`).catch(() => undefined)
    carregar()
  }

  return (
    <div>
      <h1 className="titulo-pagina mb-6">Notificações</h1>

      {erro && <Alerta>{erro}</Alerta>}

      {notificacoes === null && !erro && <p className="text-sm text-aco">A carregar…</p>}

      {notificacoes && (
        <div className="space-y-2">
          {pgNotificacoes.visiveis.map((n) => (
            <button
              key={n.id}
              onClick={() => void marcarLida(n)}
              className={`block w-full border p-4 text-left transition ${
                n.lida_em ? 'border-linha bg-superficie' : 'border-dourado bg-superficie-alt'
              }`}
            >
              <div className="flex items-center justify-between">
                <p className="text-sm font-medium text-texto">{n.titulo}</p>
                <span className="text-xs text-aco-suave">{new Date(n.criada_em).toLocaleString('pt-PT')}</span>
              </div>
              {n.mensagem && <p className="mt-1 text-sm text-aco">{n.mensagem}</p>}
            </button>
          ))}
          {notificacoes.length === 0 && <p className="text-sm text-aco">Sem notificações.</p>}
          <Paginacao coleccao={pgNotificacoes.coleccao} aoMudar={pgNotificacoes.setPagina} />
        </div>
      )}
    </div>
  )
}
