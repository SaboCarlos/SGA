import { FormEvent, useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { Perfil, Utilizador } from '@/tipos/api'

const PERFIS_DISPONIVEIS: Perfil[] = ['ADMINISTRADOR', 'COORDENADOR', 'DOCENTE', 'ESTUDANTE']
const ESTADO_INICIAL = { nome: '', email: '', palavra_passe: '', perfis: [] as Perfil[] }

/** Gestão de utilizadores e perfis (UC02, UC03 / US-02, US-03) — só o Administrador acede. */
export function Utilizadores() {
  const [utilizadores, setUtilizadores] = useState<Utilizador[] | null>(null)
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(ESTADO_INICIAL)
  const [erro, setErro] = useState<string | null>(null)

  useEffect(() => {
    carregar()
  }, [])

  function carregar() {
    api.get<Utilizador[]>('/utilizadores').then(setUtilizadores).catch(() => setErro('Não foi possível carregar os utilizadores.'))
  }

  function alternarPerfil(perfil: Perfil) {
    setFormulario((f) => ({
      ...f,
      perfis: f.perfis.includes(perfil) ? f.perfis.filter((p) => p !== perfil) : [...f.perfis, perfil],
    }))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)

    try {
      await api.post('/utilizadores', formulario)
      setFormulario(ESTADO_INICIAL)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível criar o utilizador.')
    }
  }

  async function alternarActivo(utilizador: Utilizador) {
    setErro(null)
    try {
      await api.delete(`/utilizadores/${utilizador.id}`)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível desactivar o utilizador.')
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-lg font-semibold text-slate-800">Utilizadores</h1>
        <button
          onClick={() => setAMostrarFormulario((v) => !v)}
          className="rounded bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90"
        >
          {aMostrarFormulario ? 'Cancelar' : 'Novo utilizador'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="mb-6 grid grid-cols-2 gap-3 rounded-lg border border-slate-200 bg-white p-4">
          <input required placeholder="Nome completo" value={formulario.nome}
            onChange={(e) => setFormulario({ ...formulario, nome: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input required type="email" placeholder="E-mail" value={formulario.email}
            onChange={(e) => setFormulario({ ...formulario, email: e.target.value })}
            className="rounded border border-slate-300 px-3 py-2 text-sm" />
          <input required type="password" minLength={8} placeholder="Palavra-passe inicial" value={formulario.palavra_passe}
            onChange={(e) => setFormulario({ ...formulario, palavra_passe: e.target.value })}
            className="col-span-2 rounded border border-slate-300 px-3 py-2 text-sm" />
          <div className="col-span-2 flex gap-4">
            {PERFIS_DISPONIVEIS.map((p) => (
              <label key={p} className="flex items-center gap-1.5 text-sm text-slate-700">
                <input type="checkbox" checked={formulario.perfis.includes(p)} onChange={() => alternarPerfil(p)} />
                {p}
              </label>
            ))}
          </div>
          <button type="submit" className="col-span-2 mt-2 rounded bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90">
            Criar utilizador
          </button>
        </form>
      )}

      {utilizadores === null && !erro && <p className="text-sm text-slate-500">A carregar…</p>}

      {utilizadores && (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-2">Nome</th>
                <th className="px-4 py-2">E-mail</th>
                <th className="px-4 py-2">Perfis</th>
                <th className="px-4 py-2"></th>
              </tr>
            </thead>
            <tbody>
              {utilizadores.map((u) => (
                <tr key={u.id} className="border-t border-slate-100">
                  <td className="px-4 py-2">{u.nome}</td>
                  <td className="px-4 py-2">{u.email}</td>
                  <td className="px-4 py-2">{u.perfis.join(', ')}</td>
                  <td className="px-4 py-2 text-right">
                    <button onClick={() => void alternarActivo(u)} className="text-red-600 hover:underline">
                      Desactivar
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
