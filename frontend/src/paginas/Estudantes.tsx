import { FormEvent, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { ColeccaoPaginada, Curso, Estudante } from '@/tipos/api'

const ESTADO_INICIAL = {
  nome_completo: '',
  documento_identificacao: '',
  data_nascimento: '',
  sexo: 'FEMININO' as 'MASCULINO' | 'FEMININO',
  email: '',
  curso_id: '',
  ano_ingresso: new Date().getFullYear().toString(),
}

/** Cadastro de estudantes (UC05, UC06 / US-05, US-06). */
export function Estudantes() {
  const [estudantes, setEstudantes] = useState<Estudante[] | null>(null)
  const [cursos, setCursos] = useState<Curso[]>([])
  const [pesquisa, setPesquisa] = useState('')
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(ESTADO_INICIAL)
  const [erro, setErro] = useState<string | null>(null)
  const [aSubmeter, setASubmeter] = useState(false)

  useEffect(() => {
    api.get<Curso[]>('/cursos').then(setCursos).catch(() => undefined)
  }, [])

  useEffect(() => {
    const temporizador = setTimeout(() => carregar(), 300)
    return () => clearTimeout(temporizador)
  }, [pesquisa])

  function carregar() {
    const parametros = pesquisa ? `?pesquisa=${encodeURIComponent(pesquisa)}` : ''
    api
      .get<ColeccaoPaginada<Estudante>>(`/estudantes${parametros}`)
      .then((resposta) => setEstudantes(resposta.dados))
      .catch(() => setErro('Não foi possível carregar os estudantes.'))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)
    setASubmeter(true)

    try {
      await api.post('/estudantes', {
        ...formulario,
        curso_id: Number(formulario.curso_id),
        ano_ingresso: Number(formulario.ano_ingresso),
      })
      setFormulario(ESTADO_INICIAL)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível registar o estudante.')
    } finally {
      setASubmeter(false)
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-lg font-semibold text-slate-800">Estudantes</h1>
          <p className="text-sm text-slate-500">Cadastro e pesquisa (RNF-01).</p>
        </div>
        <button
          onClick={() => setAMostrarFormulario((v) => !v)}
          className="bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90"
        >
          {aMostrarFormulario ? 'Cancelar' : 'Novo estudante'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="mb-6 grid grid-cols-2 gap-3 border border-slate-200 bg-white p-4">
          <input
            required
            placeholder="Nome completo"
            value={formulario.nome_completo}
            onChange={(e) => setFormulario({ ...formulario, nome_completo: e.target.value })}
            className="col-span-2 border border-slate-300 px-3 py-2 text-sm"
          />
          <input
            required
            placeholder="Documento de identificação"
            value={formulario.documento_identificacao}
            onChange={(e) => setFormulario({ ...formulario, documento_identificacao: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm"
          />
          <input
            required
            type="date"
            value={formulario.data_nascimento}
            onChange={(e) => setFormulario({ ...formulario, data_nascimento: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm"
          />
          <select
            value={formulario.sexo}
            onChange={(e) => setFormulario({ ...formulario, sexo: e.target.value as 'MASCULINO' | 'FEMININO' })}
            className="border border-slate-300 px-3 py-2 text-sm"
          >
            <option value="FEMININO">Feminino</option>
            <option value="MASCULINO">Masculino</option>
          </select>
          <input
            required
            type="email"
            placeholder="E-mail institucional"
            value={formulario.email}
            onChange={(e) => setFormulario({ ...formulario, email: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm"
          />
          <select
            required
            value={formulario.curso_id}
            onChange={(e) => setFormulario({ ...formulario, curso_id: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm"
          >
            <option value="" disabled>Curso…</option>
            {cursos.map((c) => (
              <option key={c.id} value={c.id}>{c.designacao}</option>
            ))}
          </select>
          <input
            required
            type="number"
            placeholder="Ano de ingresso"
            value={formulario.ano_ingresso}
            onChange={(e) => setFormulario({ ...formulario, ano_ingresso: e.target.value })}
            className="border border-slate-300 px-3 py-2 text-sm"
          />
          <button
            type="submit"
            disabled={aSubmeter}
            className="col-span-2 mt-2 bg-escn-azul px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90 disabled:opacity-50"
          >
            {aSubmeter ? 'A registar…' : 'Registar estudante'}
          </button>
        </form>
      )}

      <input
        placeholder="Pesquisar por nome ou número…"
        value={pesquisa}
        onChange={(e) => setPesquisa(e.target.value)}
        className="mb-4 w-full max-w-sm border border-slate-300 px-3 py-2 text-sm"
      />

      {estudantes === null && <p className="text-sm text-slate-500">A carregar…</p>}

      {estudantes && (
        <div className="overflow-x-auto border border-slate-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-2">Nº</th>
                <th className="px-4 py-2">Nome</th>
                <th className="px-4 py-2">Curso</th>
                <th className="px-4 py-2">Estado</th>
                <th className="px-4 py-2"></th>
              </tr>
            </thead>
            <tbody>
              {estudantes.map((e) => (
                <tr key={e.id} className="border-t border-slate-100">
                  <td className="px-4 py-2 font-mono text-xs">{e.numero_estudante}</td>
                  <td className="px-4 py-2">{e.nome_completo}</td>
                  <td className="px-4 py-2">{e.curso?.designacao ?? '—'}</td>
                  <td className="px-4 py-2">{e.estado}</td>
                  <td className="px-4 py-2 text-right">
                    <Link to={`/estudantes/${e.id}/aproveitamento`} className="text-escn-azul hover:underline">
                      Aproveitamento
                    </Link>
                  </td>
                </tr>
              ))}
              {estudantes.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-4 py-6 text-center text-slate-500">Nenhum estudante encontrado.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
