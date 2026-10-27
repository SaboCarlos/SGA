import { FormEvent, useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import { Paginacao, usePaginacaoLocal } from '@/componentes/Paginacao'
import type { Perfil, Utilizador } from '@/tipos/api'

const PERFIS_DISPONIVEIS: Perfil[] = ['ADMINISTRADOR', 'COORDENADOR', 'DOCENTE', 'ESTUDANTE']
const ESTADO_INICIAL = { nome: '', email: '', palavra_passe: '', perfis: [] as Perfil[] }

/** Gestão de utilizadores e perfis (UC02, UC03 / US-02, US-03) — só o Administrador acede. */
export function Utilizadores() {
  const [utilizadores, setUtilizadores] = useState<Utilizador[] | null>(null)
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(ESTADO_INICIAL)
  const [erro, setErro] = useState<string | null>(null)

  const pgUtilizadores = usePaginacaoLocal(utilizadores)
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
        <h1 className="titulo-pagina">Utilizadores</h1>
        <button onClick={() => setAMostrarFormulario((v) => !v)} className="botao">
          {aMostrarFormulario ? 'Cancelar' : 'Novo utilizador'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="cartao mb-6 grid grid-cols-2 gap-3 p-4">
          <input required placeholder="Nome completo" value={formulario.nome}
            onChange={(e) => setFormulario({ ...formulario, nome: e.target.value })}
            className="entrada" />
          <input required type="email" placeholder="E-mail" value={formulario.email}
            onChange={(e) => setFormulario({ ...formulario, email: e.target.value })}
            className="entrada" />
          <input required type="password" minLength={8} placeholder="Palavra-passe inicial" value={formulario.palavra_passe}
            onChange={(e) => setFormulario({ ...formulario, palavra_passe: e.target.value })}
            className="entrada col-span-2" />
          <div className="col-span-2 flex gap-4">
            {PERFIS_DISPONIVEIS.map((p) => (
              <label key={p} className="flex items-center gap-1.5 text-sm text-texto">
                <input type="checkbox" checked={formulario.perfis.includes(p)} onChange={() => alternarPerfil(p)} />
                {p}
              </label>
            ))}
          </div>
          <button type="submit" className="botao col-span-2 mt-2">
            Criar utilizador
          </button>
        </form>
      )}

      {utilizadores === null && !erro && <p className="text-sm text-aco">A carregar…</p>}

      {utilizadores && (
        <div className="tabela-wrap">
          <table className="tabela">
            <thead>
              <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfis</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {pgUtilizadores.visiveis.map((u) => (
                <tr key={u.id}>
                  <td>{u.nome}</td>
                  <td>{u.email}</td>
                  <td>{u.perfis.join(', ')}</td>
                  <td className="text-right">
                    <button onClick={() => void alternarActivo(u)} className="text-perigo hover:underline">
                      Desactivar
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      <Paginacao coleccao={pgUtilizadores.coleccao} aoMudar={pgUtilizadores.setPagina} />
    </div>
  )
}
