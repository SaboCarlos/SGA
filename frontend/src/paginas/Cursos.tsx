import { FormEvent, useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { Curso, Disciplina, ItemPlano } from '@/tipos/api'

const ESTADO_INICIAL = { codigo: '', designacao: '', grau: 'Licenciatura', duracao_anos: '4', total_creditos: '240' }

/** Gestão de cursos e planos curriculares (UC08, UC09 / US-08, US-09). */
export function Cursos() {
  const [cursos, setCursos] = useState<Curso[] | null>(null)
  const [disciplinas, setDisciplinas] = useState<Disciplina[]>([])
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(ESTADO_INICIAL)
  const [cursoAberto, setCursoAberto] = useState<Curso | null>(null)
  const [plano, setPlano] = useState<ItemPlano[] | null>(null)
  const [novoItem, setNovoItem] = useState({ disciplina_id: '', ano_curricular: '1', semestre: '1' })
  const [erro, setErro] = useState<string | null>(null)
  const [erroPlano, setErroPlano] = useState<string | null>(null)
  const [aSubmeter, setASubmeter] = useState(false)

  useEffect(() => {
    carregar()
    api.get<Disciplina[]>('/disciplinas').then(setDisciplinas).catch(() => undefined)
  }, [])

  function carregar() {
    api.get<Curso[]>('/cursos').then(setCursos).catch(() => setErro('Não foi possível carregar os cursos.'))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)
    setASubmeter(true)

    try {
      await api.post('/cursos', {
        ...formulario,
        duracao_anos: Number(formulario.duracao_anos),
        total_creditos: Number(formulario.total_creditos),
      })
      setFormulario(ESTADO_INICIAL)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível criar o curso.')
    } finally {
      setASubmeter(false)
    }
  }

  function abrirPlano(curso: Curso) {
    setCursoAberto(curso)
    setErroPlano(null)
    api.get<ItemPlano[]>(`/cursos/${curso.id}/plano-curricular`).then(setPlano).catch(() => setPlano([]))
  }

  async function acrescentarAoPlano(evento: FormEvent) {
    evento.preventDefault()
    if (!cursoAberto) return
    setErroPlano(null)

    try {
      await api.post(`/cursos/${cursoAberto.id}/plano-curricular`, {
        disciplina_id: Number(novoItem.disciplina_id),
        ano_curricular: Number(novoItem.ano_curricular),
        semestre: Number(novoItem.semestre),
      })
      setNovoItem({ disciplina_id: '', ano_curricular: '1', semestre: '1' })
      abrirPlano(cursoAberto)
    } catch (e: unknown) {
      setErroPlano(e instanceof ErroApi ? e.message : 'Não foi possível acrescentar a disciplina.')
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-lg font-semibold text-slate-800">Cursos</h1>
        <button
          onClick={() => setAMostrarFormulario((v) => !v)}
          className="rounded bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90"
        >
          {aMostrarFormulario ? 'Cancelar' : 'Novo curso'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="mb-6 grid grid-cols-2 gap-3 rounded-lg border border-slate-200 bg-white p-4">
          <input required placeholder="Código (ex.: PSIT)" value={formulario.codigo}
            onChange={(e) => setFormulario({ ...formulario, codigo: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input required placeholder="Grau" value={formulario.grau}
            onChange={(e) => setFormulario({ ...formulario, grau: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input required placeholder="Designação" value={formulario.designacao}
            onChange={(e) => setFormulario({ ...formulario, designacao: e.target.value })}
            className="col-span-2 rounded border border-slate-300 px-3 py-2 text-sm" />
          <input required type="number" placeholder="Duração (anos)" value={formulario.duracao_anos}
            onChange={(e) => setFormulario({ ...formulario, duracao_anos: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input required type="number" placeholder="Total de créditos" value={formulario.total_creditos}
            onChange={(e) => setFormulario({ ...formulario, total_creditos: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <button type="submit" disabled={aSubmeter}
            className="col-span-2 mt-2 rounded bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90 disabled:opacity-50">
            {aSubmeter ? 'A criar…' : 'Criar curso'}
          </button>
        </form>
      )}

      {cursos === null && <p className="text-sm text-slate-500">A carregar…</p>}

      {cursos && (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-2">Código</th>
                <th className="px-4 py-2">Designação</th>
                <th className="px-4 py-2">Duração</th>
                <th className="px-4 py-2"></th>
              </tr>
            </thead>
            <tbody>
              {cursos.map((c) => (
                <tr key={c.id} className="border-t border-slate-100">
                  <td className="px-4 py-2 font-mono text-xs">{c.codigo}</td>
                  <td className="px-4 py-2">{c.designacao}</td>
                  <td className="px-4 py-2">{c.duracao_anos} anos</td>
                  <td className="px-4 py-2 text-right">
                    <button onClick={() => abrirPlano(c)} className="text-escn-azul hover:underline">
                      Plano curricular
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {cursoAberto && (
        <div className="mt-6 rounded-lg border border-slate-200 bg-white p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-slate-800">Plano curricular — {cursoAberto.designacao}</h2>
            <button onClick={() => setCursoAberto(null)} className="text-sm text-slate-500 hover:underline">Fechar</button>
          </div>

          {erroPlano && <Alerta>{erroPlano}</Alerta>}

          <table className="mb-4 w-full text-left text-sm">
            <thead className="text-xs uppercase text-slate-500">
              <tr>
                <th className="py-2">Disciplina</th>
                <th className="py-2">Ano</th>
                <th className="py-2">Semestre</th>
                <th className="py-2">Tipo</th>
              </tr>
            </thead>
            <tbody>
              {plano?.map((item) => (
                <tr key={item.id} className="border-t border-slate-100">
                  <td className="py-2">{item.disciplina}</td>
                  <td className="py-2">{item.ano_curricular}</td>
                  <td className="py-2">{item.semestre}</td>
                  <td className="py-2">{item.tipo}</td>
                </tr>
              ))}
              {plano?.length === 0 && (
                <tr><td colSpan={4} className="py-4 text-center text-slate-500">Sem disciplinas no plano.</td></tr>
              )}
            </tbody>
          </table>

          <form onSubmit={acrescentarAoPlano} className="flex flex-wrap items-end gap-2">
            <select required value={novoItem.disciplina_id}
              onChange={(e) => setNovoItem({ ...novoItem, disciplina_id: e.target.value })}
              className="rounded border border-slate-300 px-3 py-2 text-sm">
              <option value="" disabled>Disciplina…</option>
              {disciplinas.map((d) => <option key={d.id} value={d.id}>{d.codigo} — {d.designacao}</option>)}
            </select>
            <input required type="number" min={1} placeholder="Ano" value={novoItem.ano_curricular}
              onChange={(e) => setNovoItem({ ...novoItem, ano_curricular: e.target.value })}
              className="w-20 rounded border border-slate-300 px-3 py-2 text-sm" />
            <select value={novoItem.semestre} onChange={(e) => setNovoItem({ ...novoItem, semestre: e.target.value })}
              className="rounded border border-slate-300 px-3 py-2 text-sm">
              <option value="1">Semestre 1</option>
              <option value="2">Semestre 2</option>
            </select>
            <button type="submit" className="rounded bg-escn-azul px-4 py-2 text-sm text-white hover:bg-opacity-90">
              Acrescentar
            </button>
          </form>
        </div>
      )}
    </div>
  )
}
