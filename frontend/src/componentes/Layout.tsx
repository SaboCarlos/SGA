import { useState } from 'react'
import { NavLink, Outlet } from 'react-router-dom'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'
import { ROTULO_TEMA, useTema } from '@/contextos/TemaContexto'
import { Icone, type NomeIcone } from '@/componentes/Icone'

const CHAVE_RECOLHIDA = 'sga_barra_recolhida'

interface ItemNav {
  rota: string
  rotulo: string
  grupo: string
  icone: NomeIcone
  perfis?: string[]
  /** Rótulo para quem não é da direcção (o docente só vê as suas). */
  rotuloDocente?: string
}

/**
 * A navegação inteira, em ordem de leitura.
 *
 * Todos os itens declaram o grupo a que pertencem, incluindo o primeiro
 * bloco: antes ficava sem título e a barra abria com cinco ligações soltas
 * seguidas de duas secções nomeadas, o que fazia parecer que faltava lá
 * qualquer coisa.
 *
 * `perfis` ausente significa "todos os perfis autenticados". O filtro aqui
 * é só de usabilidade — a autorização real está no servidor (RNF-08,
 * RNF-10) e é ela que decide.
 */
const ITENS_NAV: ItemNav[] = [
  { rota: '/painel', rotulo: 'Painel', grupo: 'Início', icone: 'painel' },

  { rota: '/inscricoes', rotulo: 'Inscrições', grupo: 'Académico', icone: 'inscricoes', perfis: ['ESTUDANTE'] },
  { rota: '/notas', rotulo: 'As minhas notas', grupo: 'Académico', icone: 'notas', perfis: ['ESTUDANTE'] },
  { rota: '/estudantes', rotulo: 'Estudantes', grupo: 'Académico', icone: 'estudantes', perfis: ['ADMINISTRADOR', 'COORDENADOR'] },
  { rota: '/docentes', rotulo: 'Docentes', grupo: 'Académico', icone: 'docentes', perfis: ['ADMINISTRADOR'] },
  { rota: '/cursos', rotulo: 'Cursos', grupo: 'Académico', icone: 'cursos', perfis: ['ADMINISTRADOR'] },
  { rota: '/disciplinas', rotulo: 'Disciplinas', grupo: 'Académico', icone: 'disciplinas', perfis: ['ADMINISTRADOR'] },
  // A direcção vê todas as turmas; o docente só as que lecciona (filtro do
  // servidor). O estudante não precisa: tem Inscrições e As minhas notas.
  { rota: '/turmas', rotulo: 'Turmas', rotuloDocente: 'As minhas disciplinas', grupo: 'Académico', icone: 'turmas', perfis: ['ADMINISTRADOR', 'COORDENADOR', 'DOCENTE'] },

  { rota: '/ano-lectivo', rotulo: 'Ano lectivo', grupo: 'Avaliação', icone: 'calendario', perfis: ['ADMINISTRADOR'] },
  { rota: '/rectificacoes', rotulo: 'Rectificações', grupo: 'Avaliação', icone: 'rectificacoes' },
  { rota: '/relatorios', rotulo: 'Relatórios', grupo: 'Avaliação', icone: 'relatorios', perfis: ['ADMINISTRADOR', 'COORDENADOR'] },

  { rota: '/notificacoes', rotulo: 'Notificações', grupo: 'Gestão', icone: 'notificacoes' },
  { rota: '/auditoria', rotulo: 'Auditoria', grupo: 'Gestão', icone: 'auditoria', perfis: ['ADMINISTRADOR', 'COORDENADOR'] },
  { rota: '/utilizadores', rotulo: 'Utilizadores', grupo: 'Gestão', icone: 'utilizadores', perfis: ['ADMINISTRADOR'] },
  { rota: '/perfis', rotulo: 'Perfis e permissões', grupo: 'Gestão', icone: 'perfis', perfis: ['ADMINISTRADOR'] },
]

