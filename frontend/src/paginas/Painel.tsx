import { useEffect, useState } from 'react'
import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { api } from '@/servicos/api'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'
import { Alerta } from '@/componentes/Alerta'
import { Icone, type NomeIcone } from '@/componentes/Icone'
import { CartaoGrafico } from '@/componentes/graficos/CartaoGrafico'
import { GraficoAnel } from '@/componentes/graficos/GraficoAnel'
import { GraficoBarras } from '@/componentes/graficos/GraficoBarras'
import { GraficoBarrasEmpilhadas } from '@/componentes/graficos/GraficoBarrasEmpilhadas'
import {
  COR_ESTADO_AVALIACAO,
  COR_ESTADO_ESTUDANTE,
  COR_SITUACAO,
  numero,
  percentagem,
  rotular,
} from '@/componentes/graficos/paleta'
import type { DadosPainel, Notificacao, Perfil } from '@/tipos/api'

const INDICADOR: Record<string, { rotulo: string; nota: string }> = {
  disciplinas_inscritas: { rotulo: 'Disciplinas inscritas', nota: 'no semestre corrente' },
  disciplinas_aprovadas: { rotulo: 'Disciplinas aprovadas', nota: 'desde o ingresso' },
  turmas_atribuidas: { rotulo: 'Turmas atribuídas', nota: 'a leccionar este semestre' },
  avaliacoes_por_publicar: { rotulo: 'Avaliações por publicar', nota: 'ainda em rascunho ou activas' },
  total_estudantes: { rotulo: 'Total de estudantes', nota: 'em todos os cursos' },
  total_inscricoes_activas: { rotulo: 'Inscrições activas', nota: 'neste momento' },
  total_turmas: { rotulo: 'Total de turmas', nota: 'no ano lectivo corrente' },
}

const ORDEM_INDICADORES = Object.keys(INDICADOR)

/** Tratamentos que não são o nome de ninguém. */
const TRATAMENTOS = ['prof.', 'prof', 'professor', 'professora', 'dr.', 'dr', 'dra.', 'dra', 'eng.', 'eng']

/**
 * Primeiro nome para a saudação. Sem isto, o docente registado como
 * "Prof. Carlos Macuácua" era cumprimentado com "Olá, Prof.".
 */
function primeiroNome(nome: string | undefined): string {
  const partes = (nome ?? '').split(' ').filter(Boolean)

  return partes.find((parte) => !TRATAMENTOS.includes(parte.toLowerCase())) ?? partes[0] ?? ''
}

const ESTILO_ESTADO: Record<string, string> = {
  ACTIVO: 'selo-sucesso',
  SUSPENSO: 'selo-aviso',
  TRANSFERIDO: 'selo-neutro',
  GRADUADO: 'selo-neutro',
  DESISTENTE: 'selo-perigo',
}

interface Atalho {
  rota: string
  rotulo: string
  descricao: string
  icone: NomeIcone
  perfis?: Perfil[]
}

/** Quantos atalhos cabem no Acesso rápido; o resto está na barra lateral. */
const MAX_ATALHOS = 5

/**
 * Acesso rápido: as secções que cada perfil usa no dia a dia, por ordem de
 * uso, até MAX_ATALHOS. Não repete a navegação inteira — essa está na barra
 * lateral, e uma lista com tudo deixava o painel do administrador comprido.
 */
