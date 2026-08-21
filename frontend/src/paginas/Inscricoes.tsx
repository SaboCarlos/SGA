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
      <div className="max-w-lg border border-green-200 bg-green-50 p-6">
        <h1 className="mb-2 text-lg font-semibold text-green-800">Inscrição confirmada</h1>
        <p className="mb-4 text-sm text-green-700">
          {comprovativo.ano_lectivo} · Semestre {comprovativo.semestre} ·{' '}
          {new Date(comprovativo.emitido_em).toLocaleString('pt-PT')}
        </p>
        <ul className="mb-4 space-y-1 text-sm text-slate-700">
          {comprovativo.disciplinas.map((d) => (
            <li key={d.codigo}>
              {d.codigo} — {d.designacao} (turma {d.turma})
            </li>
          ))}
        </ul>
        <button
          onClick={() => setComprovativo(null)}
          className="bg-escn-azul px-4 py-2 text-sm text-white hover:bg-opacity-90"
        >
          Voltar às inscrições
        </button>
      </div>
    )
  }

  return (
    <div>
      <h1 className="mb-1 text-lg font-semibold text-slate-800">Inscrição em disciplinas</h1>
      <p className="mb-6 text-sm text-slate-500">
        Selecciona as turmas do semestre. As disciplinas fora do teu plano
        curricular ou com pré-requisitos em falta não aparecem na lista.
      </p>

      {erro && (
        <div role="alert" className="mb-4 bg-red-50 px-4 py-3 text-sm text-red-700">
          {typeof erro === 'string' ? (
            erro
          ) : (
            <>
              <span className="font-mono text-xs text-red-500">{erro.codigo}</span> — {erro.mensagem}
            </>
          )}
        </div>
      )}

      {turmas === null && <p className="text-sm text-slate-500">A carregar turmas…</p>}

      {turmas?.length === 0 && !erro && (
        <p className="text-sm text-slate-500">
          Não há turmas disponíveis para inscrição neste momento.
        </p>
      )}

      {turmas && turmas.length > 0 && (
        <div className="overflow-x-auto border border-slate-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-2"></th>
                <th className="px-4 py-2">Disciplina</th>
                <th className="px-4 py-2">Turma</th>
                <th className="px-4 py-2">Docente</th>
                <th className="px-4 py-2">Vagas</th>
              </tr>
            </thead>
            <tbody>
              {turmas.map((t) => (
                <tr key={t.id} className="border-t border-slate-100">
                  <td className="px-4 py-2">
                    <input
                      type="checkbox"
                      checked={seleccionadas.has(t.id)}
                      onChange={() => alternar(t.id)}
                      disabled={t.vagas === 0}
                    />
                  </td>
                  <td className="px-4 py-2">
                    {t.disciplina.codigo} — {t.disciplina.designacao}
                  </td>
                  <td className="px-4 py-2">{t.designacao}</td>
                  <td className="px-4 py-2">{t.docente}</td>
                  <td className="px-4 py-2">
                    {t.vagas === 0 ? (
                      <span className="text-red-600">Esgotada</span>
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

      <button
        onClick={() => void submeter()}
        disabled={seleccionadas.size === 0 || aSubmeter}
        className="mt-4 bg-escn-azul px-4 py-2 text-sm font-medium text-white transition hover:bg-opacity-90 disabled:opacity-50"
      >
        {aSubmeter ? 'A inscrever…' : `Inscrever (${seleccionadas.size})`}
      </button>
    </div>
  )
}
