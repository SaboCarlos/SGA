import { createContext, ReactNode, useContext, useEffect, useState } from 'react'
import { api } from '@/servicos/api'
import type { Utilizador } from '@/tipos/api'

interface EstadoAutenticacao {
  utilizador: Utilizador | null
  aCarregar: boolean
  autenticar: (email: string, palavraPasse: string) => Promise<void>
  terminarSessao: () => Promise<void>
  temPerfil: (...perfis: string[]) => boolean
}

const ContextoAutenticacao = createContext<EstadoAutenticacao | undefined>(undefined)

/**
 * Fonte única da sessão do utilizador no frontend (UC01/UC02, RF-01, RF-02).
 *
 * O token fica em localStorage (ver src/servicos/api.ts); os dados do
 * utilizador são recarregados de /auth/me no arranque, para que um token
 * ainda válido restaure a sessão ao recarregar a página, e um token
 * inválido ou expirado leve de volta ao ecrã de autenticação.
 */
export function ProvedorAutenticacao({ children }: { children: ReactNode }) {
  const [utilizador, setUtilizador] = useState<Utilizador | null>(null)
  const [aCarregar, setACarregar] = useState(true)

  useEffect(() => {
    const token = localStorage.getItem('sga_token')

    if (!token) {
      setACarregar(false)
      return
    }

    api
      .get<Utilizador>('/auth/me')
      .then(setUtilizador)
      .catch(() => localStorage.removeItem('sga_token'))
      .finally(() => setACarregar(false))
  }, [])

  async function autenticar(email: string, palavraPasse: string) {
    const resposta = await api.post<{ token: string; utilizador: Utilizador }>('/auth/login', {
      email,
      palavra_passe: palavraPasse,
    })
    localStorage.setItem('sga_token', resposta.token)
    setUtilizador(resposta.utilizador)
  }

  async function terminarSessao() {
    try {
      await api.post('/auth/logout')
    } finally {
      localStorage.removeItem('sga_token')
      setUtilizador(null)
    }
  }

  function temPerfil(...perfis: string[]) {
    if (!utilizador) return false
    return perfis.some((p) => utilizador.perfis.includes(p as never))
  }

  return (
    <ContextoAutenticacao.Provider
      value={{ utilizador, aCarregar, autenticar, terminarSessao, temPerfil }}
    >
      {children}
    </ContextoAutenticacao.Provider>
  )
}

export function useAutenticacao(): EstadoAutenticacao {
  const contexto = useContext(ContextoAutenticacao)
  if (!contexto) {
    throw new Error('useAutenticacao tem de ser usado dentro de <ProvedorAutenticacao>.')
  }
  return contexto
}
