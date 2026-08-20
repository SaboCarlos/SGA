import { FormEvent, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { ErroApi } from '@/servicos/api'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'

/**
 * Ecrã de autenticação (UC01 / US-01).
 *
 * A mensagem de erro é deliberadamente genérica ("Credenciais inválidas"),
 * sem indicar se o problema está no e-mail ou na palavra-passe — critério
 * de aceitação de US-01, para não facilitar a enumeração de contas.
 */
export function Login() {
  const { autenticar } = useAutenticacao()
  const navegar = useNavigate()
  const [email, setEmail] = useState('')
  const [palavraPasse, setPalavraPasse] = useState('')
  const [erro, setErro] = useState<string | null>(null)
  const [aEnviar, setAEnviar] = useState(false)

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)
    setAEnviar(true)

    try {
      await autenticar(email, palavraPasse)
      navegar('/painel', { replace: true })
    } catch (excepcao) {
      if (excepcao instanceof ErroApi && excepcao.estado === 401) {
        setErro('Credenciais inválidas.')
      } else if (excepcao instanceof ErroApi && excepcao.estado === 423) {
        setErro('Conta bloqueada temporariamente. Tente novamente dentro de 15 minutos.')
      } else {
        setErro('Não foi possível autenticar. Tente novamente.')
      }
    } finally {
      setAEnviar(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
      <form
        onSubmit={submeter}
        className="w-full max-w-sm rounded-lg border border-slate-200 bg-white p-8 shadow-sm"
      >
        <h1 className="mb-1 text-xl font-semibold text-escn-azul">
          Sistema de Gestão Académica
        </h1>
        <p className="mb-6 text-sm text-slate-500">
          Escola Superior de Ciências Náuticas
        </p>

        <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="email">
          E-mail institucional
        </label>
        <input
          id="email"
          type="email"
          required
          autoComplete="username"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          className="mb-4 w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-escn-azul focus:outline-none"
        />

        <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="palavra-passe">
          Palavra-passe
        </label>
        <input
          id="palavra-passe"
          type="password"
          required
          autoComplete="current-password"
          value={palavraPasse}
          onChange={(e) => setPalavraPasse(e.target.value)}
          className="mb-2 w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-escn-azul focus:outline-none"
        />

        <a href="/recuperar-palavra-passe" className="mb-4 inline-block text-sm text-escn-azul hover:underline">
          Esqueceu-se da palavra-passe?
        </a>

        {erro && (
          <p role="alert" className="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-700">
            {erro}
          </p>
        )}

        <button
          type="submit"
          disabled={aEnviar}
          className="w-full rounded bg-escn-azul px-4 py-2 text-sm font-medium text-white transition hover:bg-opacity-90 disabled:opacity-50"
        >
          {aEnviar ? 'A autenticar…' : 'Entrar'}
        </button>
      </form>
    </div>
  )
}
