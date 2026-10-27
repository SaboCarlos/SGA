import { FormEvent, useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import { Paginacao } from '@/componentes/Paginacao'
import type { ColeccaoPaginada, Disciplina } from '@/tipos/api'

const ESTADO_INICIAL = { codigo: '', designacao: '', creditos: '6', carga_horaria: '90' }

/** Cadastro de disciplinas e pré-requisitos (US-08, US-10). */
export function Disciplinas() {
  const [disciplinas, setDisciplinas] = useState<Disciplina[] | null>(null)
  const [coleccao, setColeccao] = useState<ColeccaoPaginada<Disciplina> | null>(null)
  const [pagina, setPagina] = useState(1)
  // Lista completa (sem paginação) para escolher pré-requisitos.
  const [todas, setTodas] = useState<Disciplina[]>([])
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(ESTADO_INICIAL)
  const [aberta, setAberta] = useState<Disciplina | null>(null)
  const [preRequisitos, setPreRequisitos] = useState<Disciplina[] | null>(null)
  const [novoPreRequisitoId, setNovoPreRequisitoId] = useState('')
  const [erro, setErro] = useState<string | null>(null)
  const [erroPreRequisito, setErroPreRequisito] = useState<string | null>(null)
  const [aSubmeter, setASubmeter] = useState(false)

  useEffect(() => {
    carregar()
  }, [pagina])

  function carregar() {
    api.get<Disciplina[]>('/disciplinas').then(setTodas).catch(() => undefined)
    api
      .get<ColeccaoPaginada<Disciplina>>(`/disciplinas?pagina=${pagina}&por_pagina=10`)
      .then((resposta) => {
        setDisciplinas(resposta.dados)
        setColeccao(resposta)
      }).catch(() => setErro('Não foi possível carregar as disciplinas.'))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)
    setASubmeter(true)

    try {
      await api.post('/disciplinas', {
        ...formulario,
        creditos: Number(formulario.creditos),
        carga_horaria: Number(formulario.carga_horaria),
      })
      setFormulario(ESTADO_INICIAL)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível criar a disciplina.')
    } finally {
      setASubmeter(false)
    }
  }

  function abrirPreRequisitos(disciplina: Disciplina) {
    setAberta(disciplina)
    setErroPreRequisito(null)
    api.get<Disciplina[]>(`/disciplinas/${disciplina.id}/pre-requisitos`).then(setPreRequisitos).catch(() => setPreRequisitos([]))
  }

  async function definirPreRequisito(evento: FormEvent) {
    evento.preventDefault()
    if (!aberta) return
    setErroPreRequisito(null)

    try {
      await api.post(`/disciplinas/${aberta.id}/pre-requisitos`, { pre_requisito_id: Number(novoPreRequisitoId) })
      setNovoPreRequisitoId('')
      abrirPreRequisitos(aberta)
    } catch (e: unknown) {
      // US-10: o servidor recusa ciclos directos e indirectos com 422 — a
      // mensagem devolvida já é clara o suficiente para mostrar tal-e-qual.
      setErroPreRequisito(e instanceof ErroApi ? e.message : 'Não foi possível definir o pré-requisito.')
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="titulo-pagina">Disciplinas</h1>
        <button onClick={() => setAMostrarFormulario((v) => !v)} className="botao">
          {aMostrarFormulario ? 'Cancelar' : 'Nova disciplina'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="cartao mb-6 grid grid-cols-2 gap-3 p-4">
          <input required placeholder="Código (ex.: INF401)" value={formulario.codigo}
            onChange={(e) => setFormulario({ ...formulario, codigo: e.target.value })}
            className="entrada" />
          <input required placeholder="Designação" value={formulario.designacao}
            onChange={(e) => setFormulario({ ...formulario, designacao: e.target.value })}
            className="entrada" />
          <input required type="number" placeholder="Créditos" value={formulario.creditos}
            onChange={(e) => setFormulario({ ...formulario, creditos: e.target.value })}
            className="entrada" />
          <input required type="number" placeholder="Carga horária" value={formulario.carga_horaria}
            onChange={(e) => setFormulario({ ...formulario, carga_horaria: e.target.value })}
            className="entrada" />
          <button type="submit" disabled={aSubmeter} className="botao col-span-2 mt-2">
            {aSubmeter ? 'A criar…' : 'Criar disciplina'}
          </button>
        </form>
      )}

      {disciplinas === null && <p className="text-sm text-aco">A carregar…</p>}

      {disciplinas && (
        <div className="tabela-wrap">
          <table className="tabela">
            <thead>
              <tr>
                <th>Código</th>
                <th>Designação</th>
                <th className="num">Créditos</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {disciplinas.map((d) => (
                <tr key={d.id}>
                  <td className="num">{d.codigo}</td>
                  <td>{d.designacao}</td>
                  <td className="num">{d.creditos}</td>
                  <td className="text-right">
                    <button onClick={() => abrirPreRequisitos(d)} className="text-tinta hover:underline">
                      Pré-requisitos
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      {coleccao && <Paginacao coleccao={coleccao} aoMudar={setPagina} />}

      {aberta && (
        <div className="cartao mt-6 p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="font-display text-sm font-semibold text-texto">Pré-requisitos — {aberta.designacao}</h2>
            <button onClick={() => setAberta(null)} className="text-sm text-aco hover:underline">Fechar</button>
          </div>

          {erroPreRequisito && <Alerta>{erroPreRequisito}</Alerta>}

          <ul className="mb-4 space-y-1 text-sm text-texto">
            {preRequisitos?.map((p) => <li key={p.id}>{p.codigo} — {p.designacao}</li>)}
            {preRequisitos?.length === 0 && <li className="text-aco">Sem pré-requisitos.</li>}
          </ul>

          <form onSubmit={definirPreRequisito} className="flex items-end gap-2">
            <select required value={novoPreRequisitoId} onChange={(e) => setNovoPreRequisitoId(e.target.value)}
              className="entrada">
              <option value="" disabled>Disciplina exigida…</option>
              {todas.filter((d) => d.id !== aberta.id).map((d) => (
                <option key={d.id} value={d.id}>{d.codigo} — {d.designacao}</option>
              ))}
            </select>
            <button type="submit" className="botao">
              Definir
            </button>
          </form>
        </div>
      )}
    </div>
  )
}
