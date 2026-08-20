import { Navigate, Outlet } from 'react-router-dom'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'
import type { Perfil } from '@/tipos/api'

interface Props {
  /** Se indicado, o utilizador precisa de pelo menos um destes perfis (RNF-08). */
  perfisPermitidos?: Perfil[]
}

/**
 * Protege um grupo de rotas: exige autenticação e, opcionalmente, um perfil
 * específico. A verificação aqui é apenas de usabilidade — evita mostrar um
 * ecrã que o utilizador não pode usar — e nunca substitui a autorização no
 * servidor (RNF-08, RNF-10), que continua a ser a barreira real.
 */
export function RotaProtegida({ perfisPermitidos }: Props) {
  const { utilizador, aCarregar, temPerfil } = useAutenticacao()

  if (aCarregar) {
    return (
      <div className="flex min-h-screen items-center justify-center text-sm text-slate-500">
        A carregar…
      </div>
    )
  }

  if (!utilizador) {
    return <Navigate to="/login" replace />
  }

  if (perfisPermitidos && !temPerfil(...perfisPermitidos)) {
    return <Navigate to="/painel" replace />
  }

  return <Outlet />
}