const ATALHOS: Atalho[] = [
  { rota: '/inscricoes', rotulo: 'Inscrever-me', descricao: 'Escolher turmas do semestre', icone: 'inscricoes', perfis: ['ESTUDANTE'] },
  { rota: '/notas', rotulo: 'As minhas notas', descricao: 'Notas de cada disciplina', icone: 'notas', perfis: ['ESTUDANTE'] },
  { rota: '/turmas', rotulo: 'Turmas', descricao: 'Avaliações, pautas e inscritos', icone: 'turmas', perfis: ['ADMINISTRADOR', 'COORDENADOR', 'DOCENTE'] },
  { rota: '/estudantes', rotulo: 'Estudantes', descricao: 'Cadastro, pesquisa e aproveitamento', icone: 'estudantes', perfis: ['ADMINISTRADOR', 'COORDENADOR'] },
  { rota: '/rectificacoes', rotulo: 'Rectificações', descricao: 'Pedidos de correcção de notas', icone: 'rectificacoes' },
  { rota: '/relatorios', rotulo: 'Relatórios', descricao: 'Aproveitamento por disciplina', icone: 'relatorios', perfis: ['ADMINISTRADOR', 'COORDENADOR'] },
  { rota: '/notificacoes', rotulo: 'Notificações', descricao: 'Avisos do sistema', icone: 'notificacoes' },
  { rota: '/docentes', rotulo: 'Docentes', descricao: 'Corpo docente e atribuições', icone: 'docentes', perfis: ['ADMINISTRADOR'] },
  { rota: '/ano-lectivo', rotulo: 'Ano lectivo', descricao: 'Períodos de inscrição e notas', icone: 'calendario', perfis: ['ADMINISTRADOR'] },
]

/**
 * Painel inicial (UC30 / RF-60, US-23, US-27).
 *
 * A estrutura devolvida por GET /painel varia consoante o(s) perfil(is) do
 * utilizador autenticado — um utilizador com vários perfis (ex.: Docente e
 * Coordenador) vê os indicadores de todos combinados, por isso cada secção
 * abaixo só aparece quando os dados correspondentes vêm na resposta.
 *
 * Os números escalares ficam em cartões; as distribuições ficam em
 * gráficos. A divisão não é estética: um total não tem partes para mostrar,
 * e uma distribuição lida como número perde exactamente aquilo que a torna
 * informativa.
 */
