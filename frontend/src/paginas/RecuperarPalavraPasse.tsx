import { FormEvent, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '@/servicos/api'

/**
 * Pedido de recuperação de palavra-passe (UC04 / US-04).
 *
 * A resposta do servidor é sempre 202, exista ou não o e-mail — por isso o
 * ecrã mostra sempre a mesma confirmação, sem tentar distinguir os dois
 * casos (não haveria nada de útil, nem seguro, a distinguir).
 */
export function RecuperarPalavraPasse() {
  const [email, setEmail] = useState('')
  const [enviado, setEnviado] = useState(false)
  const [aEnviar, setAEnviar] = useState(false)

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setAEnviar(true)

    try {
      await api.post('/auth/palavra-passe/recuperar', { email })
    } finally {
      setAEnviar(false)
      setEnviado(true)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
      <div className="w-full max-w-sm border border-slate-200 bg-white p-8">
        <h1 className="mb-1 text-xl font-semibold text-escn-azul">Recuperar palavra-passe</h1>
        <p className="mb-6 text-sm text-slate-500">Sistema de Gestão Académica</p>

        {enviado ? (
          <div>
            <p className="mb-4 text-sm text-slate-700">
              Se existir uma conta com esse e-mail, foi enviada uma ligação para
              definir uma nova palavra-passe.
            </p>
            <Link to="/login" className="text-sm text-escn-azul hover:underline">
              Voltar ao início de sessão
            </Link>
          </div>
        ) : (
          <form onSubmit={submeter}>
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
              className="mb-4 w-full border border-slate-300 px-3 py-2 text-sm focus:border-escn-azul focus:outline-none"
            />

            <button
              type="submit"
              disabled={aEnviar}
              className="w-full bg-escn-azul px-4 py-2 text-sm font-medium text-white transition hover:bg-opacity-90 disabled:opacity-50"
            >
              {aEnviar ? 'A enviar…' : 'Enviar ligação'}
            </button>

            <Link to="/login" className="mt-4 block text-center text-sm text-escn-azul hover:underline">
              Voltar ao início de sessão
            </Link>
          </form>
        )}
      </div>
    </div>
  )
}
