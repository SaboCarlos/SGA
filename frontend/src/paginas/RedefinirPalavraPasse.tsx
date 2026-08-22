import { FormEvent, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { api, ErroApi } from '@/servicos/api'

/**
 * Consumo da ligação de recuperação (UC04 / US-04). O token e o e-mail vêm
 * na query string, no formato gerado por
 * backend/app/Notifications/RedefinirPalavraPasse.php.
 */
export function RedefinirPalavraPasse() {
  const [parametros] = useSearchParams()
  const token = parametros.get('token') ?? ''
  const email = parametros.get('email') ?? ''

  const [palavraPasse, setPalavraPasse] = useState('')
  const [confirmacao, setConfirmacao] = useState('')
  const [erro, setErro] = useState<string | null>(null)
  const [sucesso, setSucesso] = useState(false)
  const [aEnviar, setAEnviar] = useState(false)

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)
    setAEnviar(true)

    try {
      await api.post('/auth/palavra-passe/redefinir', {
        token,
        email,
        palavra_passe: palavraPasse,
        palavra_passe_confirmation: confirmacao,
      })
      setSucesso(true)
    } catch (excepcao) {
      setErro(excepcao instanceof ErroApi ? excepcao.message : 'Não foi possível redefinir a palavra-passe.')
    } finally {
      setAEnviar(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-bruma px-4">
      <div className="w-full max-w-sm cartao p-8">
        <h1 className="titulo-pagina mb-1">Nova palavra-passe</h1>
        <p className="mb-6 text-sm text-aco">{email || 'Ligação inválida'}</p>

        {!token || !email ? (
          <div>
            <p role="alert" className="alerta alerta-erro mb-4">
              Esta ligação é inválida ou está incompleta. Solicite um novo pedido de recuperação.
            </p>
            <Link to="/recuperar-palavra-passe" className="text-sm text-tinta hover:underline">
              Pedir nova ligação
            </Link>
          </div>
        ) : sucesso ? (
          <div>
            <p className="mb-4 text-sm text-texto">
              A sua palavra-passe foi redefinida. Já pode iniciar sessão.
            </p>
            <Link to="/login" className="text-sm text-tinta hover:underline">
              Iniciar sessão
            </Link>
          </div>
        ) : (
          <form onSubmit={submeter}>
            <label className="mb-1 block text-sm font-medium text-texto" htmlFor="palavra-passe">
              Nova palavra-passe
            </label>
            <input
              id="palavra-passe"
              type="password"
              required
              minLength={8}
              autoComplete="new-password"
              value={palavraPasse}
              onChange={(e) => setPalavraPasse(e.target.value)}
              className="entrada mb-4 w-full"
            />

            <label className="mb-1 block text-sm font-medium text-texto" htmlFor="confirmacao">
              Confirmar palavra-passe
            </label>
            <input
              id="confirmacao"
              type="password"
              required
              minLength={8}
              autoComplete="new-password"
              value={confirmacao}
              onChange={(e) => setConfirmacao(e.target.value)}
              className="entrada mb-2 w-full"
            />

            {erro && (
              <p role="alert" className="alerta alerta-erro mb-4 mt-2">
                {erro}
              </p>
            )}

            <button type="submit" disabled={aEnviar} className="botao mt-4 w-full">
              {aEnviar ? 'A guardar…' : 'Redefinir palavra-passe'}
            </button>
          </form>
        )}
      </div>
    </div>
  )
}
