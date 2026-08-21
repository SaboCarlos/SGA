import { FormEvent, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, ErroApi } from '@/servicos/api'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'
import { Alerta } from '@/componentes/Alerta'
import type { Disciplina, Docente, PeriodoLectivo, Turma } from '@/tipos/api'

const ESTADO_INICIAL = { disciplina_id: '', periodo_lectivo_id: '', docente_id: '', designacao: 'A', capacidade_maxima: '30' }

/** Ano lectivo e turmas (UC12, UC13 / US-11). */
export function Turmas() {
  const { temPerfil } = useAutenticacao()
  const [turmas, setTurmas] = useState<Turma[] | null>(null)
  const [disciplinas, setDisciplinas] = useState<Disciplina[]>([])
  const [docentes, setDocentes] = useState<Docente[]>([])
  const [periodos, setPeriodos] = useState<PeriodoLectivo[]>([])
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(ESTADO_INICIAL)
  const [erro, setErro] = useState<string | null>(null)
  const [aSubmeter, setASubmeter] = useState(false)

  const podeGerir = temPerfil('ADMINISTRADOR')

  useEffect(() => {
    carregar()
    if (podeGerir) {
      api.get<Disciplina[]>('/disciplinas').then(setDisciplinas).catch(() => undefined)
      api.get<Docente[]>('/docentes').then(setDocentes).catch(() => undefined)
      api
        .get<{ estado: string; id: number }[]>('/anos-lectivos')
        .then((anos) => {
          const activo = anos.find((a) => a.estado === 'ACTIVO')
          if (activo) return api.get<PeriodoLectivo[]>(`/anos-lectivos/${activo.id}/periodos`).then(setPeriodos)
        })
        .catch(() => undefined)
    }
  }, [podeGerir])

  function carregar() {
    api.get<Turma[]>('/turmas').then(setTurmas).catch(() => setErro('Não foi possível carregar as turmas.'))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)
    setASubmeter(true)

    try {
      await api.post('/turmas', {
        ...formulario,
        disciplina_id: Number(formulario.disciplina_id),
        periodo_lectivo_id: Number(formulario.periodo_lectivo_id),
        capacidade_maxima: Number(formulario.capacidade_maxima),
      })
      setFormulario(ESTADO_INICIAL)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível criar a turma.')
    } finally {
      setASubmeter(false)
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-lg font-semibold text-slate-800">Turmas</h1>
        {podeGerir && (
          <button
            onClick={() => setAMostrarFormulario((v) => !v)}
            className="bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90"
          >
            {aMostrarFormulario ? 'Cancelar' : 'Nova turma'}
          </button>
        )}
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="mb-6 grid grid-cols-2 gap-3 border border-slate-200 bg-white p-4">
          <select required value={formulario.disciplina_id}
            onChange={(e) => setFormulario({ ...formulario, disciplina_id: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm">
            <option value="" disabled>Disciplina…</option>
            {disciplinas.map((d) => <option key={d.id} value={d.id}>{d.codigo} — {d.designacao}</option>)}
          </select>
          <select required value={formulario.periodo_lectivo_id}
            onChange={(e) => setFormulario({ ...formulario, periodo_lectivo_id: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm">
            <option value="" disabled>Período lectivo…</option>
            {periodos.map((p) => <option key={p.id} value={p.id}>Semestre {p.semestre}</option>)}
          </select>
          <select required value={formulario.docente_id}
            onChange={(e) => setFormulario({ ...formulario, docente_id: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm">
            <option value="" disabled>Docente…</option>
            {docentes.map((d) => <option key={d.id} value={d.id}>{d.nome_completo}</option>)}
          </select>
          <input required placeholder="Designação (ex.: A)" value={formulario.designacao}
            onChange={(e) => setFormulario({ ...formulario, designacao: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm" />
          <input required type="number" placeholder="Capacidade máxima" value={formulario.capacidade_maxima}
            onChange={(e) => setFormulario({ ...formulario, capacidade_maxima: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm" />
          <button type="submit" disabled={aSubmeter}
            className="col-span-2 mt-2 bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90 disabled:opacity-50">
            {aSubmeter ? 'A criar…' : 'Criar turma'}
          </button>
        </form>
      )}

      {turmas === null && <p className="text-sm text-slate-500">A carregar…</p>}

      {turmas && (
        <div className="overflow-x-auto border border-slate-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-2">Disciplina</th>
                <th className="px-4 py-2">Turma</th>
                <th className="px-4 py-2">Docente</th>
                <th className="px-4 py-2">Vagas</th>
                <th className="px-4 py-2"></th>
              </tr>
            </thead>
            <tbody>
              {turmas.map((t) => (
                <tr key={t.id} className="border-t border-slate-100">
                  <td className="px-4 py-2">{t.disciplina.codigo} — {t.disciplina.designacao}</td>
                  <td className="px-4 py-2">{t.designacao}</td>
                  <td className="px-4 py-2">{t.docente}</td>
                  <td className="px-4 py-2">{t.vagas} / {t.capacidade_maxima}</td>
                  <td className="space-x-3 px-4 py-2 text-right">
                    <Link to={`/turmas/${t.id}/avaliacoes`} className="text-escn-azul hover:underline">Avaliações</Link>
                    <Link to={`/turmas/${t.id}/pauta`} className="text-escn-azul hover:underline">Pauta</Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
