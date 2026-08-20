import { NavLink, Outlet } from 'react-router-dom'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'

interface ItemNav {
  rota: string
  rotulo: string
  perfis?: string[]
}

const ITENS_NAV: ItemNav[] = [
  { rota: '/painel', rotulo: 'Painel' },
  { rota: '/inscricoes', rotulo: 'Inscrições', perfis: ['ESTUDANTE'] },
  { rota: '/estudantes', rotulo: 'Estudantes', perfis: ['ADMINISTRADOR', 'COORDENADOR'] },
  { rota: '/docentes', rotulo: 'Docentes', perfis: ['ADMINISTRADOR'] },
  { rota: '/cursos', rotulo: 'Cursos', perfis: ['ADMINISTRADOR'] },
  { rota: '/disciplinas', rotulo: 'Disciplinas', perfis: ['ADMINISTRADOR'] },
  { rota: '/turmas', rotulo: 'Turmas', perfis: ['ADMINISTRADOR', 'COORDENADOR', 'DOCENTE'] },
  { rota: '/ano-lectivo', rotulo: 'Ano lectivo', perfis: ['ADMINISTRADOR'] },
  { rota: '/rectificacoes', rotulo: 'Rectificações' },
  { rota: '/relatorios', rotulo: 'Relatórios', perfis: ['ADMINISTRADOR', 'COORDENADOR'] },
  { rota: '/notificacoes', rotulo: 'Notificações' },
  { rota: '/auditoria', rotulo: 'Auditoria', perfis: ['ADMINISTRADOR', 'COORDENADOR'] },
  { rota: '/utilizadores', rotulo: 'Utilizadores', perfis: ['ADMINISTRADOR'] },
  { rota: '/perfis', rotulo: 'Perfis e permissões', perfis: ['ADMINISTRADOR'] },
]

/**
 * Casca comum a todos os ecrãs autenticados (UC30 — painel inicial por
 * perfil). A navegação lateral filtra-se pelos perfis do utilizador; a
 * mesma filtragem é apenas conveniência de interface — a autorização real
 * está no servidor.
 */
export function Layout() {
  const { utilizador, terminarSessao, temPerfil } = useAutenticacao()

  const itensVisiveis = ITENS_NAV.filter((item) => !item.perfis || temPerfil(...item.perfis))

  return (
    <div className="flex min-h-screen bg-slate-50">
      <aside className="w-56 shrink-0 border-r border-slate-200 bg-white">
        <div className="border-b border-slate-200 px-4 py-4">
          <p className="text-sm font-semibold text-escn-azul">SGA</p>
          <p className="text-xs text-slate-500">Gestão Académica</p>
        </div>
        <nav className="flex flex-col gap-1 p-2">
          {itensVisiveis.map((item) => (
            <NavLink
              key={item.rota}
              to={item.rota}
              className={({ isActive }) =>
                `rounded px-3 py-2 text-sm transition ${
                  isActive
                    ? 'bg-escn-azul text-white'
                    : 'text-slate-600 hover:bg-slate-100'
                }`
              }
            >
              {item.rotulo}
            </NavLink>
          ))}
        </nav>
      </aside>

      <div className="flex flex-1 flex-col">
        <header className="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-3">
          <div>
            <p className="text-sm font-medium text-slate-800">{utilizador?.nome}</p>
            <p className="text-xs text-slate-500">{utilizador?.perfis.join(' · ')}</p>
          </div>
          <button
            onClick={() => void terminarSessao()}
            className="rounded px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-100"
          >
            Terminar sessão
          </button>
        </header>

        <main className="flex-1 p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
