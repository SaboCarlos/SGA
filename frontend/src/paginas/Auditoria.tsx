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
      <h1 className="titulo-pagina mb-1">Auditoria</h1>
      <p className="mb-6 text-sm text-aco">Registo imutável de acções sensíveis (RNF-32).</p>

      {erro && <Alerta>{erro}</Alerta>}

      <input
        placeholder="Filtrar por entidade (ex.: classificacao, inscricao)…"
        value={entidade}
        onChange={(e) => setEntidade(e.target.value)}
        className="entrada mb-4 w-full max-w-sm"
      />

      {registos === null && !erro && <p className="text-sm text-aco">A carregar…</p>}

      {registos && (
        <div className="tabela-wrap">
          <table className="tabela">
            <thead>
              <tr>
                <th>Quando</th>
                <th>Utilizador</th>
                <th>Acção</th>
                <th>Entidade</th>
                <th>Justificação</th>
              </tr>
            </thead>
            <tbody>
              {registos.map((r) => (
                <tr key={r.id}>
                  <td className="num">{new Date(r.criado_em).toLocaleString('pt-PT')}</td>
                  <td>{r.utilizador ?? '—'}</td>
                  <td className="num">{r.acao}</td>
                  <td>{r.entidade} #{r.entidade_id.slice(0, 8)}</td>
                  <td className="text-aco">{r.justificacao ?? '—'}</td>
                </tr>
              ))}
              {registos.length === 0 && (
                <tr><td colSpan={5} className="py-6 text-center text-aco">Sem registos.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
