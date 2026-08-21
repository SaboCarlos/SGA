import { useEffect, useState } from 'react'
import { api } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { LinhaRelatorioDisciplina } from '@/tipos/api'

/** Relatório de aproveitamento por disciplina (UC29 / RF-55). */
export function Relatorios() {
  const [linhas, setLinhas] = useState<LinhaRelatorioDisciplina[] | null>(null)
  const [erro, setErro] = useState<string | null>(null)

  useEffect(() => {
    api
      .get<LinhaRelatorioDisciplina[]>('/relatorios/aproveitamento-disciplina')
      .then(setLinhas)
      .catch(() => setErro('Não foi possível carregar o relatório.'))
  }, [])

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-lg font-semibold text-slate-800">Aproveitamento por disciplina</h1>
          <p className="text-sm text-slate-500">Taxas de aprovação e reprovação (RF-55).</p>
        </div>
        <div className="flex gap-2">
          <button
            onClick={() => void api.baixar('/relatorios/aproveitamento-disciplina?formato=pdf')}
            className="border border-escn-azul px-4 py-2 text-sm text-escn-azul hover:bg-slate-50"
          >
            PDF
          </button>
          <button
            onClick={() => void api.baixar('/relatorios/aproveitamento-disciplina?formato=csv')}
            className="border border-escn-azul px-4 py-2 text-sm text-escn-azul hover:bg-slate-50"
          >
            CSV
          </button>
        </div>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {linhas === null && !erro && <p className="text-sm text-slate-500">A carregar…</p>}

      {linhas && (
        <div className="overflow-x-auto border border-slate-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-2">Disciplina</th>
                <th className="px-4 py-2">Ano lectivo</th>
                <th className="px-4 py-2">Inscritos</th>
                <th className="px-4 py-2">Aprovados</th>
                <th className="px-4 py-2">Reprovados</th>
                <th className="px-4 py-2">Taxa</th>
                <th className="px-4 py-2">Média</th>
              </tr>
            </thead>
            <tbody>
              {linhas.map((l) => (
                <tr key={`${l.codigo}-${l.ano_lectivo}-${l.semestre}`} className="border-t border-slate-100">
                  <td className="px-4 py-2">{l.codigo} — {l.disciplina}</td>
                  <td className="px-4 py-2">{l.ano_lectivo} / S{l.semestre}</td>
                  <td className="px-4 py-2">{l.inscritos}</td>
                  <td className="px-4 py-2">{l.aprovados}</td>
                  <td className="px-4 py-2">{l.reprovados}</td>
                  <td className="px-4 py-2">{l.taxa_aprovacao}%</td>
                  <td className="px-4 py-2">{l.media_disciplina ?? '—'}</td>
                </tr>
              ))}
              {linhas.length === 0 && (
                <tr><td colSpan={7} className="px-4 py-6 text-center text-slate-500">Sem dados ainda.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
