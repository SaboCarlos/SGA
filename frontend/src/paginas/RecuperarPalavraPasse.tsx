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
    <div className="flex min-h-screen items-center justify-center bg-bruma px-4">
      <div className="w-full max-w-sm cartao p-8">
        <h1 className="titulo-pagina mb-1">Recuperar palavra-passe</h1>
        <p className="mb-6 text-sm text-aco">Sistema de Gestão Académica</p>

        {enviado ? (
          <div>
            <p className="mb-4 text-sm text-texto">
              Se existir uma conta com esse e-mail, foi enviada uma ligação para
              definir uma nova palavra-passe.
            </p>
            <Link to="/login" className="text-sm text-tinta hover:underline">
              Voltar ao início de sessão
            </Link>
          </div>
        ) : (
          <form onSubmit={submeter}>
            <label className="mb-1 block text-sm font-medium text-texto" htmlFor="email">
              E-mail institucional
            </label>
            <input
              id="email"
              type="email"
              required
              autoComplete="username"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="entrada mb-4 w-full"
            />

            <button type="submit" disabled={aEnviar} className="botao w-full">
              {aEnviar ? 'A enviar…' : 'Enviar ligação'}
            </button>

            <Link to="/login" className="mt-4 block text-center text-sm text-tinta hover:underline">
              Voltar ao início de sessão
            </Link>
          </form>
        )}
      </div>
    </div>
  )
}
