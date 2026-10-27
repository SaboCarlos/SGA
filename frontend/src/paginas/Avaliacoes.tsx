import { FormEvent, useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import { Paginacao, usePaginacaoLocal } from '@/componentes/Paginacao'
import type { Avaliacao, Classificacao, EstruturaAvaliacao, ResumoPublicacao, Turma } from '@/tipos/api'

const NOVA_AVALIACAO = { designacao: '', tipo: 'TESTE' as Avaliacao['tipo'], peso: '20', data_prevista: '' }

const ESTADO: Record<Avaliacao['estado'], { rotulo: string; estilo: string }> = {
  RASCUNHO: { rotulo: 'Por lançar', estilo: 'selo-neutro' },
  ACTIVA: { rotulo: 'Em lançamento', estilo: 'selo-aviso' },
  PUBLICADA: { rotulo: 'Publicada', estilo: 'selo-sucesso' },
}

/** Avaliações de uma turma: definir, lançar classificações, publicar (UC19, UC20, UC21). */
export function Avaliacoes() {
  const { turmaId } = useParams<{ turmaId: string }>()
  const [turma, setTurma] = useState<Turma | null>(null)
  const [estrutura, setEstrutura] = useState<EstruturaAvaliacao | null>(null)
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(NOVA_AVALIACAO)
  const [aberta, setAberta] = useState<Avaliacao | null>(null)
  const [classificacoes, setClassificacoes] = useState<Classificacao[] | null>(null)
  const [valores, setValores] = useState<Record<string, string>>({})
  const [faltas, setFaltas] = useState<Record<string, boolean>>({})
  const [resumo, setResumo] = useState<ResumoPublicacao | null>(null)
  const [erro, setErro] = useState<string | null>(null)
  const [erroAvaliacao, setErroAvaliacao] = useState<string | null>(null)
  const [gravado, setGravado] = useState(false)

  // Notas publicadas só se consultam; a correcção é por rectificação.
  const pgClassificacoes = usePaginacaoLocal(classificacoes)
  const soLeitura = aberta?.estado === 'PUBLICADA'

  const pesoExame = (estrutura?.avaliacoes ?? []).filter((a) => a.tipo === 'EXAME').reduce((t, a) => t + Number(a.peso), 0)
  const pesoFrequencia = (estrutura?.avaliacoes ?? []).filter((a) => a.tipo !== 'EXAME').reduce((t, a) => t + Number(a.peso), 0)

  useEffect(() => {
    api.get<Turma>(`/turmas/${turmaId}`).then(setTurma).catch(() => undefined)
    carregar()
  }, [turmaId])

  function carregar() {
    api
      .get<EstruturaAvaliacao>(`/turmas/${turmaId}/avaliacoes`)
      .then(setEstrutura)
      .catch(() => setErro('Não foi possível carregar as avaliações.'))
  }

  async function submeterAvaliacao(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)

    try {
      await api.post(`/turmas/${turmaId}/avaliacoes`, { ...formulario, peso: Number(formulario.peso) })
      setFormulario(NOVA_AVALIACAO)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível definir a avaliação.')
    }
  }

  function abrirClassificacoes(avaliacao: Avaliacao) {
    setAberta(avaliacao)
    setResumo(null)
    setErroAvaliacao(null)
    setGravado(false)
    api.get<Classificacao[]>(`/avaliacoes/${avaliacao.id}/classificacoes`).then((lista) => {
      setClassificacoes(lista)
      setValores(Object.fromEntries(lista.map((c) => [c.inscricao_id, c.valor?.toString() ?? ''])))
      setFaltas(Object.fromEntries(lista.map((c) => [c.inscricao_id, c.faltou])))
    })
  }

  function gravarGrelha(avaliacao: Avaliacao, lista: Classificacao[]) {
    return api.put(`/avaliacoes/${avaliacao.id}/classificacoes`, {
      classificacoes: lista.map((c) => ({
        inscricao_id: c.inscricao_id,
        valor: faltas[c.inscricao_id] ? null : valores[c.inscricao_id] ? Number(valores[c.inscricao_id]) : null,
        faltou: faltas[c.inscricao_id] ?? false,
      })),
    })
  }

  async function lancarClassificacoes() {
    if (!aberta || !classificacoes) return
    setErroAvaliacao(null)

    try {
      await gravarGrelha(aberta, classificacoes)
      abrirClassificacoes(aberta)
      setGravado(true)
      carregar()
    } catch (e: unknown) {
      setErroAvaliacao(e instanceof ErroApi ? e.message : 'Não foi possível gravar as notas.')
    }
  }

  async function publicar(confirmarIncompleta: boolean) {
    if (!aberta) return
    setErroAvaliacao(null)

    try {
      // Grava primeiro o que está na grelha: quem escreve as notas e carrega
      // logo em Publicar não as deve perder.
      if (classificacoes && classificacoes.length > 0) {
        await gravarGrelha(aberta, classificacoes)
      }
      const resposta = await api.post<ResumoPublicacao>(`/avaliacoes/${aberta.id}/publicar`, {
        confirmar_incompleta: confirmarIncompleta,
      })
      setResumo(resposta)
      setAberta({ ...aberta, estado: 'PUBLICADA' })
      carregar()
    } catch (e: unknown) {
      // US-18, fluxo A1: só a falta de notas se pode confirmar. As outras
      // recusas (por exemplo, pesos que não somam 100%) mostram-se como erro.
      if (e instanceof ErroApi && e.estado === 409 && e.codigoRegra === 'US-18') {
        if (window.confirm(`${e.message}\n\nPublicar mesmo assim?`)) {
          void publicar(true)
          return
        }
      }
      setErroAvaliacao(e instanceof ErroApi ? e.message : 'Não foi possível publicar.')
    }
  }

  return (
    <div>
      <h1 className="titulo-pagina">Avaliações e notas</h1>
      <p className="mb-2 text-sm text-aco">
        {turma ? `${turma.disciplina.codigo} — ${turma.disciplina.designacao}, turma ${turma.designacao}` : '…'}
      </p>
      <p className="mb-6 text-sm text-aco">
        Em cada avaliação, carregue em <strong>Lançar notas</strong>, escreva as notas e carregue em{' '}
        <strong>Publicar</strong>. Pode publicar uma avaliação de cada vez, à medida que as corrige.
      </p>

      {erro && <Alerta>{erro}</Alerta>}

      {estrutura && (
        <div className="cartao mb-4 flex flex-wrap items-center justify-between gap-3 px-4 py-3">
          <div className="text-sm text-texto">
            <p>
              Testes e trabalhos: <strong>{pesoFrequencia}%</strong> · Exame: <strong>{pesoExame}%</strong> · Total:{' '}
              <strong>{estrutura.soma_pesos}%</strong>{' '}
              {estrutura.estrutura_valida ? (
                <span className="selo selo-sucesso">pronto para publicar</span>
              ) : (
                <span className="selo selo-aviso">o total tem de ser 100%</span>
              )}
            </p>
            <p className="mt-1 text-xs text-aco">
              A frequência é a média dos testes e trabalhos (pelos seus pesos) e calcula-se quando estiverem todos
              publicados. Com 14 ou mais, o aluno fica dispensado; de 10 a 13,9 vai a exame; abaixo de 10 fica
              excluído. Para quem vai a exame, a nota final é a média entre a frequência e o exame.
            </p>
          </div>
          <button onClick={() => setAMostrarFormulario((v) => !v)} className="botao px-3 py-1.5">
            {aMostrarFormulario ? 'Cancelar' : 'Nova avaliação'}
          </button>
        </div>
      )}

      {aMostrarFormulario && (
        <form onSubmit={submeterAvaliacao} className="cartao mb-6 grid grid-cols-2 gap-3 p-4">
          <input required placeholder="Designação (ex.: Teste 1)" value={formulario.designacao}
            onChange={(e) => setFormulario({ ...formulario, designacao: e.target.value })}
            className="entrada" />
          <select value={formulario.tipo} onChange={(e) => setFormulario({ ...formulario, tipo: e.target.value as Avaliacao['tipo'] })}
            className="entrada">
            <option value="TESTE">Teste</option>
            <option value="TRABALHO">Trabalho</option>
            <option value="PRATICA">Prática</option>
            <option value="EXAME">Exame</option>
          </select>
          <input required type="number" step="0.01" placeholder="Peso (%)" value={formulario.peso}
            onChange={(e) => setFormulario({ ...formulario, peso: e.target.value })}
            className="entrada" />
          <input type="date" value={formulario.data_prevista}
            onChange={(e) => setFormulario({ ...formulario, data_prevista: e.target.value })}
            className="entrada" />
          <button type="submit" className="botao col-span-2 mt-2">
            Definir avaliação
          </button>
        </form>
      )}

      {estrutura && (
        <div className="tabela-wrap">
          <table className="tabela">
            <thead>
              <tr>
                <th>Designação</th>
                <th>Tipo</th>
                <th className="num">Peso</th>
                <th>Estado</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {estrutura.avaliacoes.map((a) => (
                <tr key={a.id}>
                  <td>{a.designacao}</td>
                  <td>{a.tipo}</td>
                  <td className="num">{a.peso}%</td>
                  <td><span className={`selo ${ESTADO[a.estado].estilo}`}>{ESTADO[a.estado].rotulo}</span></td>
                  <td className="text-right">
                    {a.estado === 'PUBLICADA' ? (
                      <button onClick={() => abrirClassificacoes(a)} className="text-tinta hover:underline">
                        Ver notas
                      </button>
                    ) : (
                      <button onClick={() => abrirClassificacoes(a)} className="botao px-3 py-1 text-sm">
                        Lançar notas
                      </button>
                    )}
                  </td>
                </tr>
              ))}
              {estrutura.avaliacoes.length === 0 && (
                <tr><td colSpan={5} className="py-6 text-center text-aco">Ainda não há avaliações. Carregue em <strong>Nova avaliação</strong> para criar a primeira (por exemplo, "Teste 1").</td></tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      {aberta && (
        <div className="cartao mt-6 p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="font-display text-sm font-semibold text-texto">
              Notas — {aberta.designacao}
              {soLeitura && <span className="ml-2 font-normal text-aco">(publicadas; para corrigir, peça uma rectificação)</span>}
            </h2>
            <button onClick={() => setAberta(null)} className="text-sm text-aco hover:underline">Fechar</button>
          </div>

          {erroAvaliacao && <Alerta>{erroAvaliacao}</Alerta>}
          {resumo && (
            <Alerta tipo="sucesso">
              Notas publicadas — os alunos já as vêem.{' '}
              {resumo.frequencia_calculada
                ? `Frequência calculada: ${resumo.dispensados} dispensado(s), ${resumo.admitidos_exame} admitido(s) a exame, ${resumo.reprovados_frequencia} excluído(s).`
                : 'A nota de frequência será calculada quando todos os testes e trabalhos estiverem publicados.'}
            </Alerta>
          )}

          <table className="tabela mb-4">
            <thead>
              <tr>
                <th>Estudante</th>
                <th>Nota (0–20)</th>
                <th>Faltou</th>
              </tr>
            </thead>
            <tbody>
              {pgClassificacoes.visiveis.map((c) => (
                <tr key={c.inscricao_id}>
                  <td>{c.numero_estudante} — {c.nome_completo}</td>
                  <td>
                    <input
                      type="number" min={0} max={20} step="0.1"
                      disabled={soLeitura || faltas[c.inscricao_id]}
                      value={valores[c.inscricao_id] ?? ''}
                      onChange={(e) => setValores({ ...valores, [c.inscricao_id]: e.target.value })}
                      className="entrada w-20 py-1 disabled:bg-superficie-alt"
                    />
                  </td>
                  <td>
                    <input
                      type="checkbox"
                      disabled={soLeitura}
                      checked={faltas[c.inscricao_id] ?? false}
                      onChange={(e) => setFaltas({ ...faltas, [c.inscricao_id]: e.target.checked })}
                    />
                  </td>
                </tr>
              ))}
              {classificacoes?.length === 0 && (
                <tr><td colSpan={3} className="py-4 text-center text-aco">Sem estudantes inscritos nesta turma.</td></tr>
              )}
            </tbody>
          </table>
          <Paginacao coleccao={pgClassificacoes.coleccao} aoMudar={pgClassificacoes.setPagina} />

          {!soLeitura && (
            <div className="flex flex-wrap items-center gap-2">
              <button onClick={() => void lancarClassificacoes()} className="botao-fantasma">
                Gravar notas
              </button>
              <button onClick={() => void publicar(false)} className="botao">
                Publicar
              </button>
              {gravado && <span className="text-sm text-sucesso">Notas gravadas. Os alunos ainda não as vêem.</span>}
            </div>
          )}
        </div>
      )}
    </div>
  )
}
