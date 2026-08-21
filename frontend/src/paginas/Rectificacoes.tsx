import { FormEvent, useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'
import { Alerta } from '@/componentes/Alerta'
import type { PedidoRectificacao } from '@/tipos/api'

const NOVO_PEDIDO = { classificacao_id: '', valor_novo: '', justificacao: '' }

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
          <h1 className="text-lg font-semibold text-slate-800">Rectificações</h1>
          <p className="text-sm text-slate-500">Pedidos de correcção de notas já publicadas.</p>
        </div>
        <button
          onClick={() => setAMostrarFormulario((v) => !v)}
          className="bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90"
        >
          {aMostrarFormulario ? 'Cancelar' : 'Solicitar rectificação'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="mb-6 grid grid-cols-2 gap-3 border border-slate-200 bg-white p-4">
          <input required placeholder="ID da classificação" value={formulario.classificacao_id}
            onChange={(e) => setFormulario({ ...formulario, classificacao_id: e.target.value })}
            className="col-span-2 border border-slate-300 px-3 py-2 text-sm font-mono" />
          <input required type="number" min={0} max={20} step="0.1" placeholder="Valor novo"
            value={formulario.valor_novo}
            onChange={(e) => setFormulario({ ...formulario, valor_novo: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm" />
          <textarea required minLength={20} placeholder="Justificação (mínimo 20 caracteres)" value={formulario.justificacao}
            onChange={(e) => setFormulario({ ...formulario, justificacao: e.target.value })}
            className="col-span-2 border border-slate-300 px-3 py-2 text-sm" rows={2} />
          <button type="submit" className="col-span-2 mt-2 bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90">
            Solicitar
          </button>
        </form>
      )}

      {pedidos === null && <p className="text-sm text-slate-500">A carregar…</p>}

      {pedidos && (
        <div className="space-y-3">
          {pedidos.map((p) => (
            <div key={p.id} className="border border-slate-200 bg-white p-4">
              <div className="mb-2 flex items-center justify-between">
                <p className="text-sm font-medium text-slate-800">{p.estudante}</p>
                <span className={`px-2 py-0.5 text-xs ${
                  p.estado === 'PENDENTE' ? 'bg-amber-100 text-amber-700'
                  : p.estado === 'APROVADO' ? 'bg-green-100 text-green-700'
                  : 'bg-red-100 text-red-700'
                }`}>{p.estado}</span>
              </div>
              <p className="mb-1 text-sm text-slate-600">
                {p.valor_anterior} → {p.valor_novo}
              </p>
              <p className="mb-3 text-sm text-slate-500">{p.justificacao}</p>

              {podeDecidir && p.estado === 'PENDENTE' && (
                <div className="flex items-end gap-2">
                  <input
                    placeholder="Motivo (obrigatório se recusar)"
                    value={motivos[p.id] ?? ''}
                    onChange={(e) => setMotivos({ ...motivos, [p.id]: e.target.value })}
                    className="flex-1 border border-slate-300 px-3 py-1.5 text-sm"
                  />
                  <button onClick={() => void decidir(p, 'APROVAR')} className="bg-green-600 px-3 py-1.5 text-sm text-white hover:bg-green-700">
                    Aprovar
                  </button>
                  <button onClick={() => void decidir(p, 'RECUSAR')} className="bg-red-600 px-3 py-1.5 text-sm text-white hover:bg-red-700">
                    Recusar
                  </button>
                </div>
              )}
            </div>
          ))}
          {pedidos.length === 0 && <p className="text-sm text-slate-500">Sem pedidos de rectificação.</p>}
        </div>
      )}
    </div>
  )
}
