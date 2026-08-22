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
  if (!dados) return <p className="text-sm text-aco">A carregar…</p>

  return (
    <div>
      <h1 className="titulo-pagina">{dados.estudante}</h1>
      <p className="mb-6 text-sm text-aco">
        {dados.numero_estudante} · {dados.curso}
      </p>

      <div className="mb-6 grid grid-cols-3 gap-3">
        <div className="cartao p-4">
          <p className="text-xs uppercase tracking-wide text-aco-suave">Média ponderada</p>
          <p className="mt-1 font-display text-2xl font-semibold text-tinta">
            {dados.media_ponderada ?? '—'}
          </p>
        </div>
        <div className="cartao p-4">
          <p className="text-xs uppercase tracking-wide text-aco-suave">Créditos obtidos</p>
          <p className="mt-1 font-display text-2xl font-semibold text-tinta">
            {dados.creditos_obtidos} / {dados.creditos_curso}
          </p>
        </div>
        <div className="cartao p-4">
          <p className="text-xs uppercase tracking-wide text-aco-suave">Conclusão</p>
          <p className="mt-1 font-display text-2xl font-semibold text-tinta">{dados.percentagem_conclusao}%</p>
        </div>
      </div>

      {dados.anos_lectivos.map((ano) => (
        <div key={`${ano.ano_lectivo}-${ano.semestre}`} className="tabela-wrap mb-4">
          <div className="border-b border-linha bg-superficie-alt px-4 py-2 text-sm font-medium text-texto">
            {ano.ano_lectivo} — Semestre {ano.semestre}
          </div>
          <table className="tabela">
            <thead>
              <tr>
                <th>Disciplina</th>
                <th className="num">Frequência</th>
                <th>Situação</th>
                <th className="num">Final</th>
              </tr>
            </thead>
            <tbody>
              {ano.disciplinas.map((d) => (
                <tr key={d.id}>
                  <td>{d.codigo} — {d.disciplina}</td>
                  <td className="num">{d.nota_frequencia ?? '—'}</td>
                  <td>{d.situacao_frequencia}</td>
                  <td className="num">{d.classificacao_final ?? '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ))}

      {dados.anos_lectivos.length === 0 && (
        <p className="text-sm text-aco">Ainda sem histórico académico.</p>
      )}
    </div>
  )
}
