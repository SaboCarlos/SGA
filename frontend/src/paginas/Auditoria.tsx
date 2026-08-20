import { useEffect, useState } from 'react'
import { api } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { RegistoAuditoria } from '@/tipos/api'

/** Consulta do registo de auditoria (UC31) — apenas leitura (RNF-32). */
export function Auditoria() {
  const [registos, setRegistos] = useState<RegistoAuditoria[] | null>(null)
  const [entidade, setEntidade] = useState('')
  const [erro, setErro] = useState<string | null>(null)

  useEffect(() => {
    const temporizador = setTimeout(() => carregar(), 300)
    return () => clearTimeout(temporizador)
  }, [entidade])

  function carregar() {
    const parametros = entidade ? `?entidade=${encodeURIComponent(entidade)}` : ''
    api.get<RegistoAuditoria[]>(`/auditoria${parametros}`).then(setRegistos).catch(() => setErro('Não foi possível carregar a auditoria.'))
  }

  return (
    <div>
      <h1 className="mb-1 text-lg font-semibold text-slate-800">Auditoria</h1>
      <p className="mb-6 text-sm text-slate-500">Registo imutável de acções sensíveis (RNF-32).</p>

      {erro && <Alerta>{erro}</Alerta>}

      <input
        placeholder="Filtrar por entidade (ex.: classificacao, inscricao)…"
        value={entidade}
        onChange={(e) => setEntidade(e.target.value)}
        className="mb-4 w-full max-w-sm rounded border border-slate-300 px-3 py-2 text-sm"
      />

      {registos === null && !erro && <p className="text-sm text-slate-500">A carregar…</p>}

      {registos && (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-2">Quando</th>
                <th className="px-4 py-2">Utilizador</th>
                <th className="px-4 py-2">Acção</th>
                <th className="px-4 py-2">Entidade</th>
                <th className="px-4 py-2">Justificação</th>
              </tr>
            </thead>
            <tbody>
              {registos.map((r) => (
                <tr key={r.id} className="border-t border-slate-100">
                  <td className="px-4 py-2 text-xs text-slate-500">{new Date(r.criado_em).toLocaleString('pt-PT')}</td>
                  <td className="px-4 py-2">{r.utilizador ?? '—'}</td>
                  <td className="px-4 py-2 font-mono text-xs">{r.acao}</td>
                  <td className="px-4 py-2">{r.entidade} #{r.entidade_id.slice(0, 8)}</td>
                  <td className="px-4 py-2 text-slate-600">{r.justificacao ?? '—'}</td>
                </tr>
              ))}
              {registos.length === 0 && (
                <tr><td colSpan={5} className="px-4 py-6 text-center text-slate-500">Sem registos.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
