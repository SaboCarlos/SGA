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
      <h1 className="titulo-pagina mb-1">Perfis e permissões</h1>
      <p className="mb-6 text-sm text-aco">
        Catálogo de perfis e permissões, e a sua associação. Para atribuir perfis a um
        utilizador específico, ver o ecrã Utilizadores.
      </p>

      {erro && <Alerta>{erro}</Alerta>}

      <div className="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div>
          <div className="mb-3 flex items-center justify-between">
            <h2 className="font-display text-sm font-semibold text-texto">Perfis</h2>
            <button onClick={() => setAMostrarFormularioPerfil((v) => !v)} className="text-sm text-tinta hover:underline">
              {aMostrarFormularioPerfil ? 'Cancelar' : '+ Novo perfil'}
            </button>
          </div>

          {aMostrarFormularioPerfil && (
            <form onSubmit={criarPerfil} className="cartao mb-3 space-y-2 p-3">
              <input required placeholder="Nome (ex.: AUDITOR)" value={novoPerfil.nome}
                onChange={(e) => setNovoPerfil({ ...novoPerfil, nome: e.target.value.toUpperCase() })}
                className="entrada w-full py-1.5" />
              <input placeholder="Descrição" value={novoPerfil.descricao}
                onChange={(e) => setNovoPerfil({ ...novoPerfil, descricao: e.target.value })}
                className="entrada w-full py-1.5" />
              <button type="submit" className="botao w-full py-1.5">
                Criar
              </button>
            </form>
          )}

          <div className="cartao">
            {perfis === null && <p className="p-3 text-sm text-aco">A carregar…</p>}
            {perfis?.map((p) => (
              <button
                key={p.id}
                onClick={() => abrirPerfil(p)}
                className={`block w-full border-t border-linha px-3 py-2 text-left text-sm first:border-t-0 hover:bg-superficie-alt ${
                  aberto?.id === p.id ? 'bg-superficie-alt' : ''
                }`}
              >
                <span className="font-medium text-texto">{p.nome}</span>
                <span className="ml-2 text-xs text-aco-suave">
                  {p.permissoes.length} {p.permissoes.length === 1 ? 'permissão' : 'permissões'}
                </span>
              </button>
            ))}
          </div>
        </div>

        <div>
          <div className="mb-3 flex items-center justify-between">
            <h2 className="font-display text-sm font-semibold text-texto">Catálogo de permissões</h2>
            <button onClick={() => setAMostrarFormularioPermissao((v) => !v)} className="text-sm text-tinta hover:underline">
              {aMostrarFormularioPermissao ? 'Cancelar' : '+ Nova permissão'}
            </button>
          </div>

          {aMostrarFormularioPermissao && (
            <form onSubmit={criarPermissao} className="cartao mb-3 space-y-2 p-3">
              <input required placeholder="Código (ex.: classificacao.publicar)" value={novaPermissao.codigo}
                onChange={(e) => setNovaPermissao({ ...novaPermissao, codigo: e.target.value })}
                className="entrada w-full py-1.5 font-mono" />
              <input placeholder="Descrição" value={novaPermissao.descricao}
                onChange={(e) => setNovaPermissao({ ...novaPermissao, descricao: e.target.value })}
                className="entrada w-full py-1.5" />
              <button type="submit" className="botao w-full py-1.5">
                Criar
              </button>
            </form>
          )}

          <ul className="cartao text-sm">
            {permissoes.map((p) => (
              <li key={p.id} className="border-t border-linha px-3 py-2 first:border-t-0">
                <span className="font-mono text-xs text-texto">{p.codigo}</span>
                {p.descricao && <span className="ml-2 text-aco">{p.descricao}</span>}
              </li>
            ))}
            {permissoes.length === 0 && <li className="px-3 py-4 text-center text-aco">Sem permissões no catálogo.</li>}
          </ul>
        </div>
      </div>

      {aberto && (
        <div className="cartao p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="font-display text-sm font-semibold text-texto">Permissões do perfil {aberto.nome}</h2>
            <button onClick={() => setAberto(null)} className="text-sm text-aco hover:underline">Fechar</button>
          </div>

          {erroPerfil && <Alerta>{erroPerfil}</Alerta>}

          <ul className="mb-4 space-y-1 text-sm">
            {aberto.permissoes.map((p) => (
              <li key={p.id} className="flex items-center justify-between bg-superficie-alt px-3 py-1.5">
                <span className="font-mono text-xs text-texto">{p.codigo}</span>
                <button onClick={() => void retirar(p.id)} className="text-perigo hover:underline">Retirar</button>
              </li>
            ))}
            {aberto.permissoes.length === 0 && <li className="text-aco">Este perfil ainda não tem permissões atribuídas.</li>}
          </ul>

          <form onSubmit={atribuir} className="flex items-end gap-2">
            <select required value={novaPermissaoId} onChange={(e) => setNovaPermissaoId(e.target.value)}
              className="entrada flex-1">
              <option value="" disabled>Permissão a atribuir…</option>
              {permissoesDisponiveis.map((p) => <option key={p.id} value={p.id}>{p.codigo}</option>)}
            </select>
            <button type="submit" className="botao">
              Atribuir
            </button>
          </form>
        </div>
      )}
    </div>
  )
}
