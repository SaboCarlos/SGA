import { FormEvent, useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { AnoLectivo as AnoLectivoTipo, PeriodoLectivo } from '@/tipos/api'

const ESTADO_INICIAL = { designacao: '', data_inicio: '', data_fim: '' }
const NOVO_PERIODO = { semestre: '1', inscricao_inicio: '', inscricao_fim: '', notas_inicio: '', notas_fim: '' }

/** Gestão do ano lectivo e dos períodos de inscrição/notas (UC12 / US-12). */
export function AnoLectivo() {
  const [anos, setAnos] = useState<AnoLectivoTipo[] | null>(null)
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(ESTADO_INICIAL)
  const [aberto, setAberto] = useState<AnoLectivoTipo | null>(null)
  const [periodos, setPeriodos] = useState<PeriodoLectivo[] | null>(null)
  const [novoPeriodo, setNovoPeriodo] = useState(NOVO_PERIODO)
  const [erro, setErro] = useState<string | null>(null)
  const [erroAno, setErroAno] = useState<string | null>(null)

  useEffect(() => {
    carregar()
  }, [])

  function carregar() {
    api.get<AnoLectivoTipo[]>('/anos-lectivos').then(setAnos).catch(() => setErro('Não foi possível carregar os anos lectivos.'))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)

    try {
      await api.post('/anos-lectivos', formulario)
      setFormulario(ESTADO_INICIAL)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível criar o ano lectivo.')
    }
  }

  async function alterarEstado(ano: AnoLectivoTipo, accao: 'activar' | 'encerrar') {
    setErro(null)

    try {
      await api.post(`/anos-lectivos/${ano.id}/${accao}`)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível alterar o estado.')
    }
  }

  function abrirPeriodos(ano: AnoLectivoTipo) {
    setAberto(ano)
    setErroAno(null)
    api.get<PeriodoLectivo[]>(`/anos-lectivos/${ano.id}/periodos`).then(setPeriodos).catch(() => setPeriodos([]))
  }

  async function criarPeriodo(evento: FormEvent) {
    evento.preventDefault()
    if (!aberto) return
    setErroAno(null)

    try {
      await api.post(`/anos-lectivos/${aberto.id}/periodos`, { ...novoPeriodo, semestre: Number(novoPeriodo.semestre) })
      setNovoPeriodo(NOVO_PERIODO)
      abrirPeriodos(aberto)
    } catch (e: unknown) {
      setErroAno(e instanceof ErroApi ? e.message : 'Não foi possível criar o período.')
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="titulo-pagina">Ano lectivo</h1>
        <button onClick={() => setAMostrarFormulario((v) => !v)} className="botao">
          {aMostrarFormulario ? 'Cancelar' : 'Novo ano lectivo'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="cartao mb-6 grid grid-cols-3 gap-3 p-4">
          <input required placeholder="Designação (ex.: 2027)" value={formulario.designacao}
            onChange={(e) => setFormulario({ ...formulario, designacao: e.target.value })}
            className="entrada" />
          <input required type="date" value={formulario.data_inicio}
            onChange={(e) => setFormulario({ ...formulario, data_inicio: e.target.value })}
            className="entrada" />
          <input required type="date" value={formulario.data_fim}
            onChange={(e) => setFormulario({ ...formulario, data_fim: e.target.value })}
            className="entrada" />
          <button type="submit" className="botao col-span-3 mt-2">
            Criar
          </button>
        </form>
      )}

      {anos === null && <p className="text-sm text-aco">A carregar…</p>}

      {anos && (
        <div className="tabela-wrap">
          <table className="tabela">
            <thead>
              <tr>
                <th>Designação</th>
                <th>Período</th>
                <th>Estado</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {anos.map((a) => (
                <tr key={a.id}>
                  <td>{a.designacao}</td>
                  <td>{a.data_inicio} — {a.data_fim}</td>
                  <td>
                    <span className={`selo ${a.estado === 'ACTIVO' ? 'selo-sucesso' : a.estado === 'ENCERRADO' ? 'selo-neutro' : 'selo-aviso'}`}>
                      {a.estado}
                    </span>
                  </td>
                  <td className="space-x-3 text-right">
                    <button onClick={() => abrirPeriodos(a)} className="text-tinta hover:underline">Períodos</button>
                    {a.estado === 'PLANEADO' && (
                      <button onClick={() => void alterarEstado(a, 'activar')} className="text-tinta hover:underline">Activar</button>
                    )}
                    {a.estado === 'ACTIVO' && (
                      <button onClick={() => void alterarEstado(a, 'encerrar')} className="text-perigo hover:underline">Encerrar</button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {aberto && (
        <div className="cartao mt-6 p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="font-display text-sm font-semibold text-texto">Períodos — {aberto.designacao}</h2>
            <button onClick={() => setAberto(null)} className="text-sm text-aco hover:underline">Fechar</button>
          </div>

          {erroAno && <Alerta>{erroAno}</Alerta>}

          <table className="tabela mb-4">
            <thead>
              <tr>
                <th>Semestre</th>
                <th>Inscrições</th>
                <th>Notas</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              {periodos?.map((p) => (
                <tr key={p.id}>
                  <td>{p.semestre}</td>
                  <td>{p.inscricao_inicio} — {p.inscricao_fim}</td>
                  <td>{p.notas_inicio} — {p.notas_fim}</td>
                  <td>{p.inscricoes_abertas ? 'Inscrições abertas' : 'Inscrições fechadas'}</td>
                </tr>
              ))}
              {periodos?.length === 0 && <tr><td colSpan={4} className="py-4 text-center text-aco">Sem períodos definidos.</td></tr>}
            </tbody>
          </table>

          <form onSubmit={criarPeriodo} className="grid grid-cols-5 gap-2">
            <select value={novoPeriodo.semestre} onChange={(e) => setNovoPeriodo({ ...novoPeriodo, semestre: e.target.value })}
              className="entrada py-1.5">
              <option value="1">Sem. 1</option>
              <option value="2">Sem. 2</option>
            </select>
            <input required type="date" title="Início das inscrições" value={novoPeriodo.inscricao_inicio}
              onChange={(e) => setNovoPeriodo({ ...novoPeriodo, inscricao_inicio: e.target.value })}
              className="entrada py-1.5" />
            <input required type="date" title="Fim das inscrições" value={novoPeriodo.inscricao_fim}
              onChange={(e) => setNovoPeriodo({ ...novoPeriodo, inscricao_fim: e.target.value })}
              className="entrada py-1.5" />
            <input required type="date" title="Início do lançamento de notas" value={novoPeriodo.notas_inicio}
              onChange={(e) => setNovoPeriodo({ ...novoPeriodo, notas_inicio: e.target.value })}
              className="entrada py-1.5" />
            <input required type="date" title="Fim do lançamento de notas" value={novoPeriodo.notas_fim}
              onChange={(e) => setNovoPeriodo({ ...novoPeriodo, notas_fim: e.target.value })}
              className="entrada py-1.5" />
            <button type="submit" className="botao col-span-5 mt-1">
              Criar período
            </button>
          </form>
        </div>
      )}
    </div>
  )
}
