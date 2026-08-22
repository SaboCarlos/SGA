import { FormEvent, useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { Curso, Disciplina, ItemPlano } from '@/tipos/api'

const ESTADO_INICIAL = { codigo: '', designacao: '', grau: 'Licenciatura', duracao_anos: '4', total_creditos: '240' }

/** Gestão de cursos e planos curriculares (UC08, UC09 / US-08, US-09). */
export function Cursos() {
  const [cursos, setCursos] = useState<Curso[] | null>(null)
  const [disciplinas, setDisciplinas] = useState<Disciplina[]>([])
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(ESTADO_INICIAL)
  const [cursoAberto, setCursoAberto] = useState<Curso | null>(null)
  const [plano, setPlano] = useState<ItemPlano[] | null>(null)
  const [novoItem, setNovoItem] = useState({ disciplina_id: '', ano_curricular: '1', semestre: '1' })
  const [erro, setErro] = useState<string | null>(null)
  const [erroPlano, setErroPlano] = useState<string | null>(null)
  const [aSubmeter, setASubmeter] = useState(false)

  useEffect(() => {
    carregar()
    api.get<Disciplina[]>('/disciplinas').then(setDisciplinas).catch(() => undefined)
  }, [])

  function carregar() {
    api.get<Curso[]>('/cursos').then(setCursos).catch(() => setErro('Não foi possível carregar os cursos.'))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)
    setASubmeter(true)

    try {
      await api.post('/cursos', {
        ...formulario,
        duracao_anos: Number(formulario.duracao_anos),
        total_creditos: Number(formulario.total_creditos),
      })
      setFormulario(ESTADO_INICIAL)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível criar o curso.')
    } finally {
      setASubmeter(false)
    }
  }

  function abrirPlano(curso: Curso) {
    setCursoAberto(curso)
    setErroPlano(null)
    api.get<ItemPlano[]>(`/cursos/${curso.id}/plano-curricular`).then(setPlano).catch(() => setPlano([]))
  }

  async function acrescentarAoPlano(evento: FormEvent) {
    evento.preventDefault()
    if (!cursoAberto) return
    setErroPlano(null)

    try {
      await api.post(`/cursos/${cursoAberto.id}/plano-curricular`, {
        disciplina_id: Number(novoItem.disciplina_id),
        ano_curricular: Number(novoItem.ano_curricular),
        semestre: Number(novoItem.semestre),
      })
      setNovoItem({ disciplina_id: '', ano_curricular: '1', semestre: '1' })
      abrirPlano(cursoAberto)
    } catch (e: unknown) {
      setErroPlano(e instanceof ErroApi ? e.message : 'Não foi possível acrescentar a disciplina.')
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="titulo-pagina">Cursos</h1>
        <button onClick={() => setAMostrarFormulario((v) => !v)} className="botao">
          {aMostrarFormulario ? 'Cancelar' : 'Novo curso'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="cartao mb-6 grid grid-cols-2 gap-3 p-4">
          <input required placeholder="Código (ex.: PSIT)" value={formulario.codigo}
            onChange={(e) => setFormulario({ ...formulario, codigo: e.target.value })}
            className="entrada" />
          <input required placeholder="Grau" value={formulario.grau}
            onChange={(e) => setFormulario({ ...formulario, grau: e.target.value })}
            className="entrada" />
          <input required placeholder="Designação" value={formulario.designacao}
            onChange={(e) => setFormulario({ ...formulario, designacao: e.target.value })}
            className="entrada col-span-2" />
          <input required type="number" placeholder="Duração (anos)" value={formulario.duracao_anos}
            onChange={(e) => setFormulario({ ...formulario, duracao_anos: e.target.value })}
            className="entrada" />
          <input required type="number" placeholder="Total de créditos" value={formulario.total_creditos}
            onChange={(e) => setFormulario({ ...formulario, total_creditos: e.target.value })}
            className="entrada" />
          <button type="submit" disabled={aSubmeter} className="botao col-span-2 mt-2">
            {aSubmeter ? 'A criar…' : 'Criar curso'}
          </button>
        </form>
      )}

      {cursos === null && <p className="text-sm text-aco">A carregar…</p>}

      {cursos && (
        <div className="tabela-wrap">
          <table className="tabela">
            <thead>
              <tr>
                <th>Código</th>
                <th>Designação</th>
                <th>Duração</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {cursos.map((c) => (
                <tr key={c.id}>
                  <td className="num">{c.codigo}</td>
                  <td>{c.designacao}</td>
                  <td>{c.duracao_anos} anos</td>
                  <td className="text-right">
                    <button onClick={() => abrirPlano(c)} className="text-tinta hover:underline">
                      Plano curricular
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {cursoAberto && (
        <div className="cartao mt-6 p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="font-display text-sm font-semibold text-texto">Plano curricular — {cursoAberto.designacao}</h2>
            <button onClick={() => setCursoAberto(null)} className="text-sm text-aco hover:underline">Fechar</button>
          </div>

          {erroPlano && <Alerta>{erroPlano}</Alerta>}

          <table className="tabela mb-4">
            <thead>
              <tr>
                <th>Disciplina</th>
                <th>Ano</th>
                <th>Semestre</th>
                <th>Tipo</th>
              </tr>
            </thead>
            <tbody>
              {plano?.map((item) => (
                <tr key={item.id}>
                  <td>{item.disciplina}</td>
                  <td>{item.ano_curricular}</td>
                  <td>{item.semestre}</td>
                  <td>{item.tipo}</td>
                </tr>
              ))}
              {plano?.length === 0 && (
                <tr><td colSpan={4} className="py-4 text-center text-aco">Sem disciplinas no plano.</td></tr>
              )}
            </tbody>
          </table>

          <form onSubmit={acrescentarAoPlano} className="flex flex-wrap items-end gap-2">
            <select required value={novoItem.disciplina_id}
              onChange={(e) => setNovoItem({ ...novoItem, disciplina_id: e.target.value })}
              className="entrada">
              <option value="" disabled>Disciplina…</option>
              {disciplinas.map((d) => <option key={d.id} value={d.id}>{d.codigo} — {d.designacao}</option>)}
            </select>
            <input required type="number" min={1} placeholder="Ano" value={novoItem.ano_curricular}
              onChange={(e) => setNovoItem({ ...novoItem, ano_curricular: e.target.value })}
              className="entrada w-20" />
            <select value={novoItem.semestre} onChange={(e) => setNovoItem({ ...novoItem, semestre: e.target.value })}
              className="entrada">
              <option value="1">Semestre 1</option>
              <option value="2">Semestre 2</option>
            </select>
            <button type="submit" className="botao">
              Acrescentar
            </button>
          </form>
        </div>
      )}
    </div>
  )
}
