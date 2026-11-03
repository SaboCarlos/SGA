import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import { Paginacao, usePaginacaoLocal } from '@/componentes/Paginacao'
import type { Pauta, SituacaoFinal } from '@/tipos/api'

const SELO_FINAL: Record<SituacaoFinal, { rotulo: string; estilo: string }> = {
  EM_CURSO: { rotulo: 'Em curso', estilo: 'selo-neutro' },
  APROVADO: { rotulo: 'Aprovado', estilo: 'selo-sucesso' },
  REPROVADO: { rotulo: 'Reprovado', estilo: 'selo-perigo' },
}

/** Emissão e homologação de pautas (UC26, UC27). */
export function Pautas() {
  const { turmaId } = useParams<{ turmaId: string }>()
  const [pauta, setPauta] = useState<Pauta | null>(null)
  const [erro, setErro] = useState<string | null>(null)
  const [aHomologar, setAHomologar] = useState(false)

  const pgLinhas = usePaginacaoLocal(pauta?.linhas)
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
  if (!pauta) return <p className="text-sm text-aco">A carregar…</p>

  return (
    <div>
      <div className="mb-6 flex items-start justify-between">
        <div>
          <h1 className="titulo-pagina">{pauta.numero}</h1>
          <p className="text-sm text-aco">
            {pauta.turma.disciplina} — Turma {pauta.turma.designacao} · <span className="font-medium text-texto">{pauta.estado}</span>
          </p>
        </div>
        <div className="flex gap-2">
          <button
            onClick={() => void api.baixar(`/turmas/${turmaId}/pauta?formato=pdf`)}
            className="botao-fantasma"
          >
            Descarregar PDF
          </button>
          {pauta.estado !== 'HOMOLOGADA' && (
            <button onClick={() => void homologar()} disabled={aHomologar} className="botao">
              {aHomologar ? 'A homologar…' : 'Homologar'}
            </button>
          )}
        </div>
      </div>

      <div className="mb-4 grid grid-cols-3 gap-3">
        <div className="cartao p-4">
          <p className="text-xs uppercase tracking-wide text-aco-suave">Inscritos</p>
          <p className="mt-1 font-display text-2xl font-semibold text-tinta">{pauta.total_inscritos}</p>
        </div>
        <div className="cartao p-4">
          <p className="text-xs uppercase tracking-wide text-aco-suave">Aprovados</p>
          <p className="mt-1 font-display text-2xl font-semibold text-tinta">{pauta.total_aprovados}</p>
        </div>
        <div className="cartao p-4">
          <p className="text-xs uppercase tracking-wide text-aco-suave">Taxa de aprovação</p>
          <p className="mt-1 font-display text-2xl font-semibold text-tinta">{pauta.taxa_aprovacao}%</p>
        </div>
      </div>

      <div className="tabela-wrap">
        <table className="tabela">
          <thead>
            <tr>
              <th>Nº</th>
              <th>Nome</th>
              {pauta.avaliacoes.map((a) => (
                <th key={a.id} className="num">
                  {a.designacao}
                  <span className="block text-[0.65rem] font-normal normal-case opacity-70">{a.tipo === 'EXAME' ? 'à parte' : `${Number(a.peso)}%`}</span>
                </th>
              ))}
              <th className="num">Frequência</th>
              <th className="num">Final</th>
              <th>Situação</th>
            </tr>
          </thead>
          <tbody>
            {pgLinhas.visiveis.map((l) => (
              <tr key={l.inscricao_id}>
                <td className="num">{l.numero_estudante}</td>
                <td>{l.nome_completo}</td>
                {pauta.avaliacoes.map((a) => {
                  const n = l.notas[String(a.id)]
                  const texto = !n || (n.valor === null && !n.faltou) ? '—' : n.faltou ? 'F' : n.valor
                  return (
                    <td key={a.id} className={`num ${n && !n.publicada ? 'italic text-aco-suave' : ''}`}>
                      {texto}
                    </td>
                  )
                })}
                <td className="num">{l.nota_frequencia ?? '—'}</td>
                <td className="num font-semibold">{l.classificacao_final ?? '—'}</td>
                <td>
                  <span className={`selo ${SELO_FINAL[l.situacao_final].estilo}`}>{SELO_FINAL[l.situacao_final].rotulo}</span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <Paginacao coleccao={pgLinhas.coleccao} aoMudar={pgLinhas.setPagina} />

      <p className="mt-3 text-xs text-aco">
        F = faltou. As notas a cinzento e em itálico estão gravadas mas ainda não foram publicadas. A frequência
        calcula-se quando todos os testes e trabalhos estiverem publicados; a nota final, depois do exame (ou logo,
        para quem fica dispensado ou excluído).
      </p>
    </div>
  )
}
