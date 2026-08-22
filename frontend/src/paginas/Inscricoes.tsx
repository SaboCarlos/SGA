import { useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import type { ComprovativoInscricao, ErroNegocioApi, Turma } from '@/tipos/api'

/**
 * Inscrição em disciplinas (UC14 / US-13).
 *
 * Segue o fluxo desenhado na Fase 2 (diagrama de sequência
 * docs/diagramas/sequencia-inscricao.png): a listagem em
 * GET /inscricoes/disponiveis já filtra por elegibilidade, mas a submissão
 * revalida tudo no servidor — por isso os erros de regra (RN-01 a RN-05)
 * são tratados explicitamente e mostrados com o código da regra violada,
 * nunca escondidos atrás de uma mensagem genérica.
 */
export function Inscricoes() {
  const [turmas, setTurmas] = useState<Turma[] | null>(null)
  const [seleccionadas, setSeleccionadas] = useState<Set<number>>(new Set())
  const [erro, setErro] = useState<ErroNegocioApi | string | null>(null)
  const [comprovativo, setComprovativo] = useState<ComprovativoInscricao | null>(null)
  const [aSubmeter, setASubmeter] = useState(false)

  useEffect(() => {
    carregarDisponiveis()
  }, [])

  function carregarDisponiveis() {
    setErro(null)
    api
      .get<Turma[]>('/inscricoes/disponiveis')
      .then(setTurmas)
      .catch((e: unknown) => {
        if (e instanceof ErroApi && e.estado === 409) {
          setErro(e.message) // período de inscrições encerrado (RN-05)
        } else {
          setErro('Não foi possível carregar as turmas disponíveis.')
        }
        setTurmas([])
      })
  }

  function alternar(turmaId: number) {
    setSeleccionadas((actual) => {
      const nova = new Set(actual)
      nova.has(turmaId) ? nova.delete(turmaId) : nova.add(turmaId)
      return nova
    })
  }

  async function submeter() {
    if (seleccionadas.size === 0) return
    setASubmeter(true)
    setErro(null)

    try {
      const resposta = await api.post<ComprovativoInscricao>('/inscricoes', {
        turmas: Array.from(seleccionadas),
      })
      setComprovativo(resposta)
      setSeleccionadas(new Set())
      carregarDisponiveis()
    } catch (e: unknown) {
      if (e instanceof ErroApi && e.estado === 422) {
        // Corpo da resposta segue o esquema ErroNegocio (docs/openapi.yaml):
        // inclui o código da regra (ex.:"RN-02") e a mensagem explicativa.
        setErro({ codigo: e.codigoRegra ?? '?', mensagem: e.message })
      } else {
        setErro('Não foi possível concluir a inscrição.')
      }
    } finally {
      setASubmeter(false)
    }
  }

  if (comprovativo) {
    return (
      <div className="max-w-lg border border-sucesso bg-sucesso-bg p-6">
        <h1 className="mb-2 font-display text-lg font-semibold text-sucesso">Inscrição confirmada</h1>
        <p className="mb-4 text-sm text-sucesso">
          {comprovativo.ano_lectivo} · Semestre {comprovativo.semestre} ·{' '}
          {new Date(comprovativo.emitido_em).toLocaleString('pt-PT')}
        </p>
        <ul className="mb-4 space-y-1 text-sm text-texto">
          {comprovativo.disciplinas.map((d) => (
            <li key={d.codigo}>
              {d.codigo} — {d.designacao} (turma {d.turma})
            </li>
          ))}
        </ul>
        <button onClick={() => setComprovativo(null)} className="botao">
          Voltar às inscrições
        </button>
      </div>
    )
  }

  return (
    <div>
      <h1 className="titulo-pagina mb-1">Inscrição em disciplinas</h1>
      <p className="mb-6 text-sm text-aco">
        Selecciona as turmas do semestre. As disciplinas fora do teu plano
        curricular ou com pré-requisitos em falta não aparecem na lista.
      </p>

      {erro && (
        <div role="alert" className="alerta alerta-erro mb-4">
          {typeof erro === 'string' ? (
            erro
          ) : (
            <>
              <span className="font-mono text-xs">{erro.codigo}</span> — {erro.mensagem}
            </>
          )}
        </div>
      )}

      {turmas === null && <p className="text-sm text-aco">A carregar turmas…</p>}

      {turmas?.length === 0 && !erro && (
        <p className="text-sm text-aco">
          Não há turmas disponíveis para inscrição neste momento.
        </p>
      )}

      {turmas && turmas.length > 0 && (
        <div className="tabela-wrap">
          <table className="tabela">
            <thead>
              <tr>
                <th></th>
                <th>Disciplina</th>
                <th>Turma</th>
                <th>Docente</th>
                <th className="num">Vagas</th>
              </tr>
            </thead>
            <tbody>
              {turmas.map((t) => (
                <tr key={t.id}>
                  <td>
                    <input
                      type="checkbox"
                      checked={seleccionadas.has(t.id)}
                      onChange={() => alternar(t.id)}
                      disabled={t.vagas === 0}
                    />
                  </td>
                  <td>
                    {t.disciplina.codigo} — {t.disciplina.designacao}
                  </td>
                  <td>{t.designacao}</td>
                  <td>{t.docente}</td>
                  <td className="num">
                    {t.vagas === 0 ? (
                      <span className="selo selo-perigo">Esgotada</span>
                    ) : (
                      `${t.vagas} / ${t.capacidade_maxima}`
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <button onClick={() => void submeter()} disabled={seleccionadas.size === 0 || aSubmeter} className="botao mt-4">
        {aSubmeter ? 'A inscrever…' : `Inscrever (${seleccionadas.size})`}
      </button>
    </div>
  )
}
