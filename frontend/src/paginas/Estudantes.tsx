import { FormEvent, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import { Paginacao } from '@/componentes/Paginacao'
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
  const [coleccao, setColeccao] = useState<ColeccaoPaginada<Estudante> | null>(null)
  const [pagina, setPagina] = useState(1)
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
  }, [pesquisa, pagina])

  function carregar() {
    const parametros = new URLSearchParams({ pagina: String(pagina), por_pagina: '10' })
    if (pesquisa) parametros.set('pesquisa', pesquisa)
    api
      .get<ColeccaoPaginada<Estudante>>(`/estudantes?${parametros}`)
      .then((resposta) => {
        setEstudantes(resposta.dados)
        setColeccao(resposta)
      })
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
          <h1 className="titulo-pagina">Estudantes</h1>
          <p className="text-sm text-aco">Cadastro e pesquisa (RNF-01).</p>
        </div>
        <button onClick={() => setAMostrarFormulario((v) => !v)} className="botao">
          {aMostrarFormulario ? 'Cancelar' : 'Novo estudante'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="cartao mb-6 grid grid-cols-2 gap-3 p-4">
          <input
            required
            placeholder="Nome completo"
            value={formulario.nome_completo}
            onChange={(e) => setFormulario({ ...formulario, nome_completo: e.target.value })}
            className="entrada col-span-2"
          />
          <input
            required
            placeholder="Documento de identificação"
            value={formulario.documento_identificacao}
            onChange={(e) => setFormulario({ ...formulario, documento_identificacao: e.target.value })}
            className="entrada"
          />
          <input
            required
            type="date"
            value={formulario.data_nascimento}
            onChange={(e) => setFormulario({ ...formulario, data_nascimento: e.target.value })}
            className="entrada"
          />
          <select
            value={formulario.sexo}
            onChange={(e) => setFormulario({ ...formulario, sexo: e.target.value as 'MASCULINO' | 'FEMININO' })}
            className="entrada"
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
            className="entrada"
          />
          <select
            required
            value={formulario.curso_id}
            onChange={(e) => setFormulario({ ...formulario, curso_id: e.target.value })}
            className="entrada"
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
            className="entrada"
          />
          <button type="submit" disabled={aSubmeter} className="botao col-span-2 mt-2">
            {aSubmeter ? 'A registar…' : 'Registar estudante'}
          </button>
        </form>
      )}

      <input
        placeholder="Pesquisar por nome ou número…"
        value={pesquisa}
        onChange={(e) => {
          setPesquisa(e.target.value)
          setPagina(1)
        }}
        className="entrada mb-4 w-full max-w-sm"
      />

      {estudantes === null && <p className="text-sm text-aco">A carregar…</p>}

      {estudantes && (
        <div className="tabela-wrap">
          <table className="tabela">
            <thead>
              <tr>
                <th>Nº</th>
                <th>Nome</th>
                <th>Curso</th>
                <th>Estado</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {estudantes.map((e) => (
                <tr key={e.id}>
                  <td className="num">{e.numero_estudante}</td>
                  <td>{e.nome_completo}</td>
                  <td>{e.curso?.designacao ?? '—'}</td>
                  <td>{e.estado}</td>
                  <td className="text-right">
                    <Link to={`/estudantes/${e.id}/aproveitamento`} className="text-tinta hover:underline">
                      Aproveitamento
                    </Link>
                  </td>
                </tr>
              ))}
              {estudantes.length === 0 && (
                <tr>
                  <td colSpan={5} className="py-6 text-center text-aco">Nenhum estudante encontrado.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}
      {coleccao && <Paginacao coleccao={coleccao} aoMudar={setPagina} />}
    </div>
  )
}
