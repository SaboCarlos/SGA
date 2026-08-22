import { createContext, ReactNode, useContext, useEffect, useState } from 'react'

export type Tema = 'sistema' | 'claro' | 'escuro'

export const ROTULO_TEMA: Record<Tema, string> = { sistema: 'Sistema', claro: 'Claro', escuro: 'Escuro' }
const PROXIMO: Record<Tema, Tema> = { sistema: 'claro', claro: 'escuro', escuro: 'sistema' }
const CHAVE_ARMAZENAMENTO = 'sga_tema'

interface EstadoTema {
  tema: Tema
  alternarTema: () => void
}

const ContextoTema = createContext<EstadoTema | undefined>(undefined)

/**
 * Claro/escuro/sistema. "Sistema" (omissão) segue prefers-color-scheme do
 * SO — ver src/index.css. Uma escolha explícita marca <html data-theme>,
 * que os tokens em index.css tratam com prioridade sobre a preferência do
 * SO, e fica guardada para a próxima visita.
 */
export function ProvedorTema({ children }: { children: ReactNode }) {
  const [tema, setTema] = useState<Tema>(
    () => (localStorage.getItem(CHAVE_ARMAZENAMENTO) as Tema | null) ?? 'sistema'
  )

  useEffect(() => {
    if (tema === 'sistema') {
      delete document.documentElement.dataset.theme
    } else {
      document.documentElement.dataset.theme = tema === 'claro' ? 'light' : 'dark'
    }
    localStorage.setItem(CHAVE_ARMAZENAMENTO, tema)
  }, [tema])

  function alternarTema() {
    setTema((actual) => PROXIMO[actual])
  }

  return <ContextoTema.Provider value={{ tema, alternarTema }}>{children}</ContextoTema.Provider>
}

export function useTema(): EstadoTema {
  const contexto = useContext(ContextoTema)
  if (!contexto) {
    throw new Error('useTema tem de ser usado dentro de <ProvedorTema>.')
  }
  return contexto
}
