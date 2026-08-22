import { FormEvent, useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'
import { Alerta } from '@/componentes/Alerta'
import type { PedidoRectificacao } from '@/tipos/api'

const NOVO_PEDIDO = { classificacao_id: '', valor_novo: '', justificacao: '' }

const ESTILO_ESTADO: Record<PedidoRectificacao['estado'], string> = {
  PENDENTE: 'selo-aviso',
  APROVADO: 'selo-sucesso',
  RECUSADO: 'selo-perigo',
}

/** Solicitação e decisão de rectificações de nota (UC23, UC24 / US-19). */
export function Rectificacoes() {
  const { temPerfil } = useAutenticacao()
  const [pedidos, setPedidos] = useState<PedidoRectificacao[] | null>(null)
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(NOVO_PEDIDO)
  const [motivos, setMotivos] = useState<Record<string, string>>({})
  const [erro, setErro] = useState<string | null>(null)

  const podeDecidir = temPerfil('ADMINISTRADOR', 'COORDENADOR', 'DOCENTE')

  useEffect(() => {
    carregar()
  }, [])

  function carregar() {
    api.get<PedidoRectificacao[]>('/rectificacoes').then(setPedidos).catch(() => setErro('Não foi possível carregar os pedidos.'))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)

    try {
      await api.post('/rectificacoes', { ...formulario, valor_novo: Number(formulario.valor_novo) })
      setFormulario(NOVO_PEDIDO)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível solicitar a rectificação.')
    }
  }

  async function decidir(pedido: PedidoRectificacao, decisao: 'APROVAR' | 'RECUSAR') {
    setErro(null)

    try {
      await api.post(`/rectificacoes/${pedido.id}/decisao`, { decisao, motivo: motivos[pedido.id] })
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível registar a decisão.')
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="titulo-pagina">Rectificações</h1>
          <p className="text-sm text-aco">Pedidos de correcção de notas já publicadas.</p>
        </div>
        <button onClick={() => setAMostrarFormulario((v) => !v)} className="botao">
          {aMostrarFormulario ? 'Cancelar' : 'Solicitar rectificação'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="cartao mb-6 grid grid-cols-2 gap-3 p-4">
          <input required placeholder="ID da classificação" value={formulario.classificacao_id}
            onChange={(e) => setFormulario({ ...formulario, classificacao_id: e.target.value })}
            className="entrada col-span-2" />
          <input required type="number" min={0} max={20} step="0.1" placeholder="Valor novo"
            value={formulario.valor_novo}
            onChange={(e) => setFormulario({ ...formulario, valor_novo: e.target.value })}
            className="entrada" />
          <textarea required minLength={20} placeholder="Justificação (mínimo 20 caracteres)" value={formulario.justificacao}
            onChange={(e) => setFormulario({ ...formulario, justificacao: e.target.value })}
            className="entrada col-span-2" rows={2} />
          <button type="submit" className="botao col-span-2 mt-2">
            Solicitar
          </button>
        </form>
      )}

      {pedidos === null && <p className="text-sm text-aco">A carregar…</p>}

      {pedidos && (
        <div className="space-y-3">
          {pedidos.map((p) => (
            <div key={p.id} className="cartao p-4">
              <div className="mb-2 flex items-center justify-between">
                <p className="text-sm font-medium text-texto">{p.estudante}</p>
                <span className={`selo ${ESTILO_ESTADO[p.estado]}`}>{p.estado}</span>
              </div>
              <p className="mb-1 text-sm text-texto">
                {p.valor_anterior} → {p.valor_novo}
              </p>
              <p className="mb-3 text-sm text-aco">{p.justificacao}</p>

              {podeDecidir && p.estado === 'PENDENTE' && (
                <div className="flex items-end gap-2">
                  <input
                    placeholder="Motivo (obrigatório se recusar)"
                    value={motivos[p.id] ?? ''}
                    onChange={(e) => setMotivos({ ...motivos, [p.id]: e.target.value })}
                    className="entrada flex-1 py-1.5"
                  />
                  <button onClick={() => void decidir(p, 'APROVAR')} className="border border-sucesso bg-sucesso-bg px-3 py-1.5 text-sm font-semibold text-sucesso hover:bg-sucesso hover:text-white">
                    Aprovar
                  </button>
                  <button onClick={() => void decidir(p, 'RECUSAR')} className="border border-perigo bg-perigo-bg px-3 py-1.5 text-sm font-semibold text-perigo hover:bg-perigo hover:text-white">
                    Recusar
                  </button>
                </div>
              )}
            </div>
          ))}
          {pedidos.length === 0 && <p className="text-sm text-aco">Sem pedidos de rectificação.</p>}
        </div>
      )}
    </div>
  )
}