/**
 * Casca comum a todos os ecrãs autenticados (UC30 — painel inicial por
 * perfil). A navegação lateral filtra-se pelos perfis do utilizador; a
 * mesma filtragem é apenas conveniência de interface — a autorização real
 * está no servidor.
 *
 * A barra lateral fica sempre em tinta profunda (moldura fixa da app),
 * independente do tema claro/escuro do resto do ecrã — ver src/index.css.
 */
export function Layout() {
  const { utilizador, terminarSessao, temPerfil } = useAutenticacao()
  const { tema, alternarTema } = useTema()
  const [menuAberto, setMenuAberto] = useState(false)
  // Só no ecrã largo: a barra encolhe para os ícones. A escolha fica no
  // browser; se o armazenamento falhar, abre expandida.
  const [recolhida, setRecolhida] = useState(() => {
    try {
      return localStorage.getItem(CHAVE_RECOLHIDA) === '1'
    } catch {
      return false
    }
  })

  function alternarBarra() {
    setRecolhida((v) => {
      try {
        localStorage.setItem(CHAVE_RECOLHIDA, v ? '0' : '1')
      } catch {
        // sem armazenamento: vale só para esta sessão
      }
      return !v
    })
  }

  const ehDireccao = temPerfil('ADMINISTRADOR', 'COORDENADOR')
  const itensVisiveis = ITENS_NAV.filter((item) => !item.perfis || temPerfil(...item.perfis)).map((item) =>
    item.rotuloDocente && !ehDireccao ? { ...item, rotulo: item.rotuloDocente } : item
  )

  // Agrupa depois de filtrar: um grupo cujos itens foram todos escondidos
  // pelo perfil não deve deixar o título orfão na barra.
  const grupos = itensVisiveis.reduce<Array<{ grupo: string; itens: ItemNav[] }>>((acc, item) => {
    const ultimo = acc[acc.length - 1]
    if (ultimo?.grupo === item.grupo) {
      ultimo.itens.push(item)
    } else {
      acc.push({ grupo: item.grupo, itens: [item] })
    }
    return acc
  }, [])

  return (
    <div className="flex min-h-screen flex-col bg-bruma md:flex-row">
      <header className="flex items-center justify-between bg-tinta-profunda px-4 py-3 md:hidden">
        <span className="font-display text-sm font-semibold text-white">SGA</span>
        <button
          onClick={() => setMenuAberto((v) => !v)}
          aria-expanded={menuAberto}
          aria-label="Menu de navegação"
          className="border border-white/25 px-3 py-1.5 text-sm text-white"
        >
          {menuAberto ? 'Fechar' : 'Menu'}
        </button>
      </header>

      <aside
        className={`w-full shrink-0 bg-tinta-profunda md:sticky md:top-0 md:flex md:h-screen md:flex-col md:transition-[width] ${
          recolhida ? 'md:w-16' : 'md:w-60'
        } ${menuAberto ? 'flex flex-col' : 'hidden'}`}
      >
        <div
          className={`hidden items-center border-b border-white/10 py-4 md:flex ${
            recolhida ? 'justify-center px-2' : 'gap-3 px-5'
          }`}
        >
          <span className="flex h-9 w-9 shrink-0 items-center justify-center border border-dourado font-display text-sm font-semibold text-dourado">
            S
          </span>
          {!recolhida && (
            <div>
              <p className="font-display text-sm font-semibold leading-tight text-white">SGA</p>
              <p className="text-[0.68rem] leading-tight text-white/55">Gestão Académica</p>
            </div>
          )}
        </div>

        <nav className={`flex flex-1 flex-col overflow-y-auto ${recolhida ? 'p-3 md:px-2' : 'p-3'}`}>
          {grupos.map(({ grupo, itens }) => (
            <div key={grupo} className="mb-1">
              {/* Recolhida, o título do grupo dá lugar a um separador. */}
              <p
                className={`px-3 pb-1.5 pt-3 text-[0.66rem] font-semibold uppercase tracking-wider text-white/40 ${
                  recolhida ? 'md:hidden' : ''
                }`}
              >
                {grupo}
              </p>
              {recolhida && <hr className="mx-2 my-2 hidden border-white/10 md:block" />}
              {itens.map((item) => (
                <NavLink
                  key={item.rota}
                  to={item.rota}
                  onClick={() => setMenuAberto(false)}
                  title={recolhida ? item.rotulo : undefined}
                  aria-label={recolhida ? item.rotulo : undefined}
                  className={({ isActive }) =>
                    `flex items-center gap-3 border-l-2 px-3 py-2 text-sm transition ${
                      recolhida ? 'md:justify-center md:px-0' : ''
                    } ${
                      isActive
                        ? 'border-dourado bg-white/10 font-semibold text-white'
                        : 'border-transparent text-white/70 hover:bg-white/5 hover:text-white'
                    }`
                  }
                >
                  <Icone nome={item.icone} />
                  <span className={recolhida ? 'md:hidden' : ''}>{item.rotulo}</span>
                </NavLink>
              ))}
            </div>
          ))}
        </nav>

        <button
          onClick={alternarBarra}
          aria-label={recolhida ? 'Expandir a barra lateral' : 'Recolher a barra lateral'}
          title={recolhida ? 'Expandir' : 'Recolher'}
          className={`hidden items-center gap-3 border-t border-white/10 py-3 text-sm text-white/60 transition hover:bg-white/5 hover:text-white md:flex ${
            recolhida ? 'justify-center' : 'px-6'
          }`}
        >
          <Icone nome={recolhida ? 'expandir' : 'recolher'} />
          {!recolhida && <span>Recolher</span>}
        </button>
      </aside>

      <div className="flex items-center gap-2.5 bg-tinta-profunda px-4 py-2.5 md:hidden">
        <span className="flex h-7 w-7 shrink-0 items-center justify-center bg-dourado text-xs font-bold text-tinta-profunda">
          {utilizador?.nome.charAt(0)}
        </span>
        <div className="min-w-0 flex-1 text-sm">
          <p className="truncate font-medium text-white">{utilizador?.nome}</p>
          <p className="truncate text-xs text-white/55">{utilizador?.perfis.join(' · ')}</p>
        </div>
        <button
          onClick={alternarTema}
          title="Alternar tema claro/escuro"
          className="shrink-0 border border-white/25 px-2 py-1 text-xs text-white/70 hover:text-white"
        >
          {ROTULO_TEMA[tema]}
        </button>
        <button onClick={() => void terminarSessao()} className="shrink-0 text-sm text-white/70 hover:text-white">
          Sair
        </button>
      </div>

      <div className="flex flex-1 flex-col overflow-hidden">
        <header className="hidden items-center justify-between border-b border-linha bg-superficie px-6 py-3 md:flex">
          <div className="flex items-center gap-2.5">
            <span className="flex h-8 w-8 shrink-0 items-center justify-center bg-dourado text-sm font-bold text-tinta-profunda">
              {utilizador?.nome.charAt(0)}
            </span>
            <div>
              <p className="text-sm font-medium text-texto">{utilizador?.nome}</p>
              <p className="text-xs text-aco">{utilizador?.perfis.join(' · ')}</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={alternarTema}
              title="Alternar tema claro/escuro"
              className="botao-fantasma px-3 py-1.5 text-sm"
            >
              {ROTULO_TEMA[tema]}
            </button>
            <button onClick={() => void terminarSessao()} className="botao-fantasma flex items-center gap-2 text-sm">
              <Icone nome="sair" className="h-4 w-4" />
              Terminar sessão
            </button>
          </div>
        </header>

        <main className="flex-1 overflow-x-hidden p-4 md:p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
