import { FormEvent, useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { Docente } from '@/tipos/api'

const ESTADO_INICIAL = { nome_completo: '', email: '', codigo: '', grau_academico: '', categoria: '' }

/** Cadastro de docentes (UC07 / US-07). */
export function Docentes() {
  const [docentes, setDocentes] = useState<Docente[] | null>(null)
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(ESTADO_INICIAL)
  const [erro, setErro] = useState<string | null>(null)
  const [aSubmeter, setASubmeter] = useState(false)

  useEffect(() => {
    carregar()
  }, [])

  function carregar() {
    api.get<Docente[]>('/docentes').then(setDocentes).catch(() => setErro('Não foi possível carregar os docentes.'))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)
    setASubmeter(true)

    try {
      await api.post('/docentes', formulario)
      setFormulario(ESTADO_INICIAL)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível registar o docente.')
    } finally {
      setASubmeter(false)
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-lg font-semibold text-slate-800">Docentes</h1>
        <button
          onClick={() => setAMostrarFormulario((v) => !v)}
          className="rounded bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90"
        >
          {aMostrarFormulario ? 'Cancelar' : 'Novo docente'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="mb-6 grid grid-cols-2 gap-3 rounded-lg border border-slate-200 bg-white p-4">
          <input required placeholder="Nome completo" value={formulario.nome_completo}
            onChange={(e) => setFormulario({ ...formulario, nome_completo: e.target.value })}
            className="col-span-2 rounded border border-slate-300 px-3 py-2 text-sm" />
          <input required type="email" placeholder="E-mail institucional" value={formulario.email}
            onChange={(e) => setFormulario({ ...formulario, email: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input required placeholder="Código (ex.: DOC003)" value={formulario.codigo}
            onChange={(e) => setFormulario({ ...formulario, codigo: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input placeholder="Grau académico" value={formulario.grau_academico}
            onChange={(e) => setFormulario({ ...formulario, grau_academico: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input placeholder="Categoria" value={formulario.categoria}
            onChange={(e) => setFormulario({ ...formulario, categoria: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <button type="submit" disabled={aSubmeter}
            className="col-span-2 mt-2 rounded bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90 disabled:opacity-50">
            {aSubmeter ? 'A registar…' : 'Registar docente'}
          </button>
        </form>
      )}

      {docentes === null && <p className="text-sm text-slate-500">A carregar…</p>}

      {docentes && (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-2">Código</th>
                <th className="px-4 py-2">Nome</th>
                <th className="px-4 py-2">Grau</th>
                <th className="px-4 py-2">Categoria</th>
              </tr>
            </thead>
            <tbody>
              {docentes.map((d) => (
                <tr key={d.id} className="border-t border-slate-100">
                  <td className="px-4 py-2 font-mono text-xs">{d.codigo}</td>
                  <td className="px-4 py-2">{d.nome_completo}</td>
                  <td className="px-4 py-2">{d.grau_academico ?? '—'}</td>
                  <td className="px-4 py-2">{d.categoria ?? '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
