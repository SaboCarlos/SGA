import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '@/servicos/api'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'
import { Alerta } from '@/componentes/Alerta'
import type { Notificacao, Perfil } from '@/tipos/api'

interface CursoCoordenado {
  curso: string
  total_estudantes: number
  inscricoes_activas: number
  rectificacoes_pendentes: number
}

interface DadosPainel {
  // Estudante
  disciplinas_inscritas?: number
  disciplinas_aprovadas?: number
  estado?: string
  // Docente
  turmas_atribuidas?: number
  avaliacoes_por_publicar?: number
  // Coordenador (docente com curso atribuído)
  cursos_coordenados?: CursoCoordenado[]
  // Administrador
  total_estudantes?: number
  total_inscricoes_activas?: number
  total_turmas?: number
}

type ChaveIndicador = Exclude<keyof DadosPainel, 'estado' | 'cursos_coordenados'>

const INDICADOR: Record<ChaveIndicador, { rotulo: string; nota: string }> = {
  disciplinas_inscritas: { rotulo: 'Disciplinas inscritas', nota: 'no semestre corrente' },
  disciplinas_aprovadas: { rotulo: 'Disciplinas aprovadas', nota: 'desde o ingresso' },
  turmas_atribuidas: { rotulo: 'Turmas atribuídas', nota: 'a leccionar este semestre' },
  avaliacoes_por_publicar: { rotulo: 'Avaliações por publicar', nota: 'ainda em rascunho ou activas' },
  total_estudantes: { rotulo: 'Total de estudantes', nota: 'em todos os cursos' },
  total_inscricoes_activas: { rotulo: 'Inscrições activas', nota: 'neste momento' },
  total_turmas: { rotulo: 'Total de turmas', nota: 'no ano lectivo corrente' },
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
  perfis?: Perfil[]
}

const ATALHOS: Atalho[] = [
  { rota: '/inscricoes', rotulo: 'Inscrever-me', descricao: 'Escolher turmas do semestre', perfis: ['ESTUDANTE'] },
  { rota: '/turmas', rotulo: 'As minhas turmas', descricao: 'Avaliações e pautas', perfis: ['DOCENTE'] },
  { rota: '/relatorios', rotulo: 'Relatórios', descricao: 'Aproveitamento por disciplina', perfis: ['ADMINISTRADOR', 'COORDENADOR'] },
  { rota: '/estudantes', rotulo: 'Estudantes', descricao: 'Cadastro e pesquisa', perfis: ['ADMINISTRADOR', 'COORDENADOR'] },
  { rota: '/rectificacoes', rotulo: 'Rectificações', descricao: 'Pedidos de correcção de notas' },
  { rota: '/ano-lectivo', rotulo: 'Ano lectivo', descricao: 'Períodos de inscrição e notas', perfis: ['ADMINISTRADOR'] },
  { rota: '/auditoria', rotulo: 'Auditoria', descricao: 'Registo de acções sensíveis', perfis: ['ADMINISTRADOR'] },
  { rota: '/notificacoes', rotulo: 'Notificações', descricao: 'Ver todas' },
]

/**
 * Painel inicial (UC30 / RF-60, US-23, US-27). A estrutura devolvida por
 * GET /painel varia consoante o(s) perfil(is) do utilizador autenticado —
 * um utilizador com vários perfis (ex.: Docente e Coordenador) vê os
 * indicadores de todos combinados, por isso as secções abaixo aparecem
 * apenas quando os dados correspondentes vêm na resposta. Os atalhos e as
 * notificações recentes reaproveitam endpoints já existentes, só para dar
 * ao painel um ponto de partida — não são indicadores novos no servidor.
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
      .then((lista) => setNotificacoes(lista.slice(0, 4)))
      .catch(() => setNotificacoes([]))
  }, [])

  const indicadores = dados
    ? (Object.keys(INDICADOR) as ChaveIndicador[])
        .filter((chave) => dados[chave] !== undefined)
        .map((chave) => ({ chave, ...INDICADOR[chave], valor: dados[chave] as number }))
    : []

  const atalhos = ATALHOS.filter((a) => !a.perfis || temPerfil(...a.perfis))
  const naoLidas = notificacoes?.filter((n) => !n.lida_em).length ?? 0

  return (
    <div>
      <h1 className="titulo-pagina mb-1">Olá, {utilizador?.nome?.split(' ')[0]}</h1>
      <p className="mb-6 text-sm text-aco">Perfil activo: {utilizador?.perfis.join(', ')}</p>

      {erro && <Alerta>{erro}</Alerta>}

      {!dados && !erro && <p className="text-sm text-aco">A carregar indicadores…</p>}

      {dados && (
        <div className="space-y-8">
          {(indicadores.length > 0 || dados.estado) && (
            <div className="grid grid-cols-1 gap-px border border-linha bg-linha sm:grid-cols-[repeat(auto-fit,minmax(200px,1fr))]">
              {indicadores.map(({ chave, rotulo, nota, valor }) => (
                <div key={chave} className="bg-superficie p-4">
                  <p className="text-xs uppercase tracking-wide text-aco-suave">{rotulo}</p>
                  <p className="mt-1 font-display text-3xl font-semibold text-tinta">{valor}</p>
                  <p className="mt-1 text-xs text-aco-suave">{nota}</p>
                </div>
              ))}
              {dados.estado && (
                <div className="bg-superficie p-4">
                  <p className="text-xs uppercase tracking-wide text-aco-suave">Estado da matrícula</p>
                  <p className="mt-2">
                    <span className={`selo ${ESTILO_ESTADO[dados.estado] ?? 'selo-neutro'}`}>{dados.estado}</span>
                  </p>
                </div>
              )}
            </div>
          )}

          {dados.cursos_coordenados && dados.cursos_coordenados.length > 0 && (
            <div>
              <h2 className="mb-2 font-display text-base font-semibold text-texto">Cursos coordenados</h2>
              <div className="tabela-wrap">
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
                            <Link to="/rectificacoes" className="text-tinta hover:underline">
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
            </div>
          )}

          <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div className="lg:col-span-2">
              <div className="mb-2 flex items-center justify-between">
                <h2 className="font-display text-base font-semibold text-texto">Notificações recentes</h2>
                {naoLidas > 0 && <span className="selo selo-aviso">{naoLidas} por ler</span>}
              </div>

              {notificacoes === null && <p className="text-sm text-aco">A carregar…</p>}

              {notificacoes && notificacoes.length > 0 && (
                <div className="cartao divide-y divide-linha">
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
                <p className="cartao p-4 text-sm text-aco">Sem notificações por agora.</p>
              )}
            </div>

            <div>
              <h2 className="mb-2 font-display text-base font-semibold text-texto">Acesso rápido</h2>
              <div className="cartao divide-y divide-linha">
                {atalhos.map((a) => (
                  <Link key={a.rota} to={a.rota} className="block p-3 hover:bg-superficie-alt">
                    <p className="text-sm font-medium text-texto">{a.rotulo}</p>
                    <p className="text-xs text-aco">{a.descricao}</p>
                  </Link>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
