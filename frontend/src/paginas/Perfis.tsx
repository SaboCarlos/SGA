import { FormEvent, useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { PerfilDetalhe, PermissaoCatalogo } from '@/tipos/api'

/** Gestão de perfis e permissões (US-03) — catálogo, distinto de atribuir perfis a um utilizador. */
export function Perfis() {
  const [perfis, setPerfis] = useState<PerfilDetalhe[] | null>(null)
  const [permissoes, setPermissoes] = useState<PermissaoCatalogo[]>([])
  const [aberto, setAberto] = useState<PerfilDetalhe | null>(null)
  const [novaPermissaoId, setNovaPermissaoId] = useState('')
  const [novoPerfil, setNovoPerfil] = useState({ nome: '', descricao: '' })
  const [novaPermissao, setNovaPermissao] = useState({ codigo: '', descricao: '' })
  const [aMostrarFormularioPerfil, setAMostrarFormularioPerfil] = useState(false)
  const [aMostrarFormularioPermissao, setAMostrarFormularioPermissao] = useState(false)
  const [erro, setErro] = useState<string | null>(null)
  const [erroPerfil, setErroPerfil] = useState<string | null>(null)

  useEffect(() => {
    carregarPerfis()
    carregarPermissoes()
  }, [])

  function carregarPerfis() {
    api.get<PerfilDetalhe[]>('/perfis').then(setPerfis).catch(() => setErro('Não foi possível carregar os perfis.'))
  }

  function carregarPermissoes() {
    api.get<PermissaoCatalogo[]>('/permissoes').then(setPermissoes).catch(() => undefined)
  }

  async function criarPerfil(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)

    try {
      await api.post('/perfis', novoPerfil)
      setNovoPerfil({ nome: '', descricao: '' })
      setAMostrarFormularioPerfil(false)
      carregarPerfis()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível criar o perfil.')
    }
  }

  async function criarPermissao(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)

    try {
      await api.post('/permissoes', novaPermissao)
      setNovaPermissao({ codigo: '', descricao: '' })
      setAMostrarFormularioPermissao(false)
      carregarPermissoes()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível criar a permissão.')
    }
  }

  function abrirPerfil(perfil: PerfilDetalhe) {
    setAberto(perfil)
    setErroPerfil(null)
  }

  async function atribuir(evento: FormEvent) {
    evento.preventDefault()
    if (!aberto || !novaPermissaoId) return
    setErroPerfil(null)

    try {
      const actualizado = await api.post<PerfilDetalhe>(`/perfis/${aberto.id}/permissoes`, {
        permissao_id: Number(novaPermissaoId),
      })
      setAberto(actualizado)
      setNovaPermissaoId('')
      carregarPerfis()
    } catch (e: unknown) {
      setErroPerfil(e instanceof ErroApi ? e.message : 'Não foi possível atribuir a permissão.')
    }
  }

  async function retirar(permissaoId: number) {
    if (!aberto) return
    setErroPerfil(null)

    try {
      await api.delete(`/perfis/${aberto.id}/permissoes/${permissaoId}`)
      const actualizado = { ...aberto, permissoes: aberto.permissoes.filter((p) => p.id !== permissaoId) }
      setAberto(actualizado)
      carregarPerfis()
    } catch (e: unknown) {
      setErroPerfil(e instanceof ErroApi ? e.message : 'Não foi possível retirar a permissão.')
    }
  }

  const permissoesDisponiveis = permissoes.filter((p) => !aberto?.permissoes.some((ap) => ap.id === p.id))

  return (
    <div>
      <h1 className="mb-1 text-lg font-semibold text-slate-800">Perfis e permissões</h1>
      <p className="mb-6 text-sm text-slate-500">
        Catálogo de perfis e permissões, e a sua associação. Para atribuir perfis a um
        utilizador específico, ver o ecrã Utilizadores.
      </p>

      {erro && <Alerta>{erro}</Alerta>}

      <div className="mb-8 grid grid-cols-2 gap-6">
        <div>
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-slate-800">Perfis</h2>
            <button onClick={() => setAMostrarFormularioPerfil((v) => !v)} className="text-sm text-escn-azul hover:underline">
              {aMostrarFormularioPerfil ? 'Cancelar' : '+ Novo perfil'}
            </button>
          </div>

          {aMostrarFormularioPerfil && (
            <form onSubmit={criarPerfil} className="mb-3 space-y-2 rounded-lg border border-slate-200 bg-white p-3">
              <input required placeholder="Nome (ex.: AUDITOR)" value={novoPerfil.nome}
                onChange={(e) => setNovoPerfil({ ...novoPerfil, nome: e.target.value.toUpperCase() })}
                className="w-full rounded border border-slate-300 px-3 py-1.5 text-sm" />
              <input placeholder="Descrição" value={novoPerfil.descricao}
                onChange={(e) => setNovoPerfil({ ...novoPerfil, descricao: e.target.value })}
                className="w-full rounded border border-slate-300 px-3 py-1.5 text-sm" />
              <button type="submit" className="w-full rounded bg-escn-azul px-3 py-1.5 text-sm text-white hover:bg-opacity-90">
                Criar
              </button>
            </form>
          )}

          <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
            {perfis === null && <p className="p-3 text-sm text-slate-500">A carregar…</p>}
            {perfis?.map((p) => (
              <button
                key={p.id}
                onClick={() => abrirPerfil(p)}
                className={`block w-full border-t border-slate-100 px-3 py-2 text-left text-sm first:border-t-0 hover:bg-slate-50 ${
                  aberto?.id === p.id ? 'bg-blue-50' : ''
                }`}
              >
                <span className="font-medium text-slate-800">{p.nome}</span>
                <span className="ml-2 text-xs text-slate-400">{p.permissoes.length} permissões</span>
              </button>
            ))}
          </div>
        </div>

        <div>
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-slate-800">Catálogo de permissões</h2>
            <button onClick={() => setAMostrarFormularioPermissao((v) => !v)} className="text-sm text-escn-azul hover:underline">
              {aMostrarFormularioPermissao ? 'Cancelar' : '+ Nova permissão'}
            </button>
          </div>

          {aMostrarFormularioPermissao && (
            <form onSubmit={criarPermissao} className="mb-3 space-y-2 rounded-lg border border-slate-200 bg-white p-3">
              <input required placeholder="Código (ex.: classificacao.publicar)" value={novaPermissao.codigo}
                onChange={(e) => setNovaPermissao({ ...novaPermissao, codigo: e.target.value })}
                className="w-full rounded border border-slate-300 px-3 py-1.5 text-sm font-mono" />
              <input placeholder="Descrição" value={novaPermissao.descricao}
                onChange={(e) => setNovaPermissao({ ...novaPermissao, descricao: e.target.value })}
                className="w-full rounded border border-slate-300 px-3 py-1.5 text-sm" />
              <button type="submit" className="w-full rounded bg-escn-azul px-3 py-1.5 text-sm text-white hover:bg-opacity-90">
                Criar
              </button>
            </form>
          )}

          <ul className="overflow-hidden rounded-lg border border-slate-200 bg-white text-sm">
            {permissoes.map((p) => (
              <li key={p.id} className="border-t border-slate-100 px-3 py-2 first:border-t-0">
                <span className="font-mono text-xs text-slate-700">{p.codigo}</span>
                {p.descricao && <span className="ml-2 text-slate-500">{p.descricao}</span>}
              </li>
            ))}
            {permissoes.length === 0 && <li className="px-3 py-4 text-center text-slate-500">Sem permissões no catálogo.</li>}
          </ul>
        </div>
      </div>

      {aberto && (
        <div className="rounded-lg border border-slate-200 bg-white p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-slate-800">Permissões do perfil {aberto.nome}</h2>
            <button onClick={() => setAberto(null)} className="text-sm text-slate-500 hover:underline">Fechar</button>
          </div>

          {erroPerfil && <Alerta>{erroPerfil}</Alerta>}

          <ul className="mb-4 space-y-1 text-sm">
            {aberto.permissoes.map((p) => (
              <li key={p.id} className="flex items-center justify-between rounded bg-slate-50 px-3 py-1.5">
                <span className="font-mono text-xs">{p.codigo}</span>
                <button onClick={() => void retirar(p.id)} className="text-red-600 hover:underline">Retirar</button>
              </li>
            ))}
            {aberto.permissoes.length === 0 && <li className="text-slate-500">Este perfil ainda não tem permissões atribuídas.</li>}
          </ul>

          <form onSubmit={atribuir} className="flex items-end gap-2">
            <select required value={novaPermissaoId} onChange={(e) => setNovaPermissaoId(e.target.value)}
              className="flex-1 rounded border border-slate-300 px-3 py-2 text-sm">
              <option value="" disabled>Permissão a atribuir…</option>
              {permissoesDisponiveis.map((p) => <option key={p.id} value={p.id}>{p.codigo}</option>)}
            </select>
            <button type="submit" className="rounded bg-escn-azul px-4 py-2 text-sm text-white hover:bg-opacity-90">
              Atribuir
            </button>
          </form>
        </div>
      )}
    </div>
  )
}
