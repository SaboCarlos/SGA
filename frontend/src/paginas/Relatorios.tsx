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
          <h1 className="titulo-pagina">Aproveitamento por disciplina</h1>
          <p className="text-sm text-aco">Taxas de aprovação e reprovação (RF-55).</p>
        </div>
        <div className="flex gap-2">
          <button
            onClick={() => void api.baixar('/relatorios/aproveitamento-disciplina?formato=pdf')}
            className="botao-fantasma"
          >
            PDF
          </button>
          <button
            onClick={() => void api.baixar('/relatorios/aproveitamento-disciplina?formato=csv')}
            className="botao-fantasma"
          >
            CSV
          </button>
        </div>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {linhas === null && !erro && <p className="text-sm text-aco">A carregar…</p>}

      {linhas && (
        <div className="tabela-wrap">
          <table className="tabela">
            <thead>
              <tr>
                <th>Disciplina</th>
                <th>Ano lectivo</th>
                <th className="num">Inscritos</th>
                <th className="num">Aprovados</th>
                <th className="num">Reprovados</th>
                <th className="num">Taxa</th>
                <th className="num">Média</th>
              </tr>
            </thead>
            <tbody>
              {linhas.map((l) => (
                <tr key={`${l.codigo}-${l.ano_lectivo}-${l.semestre}`}>
                  <td>{l.codigo} — {l.disciplina}</td>
                  <td>{l.ano_lectivo} / S{l.semestre}</td>
                  <td className="num">{l.inscritos}</td>
                  <td className="num">{l.aprovados}</td>
                  <td className="num">{l.reprovados}</td>
                  <td className="num">{l.taxa_aprovacao}%</td>
                  <td className="num">{l.media_disciplina ?? '—'}</td>
                </tr>
              ))}
              {linhas.length === 0 && (
                <tr><td colSpan={7} className="py-6 text-center text-aco">Sem dados ainda.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
