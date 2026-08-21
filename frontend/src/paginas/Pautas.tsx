import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { Pauta } from '@/tipos/api'

/** Emissão e homologação de pautas (UC26, UC27). */
export function Pautas() {
  const { turmaId } = useParams<{ turmaId: string }>()
  const [pauta, setPauta] = useState<Pauta | null>(null)
  const [erro, setErro] = useState<string | null>(null)
  const [aHomologar, setAHomologar] = useState(false)

  useEffect(() => {
    carregar()
  }, [turmaId])

  function carregar() {
    api.get<Pauta>(`/turmas/${turmaId}/pauta`).then(setPauta).catch(() => setErro('Não foi possível carregar a pauta.'))
  }

  async function homologar() {
    if (!pauta) return
    setErro(null)
    setAHomologar(true)

    try {
      await api.post(`/pautas/${pauta.id}/homologar`)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível homologar a pauta.')
    } finally {
      setAHomologar(false)
    }
  }

  if (erro) return <Alerta>{erro}</Alerta>
  if (!pauta) return <p className="text-sm text-slate-500">A carregar…</p>

  return (
    <div>
      <div className="mb-6 flex items-start justify-between">
        <div>
          <h1 className="text-lg font-semibold text-slate-800">{pauta.numero}</h1>
          <p className="text-sm text-slate-500">
            {pauta.turma.disciplina} — Turma {pauta.turma.designacao} · <span className="font-medium">{pauta.estado}</span>
          </p>
        </div>
        <div className="flex gap-2">
          <button
            onClick={() => void api.baixar(`/turmas/${turmaId}/pauta?formato=pdf`)}
            className="border border-escn-azul px-4 py-2 text-sm text-escn-azul hover:bg-slate-50"
          >
            Descarregar PDF
          </button>
          {pauta.estado !== 'HOMOLOGADA' && (
            <button
              onClick={() => void homologar()}
              disabled={aHomologar}
              className="bg-escn-azul px-4 py-2 text-sm text-white hover:bg-opacity-90 disabled:opacity-50"
            >
              {aHomologar ? 'A homologar…' : 'Homologar'}
            </button>
          )}
        </div>
      </div>

      <div className="mb-4 grid grid-cols-3 gap-4">
        <div className="border border-slate-200 bg-white p-4">
          <p className="text-xs uppercase tracking-wide text-slate-400">Inscritos</p>
          <p className="mt-1 text-2xl font-semibold text-escn-azul">{pauta.total_inscritos}</p>
        </div>
        <div className="border border-slate-200 bg-white p-4">
          <p className="text-xs uppercase tracking-wide text-slate-400">Aprovados</p>
          <p className="mt-1 text-2xl font-semibold text-escn-azul">{pauta.total_aprovados}</p>
        </div>
        <div className="border border-slate-200 bg-white p-4">
          <p className="text-xs uppercase tracking-wide text-slate-400">Taxa de aprovação</p>
          <p className="mt-1 text-2xl font-semibold text-escn-azul">{pauta.taxa_aprovacao}%</p>
        </div>
      </div>

      <div className="overflow-x-auto border border-slate-200 bg-white">
        <table className="w-full text-left text-sm">
          <thead className="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
              <th className="px-4 py-2">Nº</th>
              <th className="px-4 py-2">Nome</th>
              <th className="px-4 py-2">Frequência</th>
              <th className="px-4 py-2">Situação</th>
            </tr>
          </thead>
          <tbody>
            {pauta.linhas.map((l) => (
              <tr key={l.inscricao_id} className="border-t border-slate-100">
                <td className="px-4 py-2 font-mono text-xs">{l.numero_estudante}</td>
                <td className="px-4 py-2">{l.nome_completo}</td>
                <td className="px-4 py-2">{l.nota_frequencia ?? '—'}</td>
                <td className="px-4 py-2">{l.situacao_frequencia}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
