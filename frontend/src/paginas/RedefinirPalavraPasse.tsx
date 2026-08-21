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
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
      <div className="w-full max-w-sm border border-slate-200 bg-white p-8">
        <h1 className="mb-1 text-xl font-semibold text-escn-azul">Nova palavra-passe</h1>
        <p className="mb-6 text-sm text-slate-500">{email || 'Ligação inválida'}</p>

        {!token || !email ? (
          <div>
            <p className="mb-4 text-sm text-red-700">
              Esta ligação é inválida ou está incompleta. Solicite um novo pedido de recuperação.
            </p>
            <Link to="/recuperar-palavra-passe" className="text-sm text-escn-azul hover:underline">
              Pedir nova ligação
            </Link>
          </div>
        ) : sucesso ? (
          <div>
            <p className="mb-4 text-sm text-slate-700">
              A sua palavra-passe foi redefinida. Já pode iniciar sessão.
            </p>
            <Link to="/login" className="text-sm text-escn-azul hover:underline">
              Iniciar sessão
            </Link>
          </div>
        ) : (
          <form onSubmit={submeter}>
            <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="palavra-passe">
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
              className="mb-4 w-full border border-slate-300 px-3 py-2 text-sm focus:border-escn-azul focus:outline-none"
            />

            <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="confirmacao">
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
              className="mb-2 w-full border border-slate-300 px-3 py-2 text-sm focus:border-escn-azul focus:outline-none"
            />

            {erro && (
              <p role="alert" className="mb-4 mt-2 bg-red-50 px-3 py-2 text-sm text-red-700">
                {erro}
              </p>
            )}

            <button
              type="submit"
              disabled={aEnviar}
              className="mt-4 w-full bg-escn-azul px-4 py-2 text-sm font-medium text-white transition hover:bg-opacity-90 disabled:opacity-50"
            >
              {aEnviar ? 'A guardar…' : 'Redefinir palavra-passe'}
            </button>
          </form>
        )}
      </div>
    </div>
  )
}