export function Painel() {
  const { utilizador, temPerfil } = useAutenticacao()
  const [dados, setDados] = useState<DadosPainel | null>(null)
  const [notificacoes, setNotificacoes] = useState<Notificacao[] | null>(null)
  const [erro, setErro] = useState<string | null>(null)

  useEffect(() => {
    api
      .get<DadosPainel>('/painel')
      .then(setDados)
      .catch(() => setErro('Não foi possível carregar o painel.'))
    api
      .get<Notificacao[]>('/notificacoes')
      .then((lista) => setNotificacoes(lista.slice(0, 5)))
      .catch(() => setNotificacoes([]))
  }, [])

  const indicadores = dados
    ? ORDEM_INDICADORES.filter((chave) => typeof dados[chave as keyof DadosPainel] === 'number').map(
        (chave) => ({ chave, ...INDICADOR[chave], valor: dados[chave as keyof DadosPainel] as number }),
      )
    : []

  const atalhos = ATALHOS.filter((a) => !a.perfis || temPerfil(...a.perfis)).map((a) =>
    // O docente só vê as disciplinas que lecciona: o atalho diz-lhe isso.
    a.rota === '/turmas' && !temPerfil('ADMINISTRADOR', 'COORDENADOR')
      ? { ...a, rotulo: 'As minhas disciplinas', descricao: 'Alunos inscritos, lançamento de notas e pautas' }
      : a
  ).slice(0, MAX_ATALHOS)
  const naoLidas = notificacoes?.filter((n) => !n.lida_em).length ?? 0

  const ocupacao = dados?.ocupacao_turmas ?? dados?.ocupacao_minhas_turmas ?? []

  /**
   * Os cartões de gráfico que este utilizador vai ver, por ordem de
   * leitura. Montados como lista (e não escritos um a um no JSX) porque a
   * grelha precisa de saber quantos são para decidir a largura do último —
   * e o número varia com o perfil: um estudante vê um ou dois, um docente
   * que também coordena vê quatro.
   */
  const cartoesGraficos: Array<{ chave: string; conteudo: ReactNode }> = []

  if (dados?.estudantes_por_estado) {
    cartoesGraficos.push({
      chave: 'estudantes_por_estado',
      conteudo: (
        <CartaoGrafico
          titulo="Estudantes por estado de matrícula"
          descricao="Distribuição do corpo discente no âmbito a que tem acesso."
          total={dados.estudantes_por_estado.reduce((s, c) => s + c.total, 0)}
        >
          <GraficoAnel
            legendaCentro="estudantes"
            fatias={dados.estudantes_por_estado.map((c) => ({
              rotulo: rotular(c.rotulo),
              total: c.total,
              cor: COR_ESTADO_ESTUDANTE[c.rotulo] ?? 'var(--serie-1)',
            }))}
          />
        </CartaoGrafico>
      ),
    })
  }

  if (dados?.inscricoes_por_situacao) {
    cartoesGraficos.push({
      chave: 'inscricoes_por_situacao',
      conteudo: (
        <CartaoGrafico
          titulo="Situação das inscrições"
          descricao="Inscrições não anuladas, por situação final."
          total={dados.inscricoes_por_situacao.reduce((s, c) => s + c.total, 0)}
        >
          <GraficoAnel
            legendaCentro="inscrições"
            fatias={dados.inscricoes_por_situacao.map((c) => ({
              rotulo: rotular(c.rotulo),
              total: c.total,
              cor: COR_SITUACAO[c.rotulo] ?? 'var(--serie-1)',
            }))}
          />
        </CartaoGrafico>
      ),
    })
  }

  if (dados?.situacao_disciplinas) {
    cartoesGraficos.push({
      chave: 'situacao_disciplinas',
      conteudo: (
        <CartaoGrafico
          titulo="As minhas disciplinas"
          descricao="Todas as disciplinas frequentadas, por situação."
          total={dados.situacao_disciplinas.reduce((s, c) => s + c.total, 0)}
        >
          <GraficoAnel
            legendaCentro="disciplinas"
            fatias={dados.situacao_disciplinas.map((c) => ({
              rotulo: rotular(c.rotulo),
              total: c.total,
              cor: COR_SITUACAO[c.rotulo] ?? 'var(--serie-1)',
            }))}
          />
        </CartaoGrafico>
      ),
    })
  }

  if (dados?.avaliacoes_por_estado) {
    cartoesGraficos.push({
      chave: 'avaliacoes_por_estado',
      conteudo: (
        <CartaoGrafico
          titulo="Avaliações por estado"
          descricao="Avaliações das turmas que lecciona."
          total={dados.avaliacoes_por_estado.reduce((s, c) => s + c.total, 0)}
        >
          <GraficoAnel
            legendaCentro="avaliações"
            fatias={dados.avaliacoes_por_estado.map((c) => ({
              rotulo: rotular(c.rotulo),
              total: c.total,
              cor: COR_ESTADO_AVALIACAO[c.rotulo] ?? 'var(--serie-1)',
            }))}
          />
        </CartaoGrafico>
      ),
    })
  }

  if (dados?.notas_por_disciplina && dados.notas_por_disciplina.length > 0) {
    cartoesGraficos.push({
      chave: 'notas_por_disciplina',
      conteudo: (
        <CartaoGrafico
          titulo="As minhas classificações"
          descricao="Classificações finais já lançadas, na escala de 0 a 20."
          total={dados.notas_por_disciplina.length}
        >
          <GraficoBarras
            maximo={20}
            referencia={{ valor: 10, rotulo: '10 valores — mínimo de aprovação' }}
            linhas={dados.notas_por_disciplina.map((n) => ({
              rotulo: n.disciplina,
              detalhe: n.codigo,
              valor: n.nota,
            }))}
          />
        </CartaoGrafico>
      ),
    })
  }

  if (ocupacao.length > 0) {
    cartoesGraficos.push({
      chave: 'ocupacao',
      conteudo: (
        <CartaoGrafico
          titulo="Ocupação das turmas"
          descricao="Inscritos activos sobre a capacidade máxima (RN-04)."
          total={ocupacao.length}
          accao={
            <Link to="/turmas" className="shrink-0 text-sm text-tinta hover:underline">
              Ver turmas
            </Link>
          }
        >
          <GraficoBarras
            maximo={100}
            unidade="%"
            linhas={ocupacao.map((t) => ({
              rotulo: t.disciplina ?? t.turma,
              detalhe: `${t.codigo ?? ''} · Turma ${t.turma}`.trim(),
              valor: t.ocupacao,
              texto: `${numero(t.inscritos)}/${numero(t.capacidade)}`,
              selo: t.inscritos >= t.capacidade ? <span className="selo selo-aviso">Esgotada</span> : undefined,
            }))}
          />
        </CartaoGrafico>
      ),
    })
  }

  return (
    <div>
      <h1 className="titulo-pagina mb-1">Olá, {primeiroNome(utilizador?.nome)}</h1>
      <p className="mb-6 text-sm text-aco">Perfil activo: {utilizador?.perfis.join(', ')}</p>

      {erro && <Alerta>{erro}</Alerta>}

      {!dados && !erro && <p className="text-sm text-aco">A carregar indicadores…</p>}

      {dados && (
        <div className="space-y-6">
          {(indicadores.length > 0 || dados.estado) && (
            <div className="grid grid-cols-1 gap-px border border-linha bg-linha sm:grid-cols-[repeat(auto-fit,minmax(200px,1fr))]">
              {indicadores.map(({ chave, rotulo, nota, valor }) => (
                <div key={chave} className="bg-superficie p-4">
                  <p className="text-xs uppercase tracking-wide text-aco-suave">{rotulo}</p>
                  <p className="mt-1 text-3xl font-semibold text-texto">{numero(valor)}</p>
                  <p className="mt-1 text-xs text-aco-suave">{nota}</p>
                </div>
              ))}
              {dados.estado && (
                <div className="bg-superficie p-4">
                  <p className="text-xs uppercase tracking-wide text-aco-suave">Estado da matrícula</p>
                  <p className="mt-2">
                    <span className={`selo ${ESTILO_ESTADO[dados.estado] ?? 'selo-neutro'}`}>
                      {rotular(dados.estado)}
                    </span>
                  </p>
                </div>
              )}
            </div>
          )}

          {/* ---------------- Distribuições ---------------- */}

          <div className="grid grid-cols-1 items-start gap-4 lg:grid-cols-2">
            {cartoesGraficos.map((cartao, indice) => (
              <div
                key={cartao.chave}
                // Número ímpar de cartões deixaria o último sozinho com
                // metade da grelha vazia ao lado. A partir de dois, o
                // último passa a ocupar a largura toda — e os cartões que
                // sobram são justamente as listas (ocupação, notas), as
                // que mais ganham com espaço horizontal.
                className={
                  cartoesGraficos.length > 1 && cartoesGraficos.length % 2 === 1 && indice === cartoesGraficos.length - 1
                    ? 'lg:col-span-2'
                    : ''
                }
              >
                {cartao.conteudo}
              </div>
            ))}
          </div>

          {/* ---------------- Aproveitamento: gráfico + tabela ---------------- */}

          {dados.aproveitamento_disciplinas && dados.aproveitamento_disciplinas.length > 0 && (
            <CartaoGrafico
              titulo="Aproveitamento por disciplina"
              descricao="As dez disciplinas com mais inscritos. O relatório completo tem exportação em PDF e CSV."
              accao={
                <Link to="/relatorios" className="shrink-0 text-sm text-tinta hover:underline">
                  Relatório completo
                </Link>
              }
            >
              <GraficoBarrasEmpilhadas
                series={[
                  { chave: 'aprovados', rotulo: 'Aprovados', cor: COR_SITUACAO.APROVADO },
                  { chave: 'em_curso', rotulo: 'Em curso', cor: COR_SITUACAO.EM_CURSO },
                  { chave: 'reprovados', rotulo: 'Reprovados', cor: COR_SITUACAO.REPROVADO },
                ]}
                linhas={dados.aproveitamento_disciplinas.map((d) => ({
                  rotulo: d.disciplina,
                  detalhe: d.codigo,
                  valores: { aprovados: d.aprovados, em_curso: d.em_curso, reprovados: d.reprovados },
                }))}
              />

              {/* A leitura exacta do gráfico acima, para quem precisa do
                  número e não da forma — e para quem não distingue as cores. */}
              <details className="mt-5 border-t border-linha pt-3">
                <summary className="cursor-pointer text-sm text-tinta">Ver como tabela</summary>
                <div className="tabela-wrap mt-3">
                  <table className="tabela">
                    <thead>
                      <tr>
                        <th>Disciplina</th>
                        <th className="num">Inscritos</th>
                        <th className="num">Aprovados</th>
                        <th className="num">Em curso</th>
                        <th className="num">Reprovados</th>
                        <th className="num">Taxa</th>
                      </tr>
                    </thead>
                    <tbody>
                      {dados.aproveitamento_disciplinas.map((d) => (
                        <tr key={d.codigo}>
                          <td>
                            {d.codigo} — {d.disciplina}
                          </td>
                          <td className="num">{d.inscritos}</td>
                          <td className="num">{d.aprovados}</td>
                          <td className="num">{d.em_curso}</td>
                          <td className="num">{d.reprovados}</td>
                          <td className="num">{percentagem(d.aprovados, d.inscritos)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </details>
            </CartaoGrafico>
          )}

          {/* ---------------- Cursos coordenados (US-23) ---------------- */}

          {dados.cursos_coordenados && dados.cursos_coordenados.length > 0 && (
            <section className="cartao">
              <div className="cartao-cabecalho">
                <h2>Cursos que coordeno</h2>
                <Link to="/estudantes" className="text-sm text-tinta hover:underline">
                  Ver estudantes
                </Link>
              </div>
              <div className="tabela-wrap border-0">
                <table className="tabela">
                  <thead>
                    <tr>
                      <th>Curso</th>
                      <th className="num">Estudantes</th>
                      <th className="num">Inscrições activas</th>
                      <th className="num">Rectificações pendentes</th>
                    </tr>
                  </thead>
                  <tbody>
                    {dados.cursos_coordenados.map((c) => (
                      <tr key={c.curso}>
                        <td>{c.curso}</td>
                        <td className="num">{c.total_estudantes}</td>
                        <td className="num">{c.inscricoes_activas}</td>
                        <td className="num">
                          {c.rectificacoes_pendentes > 0 ? (
                            <Link to="/rectificacoes" className="font-semibold text-tinta hover:underline">
                              {c.rectificacoes_pendentes}
                            </Link>
                          ) : (
                            0
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </section>
          )}

          {/* ---------------- Notificações e atalhos ---------------- */}

          <div className="grid grid-cols-1 items-start gap-4 lg:grid-cols-3">
            <section className="cartao lg:col-span-2">
              <div className="cartao-cabecalho">
                <h2>Notificações recentes</h2>
                {naoLidas > 0 && <span className="selo selo-aviso">{naoLidas} por ler</span>}
              </div>

              {notificacoes === null && <p className="p-4 text-sm text-aco">A carregar…</p>}

              {notificacoes && notificacoes.length > 0 && (
                <div className="divide-y divide-linha">
                  {notificacoes.map((n) => (
                    <div key={n.id} className="flex items-start gap-3 p-3">
                      <span
                        className={`mt-1.5 h-2 w-2 shrink-0 ${n.lida_em ? 'bg-transparent' : 'bg-dourado'}`}
                        aria-hidden
                      />
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium text-texto">{n.titulo}</p>
                        {n.mensagem && <p className="truncate text-sm text-aco">{n.mensagem}</p>}
                      </div>
                      <span className="shrink-0 text-xs text-aco-suave">
                        {new Date(n.criada_em).toLocaleDateString('pt-PT')}
                      </span>
                    </div>
                  ))}
                  <Link to="/notificacoes" className="block p-3 text-center text-sm text-tinta hover:underline">
                    Ver todas
                  </Link>
                </div>
              )}

              {notificacoes && notificacoes.length === 0 && (
                <p className="p-4 text-sm text-aco">Sem notificações por agora.</p>
              )}
            </section>

            <section className="cartao">
              <div className="cartao-cabecalho">
                <h2>Acesso rápido</h2>
              </div>
              <div className="divide-y divide-linha">
                {atalhos.map((a) => (
                  <Link key={a.rota} to={a.rota} className="flex items-center gap-3 p-3 hover:bg-superficie-alt">
                    <Icone nome={a.icone} className="h-5 w-5 text-tinta" />
                    <div className="min-w-0">
                      <p className="text-sm font-medium text-texto">{a.rotulo}</p>
                      <p className="truncate text-xs text-aco">{a.descricao}</p>
                    </div>
                  </Link>
                ))}
              </div>
            </section>
          </div>
        </div>
      )}
    </div>
  )
}
