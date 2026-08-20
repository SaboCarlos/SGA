import { FormEvent, useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { Disciplina } from '@/tipos/api'

const ESTADO_INICIAL = { codigo: '', designacao: '', creditos: '6', carga_horaria: '90' }

/** Cadastro de disciplinas e pré-requisitos (US-08, US-10). */
export function Disciplinas() {
  const [disciplinas, setDisciplinas] = useState<Disciplina[] | null>(null)
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(ESTADO_INICIAL)
  const [aberta, setAberta] = useState<Disciplina | null>(null)
  const [preRequisitos, setPreRequisitos] = useState<Disciplina[] | null>(null)
  const [novoPreRequisitoId, setNovoPreRequisitoId] = useState('')
  const [erro, setErro] = useState<string | null>(null)
  const [erroPreRequisito, setErroPreRequisito] = useState<string | null>(null)
  const [aSubmeter, setASubmeter] = useState(false)

  useEffect(() => {
    carregar()
  }, [])

  function carregar() {
    api.get<Disciplina[]>('/disciplinas').then(setDisciplinas).catch(() => setErro('Não foi possível carregar as disciplinas.'))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)
    setASubmeter(true)

    try {
      await api.post('/disciplinas', {
        ...formulario,
        creditos: Number(formulario.creditos),
        carga_horaria: Number(formulario.carga_horaria),
      })
      setFormulario(ESTADO_INICIAL)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível criar a disciplina.')
    } finally {
      setASubmeter(false)
    }
  }

  function abrirPreRequisitos(disciplina: Disciplina) {
    setAberta(disciplina)
    setErroPreRequisito(null)
    api.get<Disciplina[]>(`/disciplinas/${disciplina.id}/pre-requisitos`).then(setPreRequisitos).catch(() => setPreRequisitos([]))
  }

  async function definirPreRequisito(evento: FormEvent) {
    evento.preventDefault()
    if (!aberta) return
    setErroPreRequisito(null)

    try {
      await api.post(`/disciplinas/${aberta.id}/pre-requisitos`, { pre_requisito_id: Number(novoPreRequisitoId) })
      setNovoPreRequisitoId('')
      abrirPreRequisitos(aberta)
    } catch (e: unknown) {
      // US-10: o servidor recusa ciclos directos e indirectos com 422 — a
      // mensagem devolvida já é clara o suficiente para mostrar tal-e-qual.
      setErroPreRequisito(e instanceof ErroApi ? e.message : 'Não foi possível definir o pré-requisito.')
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-lg font-semibold text-slate-800">Disciplinas</h1>
        <button
          onClick={() => setAMostrarFormulario((v) => !v)}
          className="rounded bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90"
        >
          {aMostrarFormulario ? 'Cancelar' : 'Nova disciplina'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="mb-6 grid grid-cols-2 gap-3 rounded-lg border border-slate-200 bg-white p-4">
          <input required placeholder="Código (ex.: INF401)" value={formulario.codigo}
            onChange={(e) => setFormulario({ ...formulario, codigo: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input required placeholder="Designação" value={formulario.designacao}
            onChange={(e) => setFormulario({ ...formulario, designacao: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input required type="number" placeholder="Créditos" value={formulario.creditos}
            onChange={(e) => setFormulario({ ...formulario, creditos: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input required type="number" placeholder="Carga horária" value={formulario.carga_horaria}
            onChange={(e) => setFormulario({ ...formulario, carga_horaria: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <button type="submit" disabled={aSubmeter}
            className="col-span-2 mt-2 rounded bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90 disabled:opacity-50">
            {aSubmeter ? 'A criar…' : 'Criar disciplina'}
          </button>
        </form>
      )}

      {disciplinas === null && <p className="text-sm text-slate-500">A carregar…</p>}

      {disciplinas && (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-2">Código</th>
                <th className="px-4 py-2">Designação</th>
                <th className="px-4 py-2">Créditos</th>
                <th className="px-4 py-2"></th>
              </tr>
            </thead>
            <tbody>
              {disciplinas.map((d) => (
                <tr key={d.id} className="border-t border-slate-100">
                  <td className="px-4 py-2 font-mono text-xs">{d.codigo}</td>
                  <td className="px-4 py-2">{d.designacao}</td>
                  <td className="px-4 py-2">{d.creditos}</td>
                  <td className="px-4 py-2 text-right">
                    <button onClick={() => abrirPreRequisitos(d)} className="text-escn-azul hover:underline">
                      Pré-requisitos
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {aberta && (
        <div className="mt-6 rounded-lg border border-slate-200 bg-white p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-slate-800">Pré-requisitos — {aberta.designacao}</h2>
            <button onClick={() => setAberta(null)} className="text-sm text-slate-500 hover:underline">Fechar</button>
          </div>

          {erroPreRequisito && <Alerta>{erroPreRequisito}</Alerta>}

          <ul className="mb-4 space-y-1 text-sm text-slate-700">
            {preRequisitos?.map((p) => <li key={p.id}>{p.codigo} — {p.designacao}</li>)}
            {preRequisitos?.length === 0 && <li className="text-slate-500">Sem pré-requisitos.</li>}
          </ul>

          <form onSubmit={definirPreRequisito} className="flex items-end gap-2">
            <select required value={novoPreRequisitoId} onChange={(e) => setNovoPreRequisitoId(e.target.value)}
              className="rounded border border-slate-300 px-3 py-2 text-sm">
              <option value="" disabled>Disciplina exigida…</option>
              {disciplinas?.filter((d) => d.id !== aberta.id).map((d) => (
                <option key={d.id} value={d.id}>{d.codigo} — {d.designacao}</option>
              ))}
            </select>
            <button type="submit" className="rounded bg-escn-azul px-4 py-2 text-sm text-white hover:bg-opacity-90">
              Definir
            </button>
          </form>
        </div>
      )}
    </div>
  )
}
