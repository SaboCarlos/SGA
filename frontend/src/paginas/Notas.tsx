import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { NotasEstudante, SituacaoFinal, SituacaoFrequencia } from '@/tipos/api'

const ROTULO_FREQUENCIA: Record<SituacaoFrequencia, string> = {
  EM_CURSO: 'Em curso',
  DISPENSADO: 'Dispensado',
  ADMITIDO_EXAME: 'Admitido a exame',
  REPROVADO_FREQUENCIA: 'Excluído',
}

const SELO_FINAL: Record<SituacaoFinal, { rotulo: string; estilo: string }> = {
  EM_CURSO: { rotulo: 'Em curso', estilo: 'selo-neutro' },
  APROVADO: { rotulo: 'Aprovado', estilo: 'selo-sucesso' },
  REPROVADO: { rotulo: 'Reprovado', estilo: 'selo-perigo' },
}

type DisciplinaNotas = NotasEstudante['disciplinas'][number]

/**
 * As minhas notas (UC25 / US-22, lado do estudante): o ano em que está, as
 * disciplinas desse ano e, em cada uma, as avaliações e a nota obtida. As
 * disciplinas de anos lectivos anteriores ficam numa secção à parte. Só
 * aparecem notas já publicadas — o servidor não envia as que estão em
 * rascunho.
 */
export function Notas() {
  const [dados, setDados] = useState<NotasEstudante | null>(null)
  const [erro, setErro] = useState<string | null>(null)

  useEffect(() => {
    api.get<NotasEstudante>('/notas').then(setDados).catch(() => setErro('Não foi possível carregar as suas notas.'))
  }, [])

  if (erro) return <Alerta>{erro}</Alerta>
  if (!dados) return <p className="text-sm text-aco">A carregar…</p>

  const deste = dados.disciplinas.filter((d) => d.ano_lectivo === dados.ano_lectivo_actual)
  const anteriores = dados.disciplinas.filter((d) => d.ano_lectivo !== dados.ano_lectivo_actual)

  return (
    <div>
      <h1 className="titulo-pagina">As minhas notas</h1>
      <p className="mb-6 text-sm text-aco">
        {dados.numero_estudante} · {dados.estudante}
      </p>

      <div className="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div className="cartao p-4">
          <p className="text-xs uppercase tracking-wide text-aco-suave">Curso</p>
          <p className="mt-1 font-medium text-texto">{dados.curso ?? '—'}</p>
        </div>
        <div className="cartao p-4">
          <p className="text-xs uppercase tracking-wide text-aco-suave">Ano</p>
          <p className="mt-1 font-display text-2xl font-semibold text-tinta">
            {dados.ano_curricular ? `${dados.ano_curricular}.º ano` : '—'}
          </p>
        </div>
        <div className="cartao p-4">
          <p className="text-xs uppercase tracking-wide text-aco-suave">Ano lectivo</p>
          <p className="mt-1 font-display text-2xl font-semibold text-tinta">{dados.ano_lectivo_actual ?? '—'}</p>
        </div>
      </div>

      {dados.disciplinas.length === 0 && (
        <p className="text-sm text-aco">
          Ainda não está inscrito em nenhuma disciplina. <Link to="/inscricoes" className="text-tinta hover:underline">Inscrever-me</Link>
        </p>
      )}

      {deste.length > 0 && (
        <h2 className="mb-3 font-display text-base font-semibold text-texto">
          Disciplinas {dados.ano_curricular ? `do ${dados.ano_curricular}.º ano` : 'deste ano lectivo'} ({deste.length})
        </h2>
      )}
      {deste.map(seccao)}

      {anteriores.length > 0 && (
        <h2 className="mb-3 mt-8 font-display text-base font-semibold text-texto">Anos lectivos anteriores</h2>
      )}
      {anteriores.map(seccao)}
    </div>
  )

  function seccao(d: DisciplinaNotas) {
    return (
      <section key={d.inscricao_id} className="tabela-wrap mb-5">
        <div className="flex flex-wrap items-baseline justify-between gap-2 border-b border-linha bg-superficie-alt px-4 py-3">
          <div>
            <p className="font-medium text-texto">{d.codigo} — {d.disciplina}</p>
            <p className="text-xs text-aco">
              {d.ano_curricular && `${d.ano_curricular}.º ano · `}
              {d.ano_lectivo}, {d.semestre}.º semestre · Turma {d.turma}
              {d.docente && ` · ${d.docente}`}
            </p>
          </div>
          <span className={`selo ${SELO_FINAL[d.situacao_final].estilo}`}>{SELO_FINAL[d.situacao_final].rotulo}</span>
        </div>

        {d.avaliacoes.length === 0 ? (
          <p className="px-4 py-3 text-sm text-aco">O docente ainda não definiu as avaliações desta disciplina.</p>
        ) : (
          <table className="tabela">
            <thead>
              <tr>
                <th>Avaliação</th>
                <th className="num">Peso</th>
                <th className="num">Nota</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {d.avaliacoes.map((a) => (
                <tr key={a.id}>
                  <td>{a.designacao}</td>
                  <td className="num">{a.tipo === 'EXAME' ? 'à parte' : `${a.peso}%`}</td>
                  <td className="num">
                    {!a.publicada ? (
                      <span className="text-aco-suave">por publicar</span>
                    ) : a.faltou ? (
                      <span className="selo selo-aviso">Faltou</span>
                    ) : (
                      <strong>{a.valor}</strong>
                    )}
                  </td>
                  <td className="text-right">
                    {a.publicada && a.classificacao_id && (
                      <Link
                        to={`/rectificacoes?classificacao=${a.classificacao_id}&valor=${a.valor ?? ''}`}
                        className="text-sm text-tinta hover:underline"
                      >
                        Pedir rectificação
                      </Link>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}

        <div className="grid grid-cols-2 gap-3 border-t border-linha px-4 py-3 text-sm sm:grid-cols-3">
          <p>
            <span className="text-aco">Frequência: </span>
            <strong>{d.nota_frequencia ?? '—'}</strong>
          </p>
          <p>
            <span className="text-aco">Situação: </span>
            {ROTULO_FREQUENCIA[d.situacao_frequencia]}
          </p>
          <p>
            <span className="text-aco">Classificação final: </span>
            <strong>{d.classificacao_final ?? '—'}</strong>
          </p>
        </div>
      </section>
    )
  }
}
