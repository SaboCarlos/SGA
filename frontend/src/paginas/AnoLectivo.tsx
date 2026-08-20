import { FormEvent, useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { AnoLectivo as AnoLectivoTipo, PeriodoLectivo } from '@/tipos/api'

const ESTADO_INICIAL = { designacao: '', data_inicio: '', data_fim: '' }
const NOVO_PERIODO = { semestre: '1', inscricao_inicio: '', inscricao_fim: '', notas_inicio: '', notas_fim: '' }

/** Gestão do ano lectivo e dos períodos de inscrição/notas (UC12 / US-12). */
export function AnoLectivo() {
  const [anos, setAnos] = useState<AnoLectivoTipo[] | null>(null)
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(ESTADO_INICIAL)
  const [aberto, setAberto] = useState<AnoLectivoTipo | null>(null)
  const [periodos, setPeriodos] = useState<PeriodoLectivo[] | null>(null)
  const [novoPeriodo, setNovoPeriodo] = useState(NOVO_PERIODO)
  const [erro, setErro] = useState<string | null>(null)
  const [erroAno, setErroAno] = useState<string | null>(null)

  useEffect(() => {
    carregar()
  }, [])

  function carregar() {
    api.get<AnoLectivoTipo[]>('/anos-lectivos').then(setAnos).catch(() => setErro('Não foi possível carregar os anos lectivos.'))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)

    try {
      await api.post('/anos-lectivos', formulario)
      setFormulario(ESTADO_INICIAL)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível criar o ano lectivo.')
    }
  }

  async function alterarEstado(ano: AnoLectivoTipo, accao: 'activar' | 'encerrar') {
    setErro(null)

    try {
      await api.post(`/anos-lectivos/${ano.id}/${accao}`)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível alterar o estado.')
    }
  }

  function abrirPeriodos(ano: AnoLectivoTipo) {
    setAberto(ano)
    setErroAno(null)
    api.get<PeriodoLectivo[]>(`/anos-lectivos/${ano.id}/periodos`).then(setPeriodos).catch(() => setPeriodos([]))
  }

  async function criarPeriodo(evento: FormEvent) {
    evento.preventDefault()
    if (!aberto) return
    setErroAno(null)

    try {
      await api.post(`/anos-lectivos/${aberto.id}/periodos`, { ...novoPeriodo, semestre: Number(novoPeriodo.semestre) })
      setNovoPeriodo(NOVO_PERIODO)
      abrirPeriodos(aberto)
    } catch (e: unknown) {
      setErroAno(e instanceof ErroApi ? e.message : 'Não foi possível criar o período.')
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-lg font-semibold text-slate-800">Ano lectivo</h1>
        <button
          onClick={() => setAMostrarFormulario((v) => !v)}
          className="rounded bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90"
        >
          {aMostrarFormulario ? 'Cancelar' : 'Novo ano lectivo'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="mb-6 grid grid-cols-3 gap-3 rounded-lg border border-slate-200 bg-white p-4">
          <input required placeholder="Designação (ex.: 2027)" value={formulario.designacao}
            onChange={(e) => setFormulario({ ...formulario, designacao: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input required type="date" value={formulario.data_inicio}
            onChange={(e) => setFormulario({ ...formulario, data_inicio: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input required type="date" value={formulario.data_fim}
            onChange={(e) => setFormulario({ ...formulario, data_fim: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <button type="submit" className="col-span-3 mt-2 rounded bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90">
            Criar
          </button>
        </form>
      )}

      {anos === null && <p className="text-sm text-slate-500">A carregar…</p>}

      {anos && (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-2">Designação</th>
                <th className="px-4 py-2">Período</th>
                <th className="px-4 py-2">Estado</th>
                <th className="px-4 py-2"></th>
              </tr>
            </thead>
            <tbody>
              {anos.map((a) => (
                <tr key={a.id} className="border-t border-slate-100">
                  <td className="px-4 py-2">{a.designacao}</td>
                  <td className="px-4 py-2">{a.data_inicio} — {a.data_fim}</td>
                  <td className="px-4 py-2">{a.estado}</td>
                  <td className="space-x-3 px-4 py-2 text-right">
                    <button onClick={() => abrirPeriodos(a)} className="text-escn-azul hover:underline">Períodos</button>
                    {a.estado === 'PLANEADO' && (
                      <button onClick={() => void alterarEstado(a, 'activar')} className="text-escn-azul hover:underline">Activar</button>
                    )}
                    {a.estado === 'ACTIVO' && (
                      <button onClick={() => void alterarEstado(a, 'encerrar')} className="text-red-600 hover:underline">Encerrar</button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {aberto && (
        <div className="mt-6 rounded-lg border border-slate-200 bg-white p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-slate-800">Períodos — {aberto.designacao}</h2>
            <button onClick={() => setAberto(null)} className="text-sm text-slate-500 hover:underline">Fechar</button>
          </div>

          {erroAno && <Alerta>{erroAno}</Alerta>}

          <table className="mb-4 w-full text-left text-sm">
            <thead className="text-xs uppercase text-slate-500">
              <tr>
                <th className="py-2">Semestre</th>
                <th className="py-2">Inscrições</th>
                <th className="py-2">Notas</th>
                <th className="py-2">Estado</th>
              </tr>
            </thead>
            <tbody>
              {periodos?.map((p) => (
                <tr key={p.id} className="border-t border-slate-100">
                  <td className="py-2">{p.semestre}</td>
                  <td className="py-2">{p.inscricao_inicio} — {p.inscricao_fim}</td>
                  <td className="py-2">{p.notas_inicio} — {p.notas_fim}</td>
                  <td className="py-2">{p.inscricoes_abertas ? 'Inscrições abertas' : 'Inscrições fechadas'}</td>
                </tr>
              ))}
              {periodos?.length === 0 && <tr><td colSpan={4} className="py-4 text-center text-slate-500">Sem períodos definidos.</td></tr>}
            </tbody>
          </table>

          <form onSubmit={criarPeriodo} className="grid grid-cols-5 gap-2">
            <select value={novoPeriodo.semestre} onChange={(e) => setNovoPeriodo({ ...novoPeriodo, semestre: e.target.value })}
              className="rounded border border-slate-300 px-2 py-1.5 text-sm">
              <option value="1">Sem. 1</option>
              <option value="2">Sem. 2</option>
            </select>
            <input required type="date" title="Início das inscrições" value={novoPeriodo.inscricao_inicio}
              onChange={(e) => setNovoPeriodo({ ...novoPeriodo, inscricao_inicio: e.target.value })}
              className="rounded border border-slate-300 px-2 py-1.5 text-sm" />
            <input required type="date" title="Fim das inscrições" value={novoPeriodo.inscricao_fim}
              onChange={(e) => setNovoPeriodo({ ...novoPeriodo, inscricao_fim: e.target.value })}
              className="rounded border border-slate-300 px-2 py-1.5 text-sm" />
            <input required type="date" title="Início do lançamento de notas" value={novoPeriodo.notas_inicio}
              onChange={(e) => setNovoPeriodo({ ...novoPeriodo, notas_inicio: e.target.value })}
              className="rounded border border-slate-300 px-2 py-1.5 text-sm" />
            <input required type="date" title="Fim do lançamento de notas" value={novoPeriodo.notas_fim}
              onChange={(e) => setNovoPeriodo({ ...novoPeriodo, notas_fim: e.target.value })}
              className="rounded border border-slate-300 px-2 py-1.5 text-sm" />
            <button type="submit" className="col-span-5 mt-1 rounded bg-escn-azul px-4 py-2 text-sm text-white hover:bg-opacity-90">
              Criar período
            </button>
          </form>
        </div>
      )}
    </div>
  )
}
