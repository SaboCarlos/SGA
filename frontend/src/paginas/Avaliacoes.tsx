import { FormEvent, useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { Avaliacao, Classificacao, EstruturaAvaliacao, ResumoPublicacao, Turma } from '@/tipos/api'

const NOVA_AVALIACAO = { designacao: '', tipo: 'TESTE' as Avaliacao['tipo'], peso: '20', data_prevista: '' }

/** Avaliações de uma turma: definir, lançar classificações, publicar (UC19, UC20, UC21). */
export function Avaliacoes() {
  const { turmaId } = useParams<{ turmaId: string }>()
  const [turma, setTurma] = useState<Turma | null>(null)
  const [estrutura, setEstrutura] = useState<EstruturaAvaliacao | null>(null)
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(NOVA_AVALIACAO)
  const [aberta, setAberta] = useState<Avaliacao | null>(null)
  const [classificacoes, setClassificacoes] = useState<Classificacao[] | null>(null)
  const [valores, setValores] = useState<Record<string, string>>({})
  const [faltas, setFaltas] = useState<Record<string, boolean>>({})
  const [resumo, setResumo] = useState<ResumoPublicacao | null>(null)
  const [erro, setErro] = useState<string | null>(null)
  const [erroAvaliacao, setErroAvaliacao] = useState<string | null>(null)

  useEffect(() => {
    api.get<Turma>(`/turmas/${turmaId}`).then(setTurma).catch(() => undefined)
    carregar()
  }, [turmaId])

  function carregar() {
    api
      .get<EstruturaAvaliacao>(`/turmas/${turmaId}/avaliacoes`)
      .then(setEstrutura)
      .catch(() => setErro('Não foi possível carregar as avaliações.'))
  }

  async function submeterAvaliacao(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)

    try {
      await api.post(`/turmas/${turmaId}/avaliacoes`, { ...formulario, peso: Number(formulario.peso) })
      setFormulario(NOVA_AVALIACAO)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível definir a avaliação.')
    }
  }

  function abrirClassificacoes(avaliacao: Avaliacao) {
    setAberta(avaliacao)
    setResumo(null)
    setErroAvaliacao(null)
    api.get<Classificacao[]>(`/avaliacoes/${avaliacao.id}/classificacoes`).then((lista) => {
      setClassificacoes(lista)
      setValores(Object.fromEntries(lista.map((c) => [c.inscricao_id, c.valor?.toString() ?? ''])))
      setFaltas(Object.fromEntries(lista.map((c) => [c.inscricao_id, c.faltou])))
    })
  }

  async function lancarClassificacoes() {
    if (!aberta || !classificacoes) return
    setErroAvaliacao(null)

    try {
      await api.put(`/avaliacoes/${aberta.id}/classificacoes`, {
        classificacoes: classificacoes.map((c) => ({
          inscricao_id: c.inscricao_id,
          valor: faltas[c.inscricao_id] ? null : valores[c.inscricao_id] ? Number(valores[c.inscricao_id]) : null,
          faltou: faltas[c.inscricao_id] ?? false,
        })),
      })
      abrirClassificacoes(aberta)
    } catch (e: unknown) {
      setErroAvaliacao(e instanceof ErroApi ? e.message : 'Não foi possível gravar as classificações.')
    }
  }

  async function publicar(confirmarIncompleta: boolean) {
    if (!aberta) return
    setErroAvaliacao(null)

    try {
      const resposta = await api.post<ResumoPublicacao>(`/avaliacoes/${aberta.id}/publicar`, {
        confirmar_incompleta: confirmarIncompleta,
      })
      setResumo(resposta)
      carregar()
    } catch (e: unknown) {
      if (e instanceof ErroApi && e.estado === 409) {
        // US-18, fluxo A1: ha estudantes sem nota nem falta — pergunta-se
        // explicitamente antes de publicar incompleto, em vez de recusar em silencio.
        if (window.confirm(`${e.message}\n\nPublicar mesmo assim?`)) {
          void publicar(true)
          return
        }
      }
      setErroAvaliacao(e instanceof ErroApi ? e.message : 'Não foi possível publicar.')
    }
  }

  return (
    <div>
      <h1 className="text-lg font-semibold text-slate-800">Avaliações</h1>
      <p className="mb-6 text-sm text-slate-500">
        {turma ? `${turma.disciplina.codigo} — ${turma.disciplina.designacao}, turma ${turma.designacao}` : '…'}
      </p>

      {erro && <Alerta>{erro}</Alerta>}

      {estrutura && (
        <div className="mb-4 flex items-center justify-between border border-slate-200 bg-white px-4 py-3">
          <p className="text-sm text-slate-600">
            Soma dos pesos: <strong>{estrutura.soma_pesos}%</strong>{' '}
            {estrutura.estrutura_valida ? (
              <span className="text-green-600">— estrutura válida</span>
            ) : (
              <span className="text-amber-600">— falta chegar aos 100%</span>
            )}
          </p>
          <button onClick={() => setAMostrarFormulario((v) => !v)} className="bg-escn-azul px-3 py-1.5 text-sm text-white hover:bg-opacity-90">
            {aMostrarFormulario ? 'Cancelar' : 'Nova avaliação'}
          </button>
        </div>
      )}

      {aMostrarFormulario && (
        <form onSubmit={submeterAvaliacao} className="mb-6 grid grid-cols-2 gap-3 border border-slate-200 bg-white p-4">
          <input required placeholder="Designação (ex.: Teste 1)" value={formulario.designacao}
            onChange={(e) => setFormulario({ ...formulario, designacao: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm" />
          <select value={formulario.tipo} onChange={(e) => setFormulario({ ...formulario, tipo: e.target.value as Avaliacao['tipo'] })}
            className="border border-slate-300 px-3 py-2 text-sm">
            <option value="TESTE">Teste</option>
            <option value="TRABALHO">Trabalho</option>
            <option value="PRATICA">Prática</option>
            <option value="EXAME">Exame</option>
          </select>
          <input required type="number" step="0.01" placeholder="Peso (%)" value={formulario.peso}
            onChange={(e) => setFormulario({ ...formulario, peso: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm" />
          <input type="date" value={formulario.data_prevista}
            onChange={(e) => setFormulario({ ...formulario, data_prevista: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm" />
          <button type="submit" className="col-span-2 mt-2 bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90">
            Definir avaliação
          </button>
        </form>
      )}

      {estrutura && (
        <div className="overflow-x-auto border border-slate-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-2">Designação</th>
                <th className="px-4 py-2">Tipo</th>
                <th className="px-4 py-2">Peso</th>
                <th className="px-4 py-2">Estado</th>
                <th className="px-4 py-2"></th>
              </tr>
            </thead>
            <tbody>
              {estrutura.avaliacoes.map((a) => (
                <tr key={a.id} className="border-t border-slate-100">
                  <td className="px-4 py-2">{a.designacao}</td>
                  <td className="px-4 py-2">{a.tipo}</td>
                  <td className="px-4 py-2">{a.peso}%</td>
                  <td className="px-4 py-2">{a.estado}</td>
                  <td className="px-4 py-2 text-right">
                    {a.estado !== 'PUBLICADA' && (
                      <button onClick={() => abrirClassificacoes(a)} className="text-escn-azul hover:underline">
                        Classificações
                      </button>
                    )}
                  </td>
                </tr>
              ))}
              {estrutura.avaliacoes.length === 0 && (
                <tr><td colSpan={5} className="px-4 py-6 text-center text-slate-500">Sem avaliações definidas.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      {aberta && (
        <div className="mt-6 border border-slate-200 bg-white p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-slate-800">Classificações — {aberta.designacao}</h2>
            <button onClick={() => setAberta(null)} className="text-sm text-slate-500 hover:underline">Fechar</button>
          </div>

          {erroAvaliacao && <Alerta>{erroAvaliacao}</Alerta>}
          {resumo && (
            <Alerta tipo="sucesso">
              Publicado: {resumo.publicadas} · Dispensados: {resumo.dispensados} · Admitidos a exame: {resumo.admitidos_exame} · Reprovados por frequência: {resumo.reprovados_frequencia}
            </Alerta>
          )}

          <table className="mb-4 w-full text-left text-sm">
            <thead className="text-xs uppercase text-slate-500">
              <tr>
                <th className="py-2">Estudante</th>
                <th className="py-2">Nota (0–20)</th>
                <th className="py-2">Faltou</th>
              </tr>
            </thead>
            <tbody>
              {classificacoes?.map((c) => (
                <tr key={c.inscricao_id} className="border-t border-slate-100">
                  <td className="py-2">{c.numero_estudante} — {c.nome_completo}</td>
                  <td className="py-2">
                    <input
                      type="number" min={0} max={20} step="0.1"
                      disabled={faltas[c.inscricao_id]}
                      value={valores[c.inscricao_id] ?? ''}
                      onChange={(e) => setValores({ ...valores, [c.inscricao_id]: e.target.value })}
                      className="w-20 border border-slate-300 px-2 py-1 text-sm disabled:bg-slate-100"
                    />
                  </td>
                  <td className="py-2">
                    <input
                      type="checkbox"
                      checked={faltas[c.inscricao_id] ?? false}
                      onChange={(e) => setFaltas({ ...faltas, [c.inscricao_id]: e.target.checked })}
                    />
                  </td>
                </tr>
              ))}
              {classificacoes?.length === 0 && (
                <tr><td colSpan={3} className="py-4 text-center text-slate-500">Sem estudantes inscritos nesta turma.</td></tr>
              )}
            </tbody>
          </table>

          <div className="flex gap-2">
            <button onClick={() => void lancarClassificacoes()} className="border border-escn-azul px-4 py-2 text-sm text-escn-azul hover:bg-slate-50">
              Gravar rascunho
            </button>
            <button onClick={() => void publicar(false)} className="bg-escn-azul px-4 py-2 text-sm text-white hover:bg-opacity-90">
              Publicar
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
