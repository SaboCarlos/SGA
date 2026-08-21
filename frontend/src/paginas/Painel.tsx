import { useEffect, useState } from 'react'
import { api } from '@/servicos/api'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'

/**
 * Painel inicial (UC30 / RF-60). A estrutura devolvida por GET /painel varia
 * consoante o perfil autenticado (ver docs/openapi.yaml); aqui apresenta-se
 * de forma genérica, chave a chave, até existirem os ecrãs dedicados por
 * perfil previstos em US-27.
 */
export function Painel() {
  const { utilizador } = useAutenticacao()
  const [dados, setDados] = useState<Record<string, unknown> | null>(null)
  const [erro, setErro] = useState<string | null>(null)

  useEffect(() => {
    api
      .get<Record<string, unknown>>('/painel')
      .then(setDados)
      .catch(() => setErro('Não foi possível carregar o painel.'))
  }, [])

  return (
    <div>
      <h1 className="mb-1 text-lg font-semibold text-slate-800">
        Olá, {utilizador?.nome?.split(' ')[0]}
      </h1>
      <p className="mb-6 text-sm text-slate-500">
        Perfil activo: {utilizador?.perfis.join(', ')}
      </p>

      {erro && (
        <p role="alert" className="bg-red-50 px-3 py-2 text-sm text-red-700">
          {erro}
        </p>
      )}

      {!dados && !erro && <p className="text-sm text-slate-500">A carregar indicadores…</p>}

      {dados && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {Object.entries(dados).map(([chave, valor]) => (
            <div key={chave} className="border border-slate-200 bg-white p-4">
              <p className="text-xs uppercase tracking-wide text-slate-400">
                {chave.replaceAll('_', ' ')}
              </p>
              <p className="mt-1 text-2xl font-semibold text-escn-azul">
                {typeof valor === 'object' ? JSON.stringify(valor) : String(valor)}
              </p>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
