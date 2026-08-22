import { useState } from 'react'
import { NavLink, Outlet } from 'react-router-dom'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'
import { ROTULO_TEMA, useTema } from '@/contextos/TemaContexto'

interface ItemNav {
  rota: string
  rotulo: string
  perfis?: string[]
  grupo?: string
}

const ITENS_NAV: ItemNav[] = [
  { rota: '/painel', rotulo: 'Painel' },
  { rota: '/inscricoes', rotulo: 'Inscrições', perfis: ['ESTUDANTE'] },
  { rota: '/estudantes', rotulo: 'Estudantes', perfis: ['ADMINISTRADOR', 'COORDENADOR'] },
  { rota: '/docentes', rotulo: 'Docentes', perfis: ['ADMINISTRADOR'] },
  { rota: '/cursos', rotulo: 'Cursos', perfis: ['ADMINISTRADOR'] },
  { rota: '/disciplinas', rotulo: 'Disciplinas', perfis: ['ADMINISTRADOR'] },
  { rota: '/turmas', rotulo: 'Turmas', perfis: ['ADMINISTRADOR', 'COORDENADOR', 'DOCENTE'] },
  { rota: '/ano-lectivo', rotulo: 'Ano lectivo', perfis: ['ADMINISTRADOR'], grupo: 'Avaliação' },
  { rota: '/rectificacoes', rotulo: 'Rectificações', grupo: 'Avaliação' },
  { rota: '/relatorios', rotulo: 'Relatórios', perfis: ['ADMINISTRADOR', 'COORDENADOR'], grupo: 'Gestão' },
  { rota: '/notificacoes', rotulo: 'Notificações', grupo: 'Gestão' },
  { rota: '/auditoria', rotulo: 'Auditoria', perfis: ['ADMINISTRADOR', 'COORDENADOR'], grupo: 'Gestão' },
  { rota: '/utilizadores', rotulo: 'Utilizadores', perfis: ['ADMINISTRADOR'], grupo: 'Gestão' },
  { rota: '/perfis', rotulo: 'Perfis e permissões', perfis: ['ADMINISTRADOR'], grupo: 'Gestão' },
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

  const itensVisiveis = ITENS_NAV.filter((item) => !item.perfis || temPerfil(...item.perfis))

  let grupoAnterior: string | undefined

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
        className={`w-full shrink-0 bg-tinta-profunda md:flex md:w-60 md:flex-col ${menuAberto ? 'flex flex-col' : 'hidden'}`}
      >
        <div className="hidden items-center gap-3 border-b border-white/10 px-5 py-4 md:flex">
          <span className="flex h-9 w-9 shrink-0 items-center justify-center border border-dourado font-display text-sm font-semibold text-dourado">
            S
          </span>
          <div>
            <p className="font-display text-sm font-semibold leading-tight text-white">SGA</p>
            <p className="text-[0.68rem] leading-tight text-white/55">Gestão Académica</p>
          </div>
        </div>

        <nav className="flex flex-1 flex-col gap-0.5 p-3">
          {itensVisiveis.map((item) => {
            const mostrarRotulo = item.grupo && item.grupo !== grupoAnterior
            grupoAnterior = item.grupo
            return (
              <div key={item.rota}>
                {mostrarRotulo && (
                  <p className="px-3 pb-1.5 pt-3.5 text-[0.66rem] font-semibold uppercase tracking-wider text-white/40">
                    {item.grupo}
                  </p>
                )}
                <NavLink
                  to={item.rota}
                  onClick={() => setMenuAberto(false)}
                  className={({ isActive }) =>
                    `block border-l-2 px-3 py-2 text-sm transition ${
                      isActive
                        ? 'border-dourado bg-white/10 font-semibold text-white'
                        : 'border-transparent text-white/70 hover:bg-white/5 hover:text-white'
                    }`
                  }
                >
                  {item.rotulo}
                </NavLink>
              </div>
            )
          })}
        </nav>
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
            <button onClick={() => void terminarSessao()} className="botao-fantasma text-sm">
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
