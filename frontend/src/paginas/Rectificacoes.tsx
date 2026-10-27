import { FormEvent, useEffect, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { api, ErroApi } from '@/servicos/api'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'
import { Alerta } from '@/componentes/Alerta'
import { Paginacao, usePaginacaoLocal } from '@/componentes/Paginacao'
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
  // Vindo de "As minhas notas" (?classificacao=…&valor=…), o formulário abre
  // já com a nota escolhida — o estudante não tem de saber o ID de nada.
  const [parametros] = useSearchParams()
  const navegar = useNavigate()
  const classificacaoEscolhida = parametros.get('classificacao')
  const valorActual = parametros.get('valor')
  const [pedidos, setPedidos] = useState<PedidoRectificacao[] | null>(null)
  // O pedido nasce em "As minhas notas", junto da nota em causa.
  const aMostrarFormulario = classificacaoEscolhida !== null
  const ehEstudante = temPerfil('ESTUDANTE') && !temPerfil('ADMINISTRADOR', 'COORDENADOR', 'DOCENTE')
  const [formulario, setFormulario] = useState({ ...NOVO_PEDIDO, classificacao_id: classificacaoEscolhida ?? '' })
  const [motivos, setMotivos] = useState<Record<string, string>>({})
  const [erro, setErro] = useState<string | null>(null)


  const pgPedidos = usePaginacaoLocal(pedidos)
  useEffect(() => {
    carregar()
  }, [])

  // Cada "Pedir rectificação" traz a sua nota no endereço.
  useEffect(() => {
    setFormulario({ ...NOVO_PEDIDO, classificacao_id: classificacaoEscolhida ?? '' })
  }, [classificacaoEscolhida])

  function carregar() {
    api.get<PedidoRectificacao[]>('/rectificacoes').then(setPedidos).catch(() => setErro('Não foi possível carregar os pedidos.'))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)

    try {
      await api.post('/rectificacoes', { ...formulario, valor_novo: Number(formulario.valor_novo) })
      setFormulario(NOVO_PEDIDO)
      navegar('/rectificacoes', { replace: true })
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
          <p className="text-sm text-aco">
            {ehEstudante
              ? 'Os seus pedidos de correcção de nota. Para fazer um pedido, vá a As minhas notas e carregue em Pedir rectificação, ao lado da nota.'
              : 'Pedidos de correcção de notas já publicadas, das disciplinas que lecciona ou do curso que coordena. Só o Coordenador do curso decide.'}
          </p>
        </div>
        {aMostrarFormulario && (
          <button onClick={() => navegar('/rectificacoes', { replace: true })} className="botao-fantasma">
            Cancelar
          </button>
        )}
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="cartao mb-6 grid grid-cols-2 gap-3 p-4">
          <p className="col-span-2 text-sm text-texto">
            Nota actual: <strong>{valorActual || '—'}</strong>
          </p>
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
          {pgPedidos.visiveis.map((p) => (
            <div key={p.id} className="cartao p-4">
              <div className="mb-2 flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-texto">{p.estudante}</p>
                  <p className="text-xs text-aco">{p.disciplina} · {p.avaliacao}</p>
                </div>
                <span className={`selo ${ESTILO_ESTADO[p.estado]}`}>{p.estado}</span>
              </div>
              <p className="mb-1 text-sm text-texto">
                {p.valor_anterior} → {p.valor_novo}
              </p>
              <p className="mb-3 text-sm text-aco">{p.justificacao}</p>

              {p.pode_decidir && (
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
          <Paginacao coleccao={pgPedidos.coleccao} aoMudar={pgPedidos.setPagina} />
        </div>
      )}
    </div>
  )
}
