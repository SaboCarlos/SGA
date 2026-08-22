import { FormEvent, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ErroApi } from '@/servicos/api'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'
import { ROTULO_TEMA, useTema } from '@/contextos/TemaContexto'

/**
 * Ecrã de autenticação (UC01 / US-01).
 *
 * A mensagem de erro é deliberadamente genérica ("Credenciais inválidas"),
 * sem indicar se o problema está no e-mail ou na palavra-passe — critério
 * de aceitação de US-01, para não facilitar a enumeração de contas.
 */
export function Login() {
  const { autenticar } = useAutenticacao()
  const { tema, alternarTema } = useTema()
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
    <div className="relative flex min-h-screen items-stretch bg-bruma">
      <button
        onClick={alternarTema}
        title="Alternar tema claro/escuro"
        className="botao-fantasma absolute right-4 top-4 z-10 px-3 py-1.5 text-xs"
      >
        {ROTULO_TEMA[tema]}
      </button>
      <div className="relative hidden flex-1 flex-col justify-between overflow-hidden bg-tinta-profunda p-12 text-white lg:flex">
        <div className="flex items-center gap-3.5">
          <span className="flex h-11 w-11 shrink-0 items-center justify-center border border-dourado font-display text-lg font-semibold text-dourado">
            SGA
          </span>
          <div className="leading-tight">
            <strong className="block text-sm tracking-wide">Sistema de Gestão Académica</strong>
            <span className="block text-xs tracking-wide text-white/60">
              ESCOLA SUPERIOR DE CIÊNCIAS NÁUTICAS
            </span>
          </div>
        </div>

        <p className="max-w-md text-balance font-display text-3xl font-medium leading-tight">
          Cada inscrição, avaliação e pauta com o rigor de um livro de bordo.
        </p>

        <div className="flex gap-8 border-t border-white/15 pt-5 text-xs text-white/60">
          <div>
            <b className="block font-display text-base font-semibold text-white">2026</b>
            ano lectivo
          </div>
          <div>
            <b className="block font-display text-base font-semibold text-white">S1</b>
            período corrente
          </div>
        </div>
      </div>

      <div className="flex flex-1 items-center justify-center p-6">
        <form onSubmit={submeter} className="w-full max-w-sm">
          <h1 className="titulo-pagina mb-1">Entrar</h1>
          <p className="mb-6 text-sm text-aco">Use as credenciais institucionais da ESCN.</p>

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

          <label className="mb-1 block text-sm font-medium text-texto" htmlFor="palavra-passe">
            Palavra-passe
          </label>
          <input
            id="palavra-passe"
            type="password"
            required
            autoComplete="current-password"
            value={palavraPasse}
            onChange={(e) => setPalavraPasse(e.target.value)}
            className="entrada mb-2 w-full"
          />

          <Link to="/recuperar-palavra-passe" className="mb-4 inline-block text-sm text-tinta hover:underline">
            Esqueceu-se da palavra-passe?
          </Link>

          {erro && (
            <p role="alert" className="alerta alerta-erro mb-4">
              {erro}
            </p>
          )}

          <button type="submit" disabled={aEnviar} className="botao w-full">
            {aEnviar ? 'A autenticar…' : 'Entrar'}
          </button>
        </form>
      </div>
    </div>
  )
}
