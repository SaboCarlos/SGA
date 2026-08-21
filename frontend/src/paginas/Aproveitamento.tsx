import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { Aproveitamento as AproveitamentoTipo } from '@/tipos/api'

/** Consulta do aproveitamento académico (UC25 / US-22), com RNF-12 aplicado no servidor. */
export function Aproveitamento() {
  const { id } = useParams<{ id: string }>()
  const [dados, setDados] = useState<AproveitamentoTipo | null>(null)
  const [erro, setErro] = useState<string | null>(null)

  useEffect(() => {
    api
      .get<AproveitamentoTipo>(`/estudantes/${id}/aproveitamento`)
      .then(setDados)
      .catch((e: unknown) => {
        setErro(
          e instanceof ErroApi && e.estado === 403
            ? 'Sem permissão para consultar o aproveitamento deste estudante.'
            : 'Não foi possível carregar o aproveitamento.'
        )
      })
  }, [id])

  if (erro) return <Alerta>{erro}</Alerta>
  if (!dados) return <p className="text-sm text-slate-500">A carregar…</p>

  return (
    <div>
      <h1 className="text-lg font-semibold text-slate-800">{dados.estudante}</h1>
      <p className="mb-6 text-sm text-slate-500">
        {dados.numero_estudante} · {dados.curso}
      </p>

      <div className="mb-6 grid grid-cols-3 gap-4">
        <div className="border border-slate-200 bg-white p-4">
          <p className="text-xs uppercase tracking-wide text-slate-400">Média ponderada</p>
          <p className="mt-1 text-2xl font-semibold text-escn-azul">
            {dados.media_ponderada ?? '—'}
          </p>
        </div>
        <div className="border border-slate-200 bg-white p-4">
          <p className="text-xs uppercase tracking-wide text-slate-400">Créditos obtidos</p>
          <p className="mt-1 text-2xl font-semibold text-escn-azul">
            {dados.creditos_obtidos} / {dados.creditos_curso}
          </p>
        </div>
        <div className="border border-slate-200 bg-white p-4">
          <p className="text-xs uppercase tracking-wide text-slate-400">Conclusão</p>
          <p className="mt-1 text-2xl font-semibold text-escn-azul">{dados.percentagem_conclusao}%</p>
        </div>
      </div>

      {dados.anos_lectivos.map((ano) => (
        <div key={`${ano.ano_lectivo}-${ano.semestre}`} className="mb-4 overflow-x-auto border border-slate-200 bg-white">
          <div className="border-b border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700">
            {ano.ano_lectivo} — Semestre {ano.semestre}
          </div>
          <table className="w-full text-left text-sm">
            <thead className="text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-2">Disciplina</th>
                <th className="px-4 py-2">Frequência</th>
                <th className="px-4 py-2">Situação</th>
                <th className="px-4 py-2">Final</th>
              </tr>
            </thead>
            <tbody>
              {ano.disciplinas.map((d) => (
                <tr key={d.id} className="border-t border-slate-100">
                  <td className="px-4 py-2">{d.codigo} — {d.disciplina}</td>
                  <td className="px-4 py-2">{d.nota_frequencia ?? '—'}</td>
                  <td className="px-4 py-2">{d.situacao_frequencia}</td>
                  <td className="px-4 py-2">{d.classificacao_final ?? '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ))}

      {dados.anos_lectivos.length === 0 && (
        <p className="text-sm text-slate-500">Ainda sem histórico académico.</p>
      )}
    </div>
  )
}
