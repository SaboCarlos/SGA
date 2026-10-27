import { FormEvent, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, ErroApi } from '@/servicos/api'
import { useAutenticacao } from '@/contextos/AutenticacaoContexto'
import { Alerta } from '@/componentes/Alerta'
import { Paginacao } from '@/componentes/Paginacao'
import type { ColeccaoPaginada, Disciplina, Docente, PeriodoLectivo, Turma } from '@/tipos/api'

const ESTADO_INICIAL = { disciplina_id: '', periodo_lectivo_id: '', docente_id: '', designacao: 'A', capacidade_maxima: '30' }

/** Ano lectivo e turmas (UC12, UC13 / US-11). */
export function Turmas() {
  const { temPerfil } = useAutenticacao()
  const [turmas, setTurmas] = useState<Turma[] | null>(null)
  const [coleccao, setColeccao] = useState<ColeccaoPaginada<Turma> | null>(null)
  const [pagina, setPagina] = useState(1)
  const [disciplinas, setDisciplinas] = useState<Disciplina[]>([])
  const [docentes, setDocentes] = useState<Docente[]>([])
  const [periodos, setPeriodos] = useState<PeriodoLectivo[]>([])
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(ESTADO_INICIAL)
  const [erro, setErro] = useState<string | null>(null)
  const [aSubmeter, setASubmeter] = useState(false)

  const podeGerir = temPerfil('ADMINISTRADOR')
  // O servidor só devolve ao docente as turmas que lecciona.
  const soAsMinhas = !temPerfil('ADMINISTRADOR', 'COORDENADOR')

  useEffect(() => {
    carregar()
  }, [pagina])

  useEffect(() => {
    if (podeGerir) {
      api.get<Disciplina[]>('/disciplinas').then(setDisciplinas).catch(() => undefined)
      api.get<Docente[]>('/docentes').then(setDocentes).catch(() => undefined)
      api
        .get<{ estado: string; id: number }[]>('/anos-lectivos')
        .then((anos) => {
          const activo = anos.find((a) => a.estado === 'ACTIVO')
          if (activo) return api.get<PeriodoLectivo[]>(`/anos-lectivos/${activo.id}/periodos`).then(setPeriodos)
        })
        .catch(() => undefined)
    }
  }, [podeGerir])

  function carregar() {
    api
      .get<ColeccaoPaginada<Turma>>(`/turmas?pagina=${pagina}&por_pagina=10`)
      .then((resposta) => {
        setTurmas(resposta.dados)
        setColeccao(resposta)
      }).catch(() => setErro('Não foi possível carregar as turmas.'))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)
    setASubmeter(true)

    try {
      await api.post('/turmas', {
        ...formulario,
        disciplina_id: Number(formulario.disciplina_id),
        periodo_lectivo_id: Number(formulario.periodo_lectivo_id),
        capacidade_maxima: Number(formulario.capacidade_maxima),
      })
      setFormulario(ESTADO_INICIAL)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível criar a turma.')
    } finally {
      setASubmeter(false)
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="titulo-pagina">{soAsMinhas ? 'As minhas disciplinas' : 'Turmas'}</h1>
        {podeGerir && (
          <button onClick={() => setAMostrarFormulario((v) => !v)} className="botao">
            {aMostrarFormulario ? 'Cancelar' : 'Nova turma'}
          </button>
        )}
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="cartao mb-6 grid grid-cols-2 gap-3 p-4">
          <select required value={formulario.disciplina_id}
            onChange={(e) => setFormulario({ ...formulario, disciplina_id: e.target.value })}
            className="entrada">
            <option value="" disabled>Disciplina…</option>
            {disciplinas.map((d) => <option key={d.id} value={d.id}>{d.codigo} — {d.designacao}</option>)}
          </select>
          <select required value={formulario.periodo_lectivo_id}
            onChange={(e) => setFormulario({ ...formulario, periodo_lectivo_id: e.target.value })}
            className="entrada">
            <option value="" disabled>Período lectivo…</option>
            {periodos.map((p) => <option key={p.id} value={p.id}>Semestre {p.semestre}</option>)}
          </select>
          <select required value={formulario.docente_id}
            onChange={(e) => setFormulario({ ...formulario, docente_id: e.target.value })}
            className="entrada">
            <option value="" disabled>Docente…</option>
            {docentes.map((d) => <option key={d.id} value={d.id}>{d.nome_completo}</option>)}
          </select>
          <input required placeholder="Designação (ex.: A)" value={formulario.designacao}
            onChange={(e) => setFormulario({ ...formulario, designacao: e.target.value })}
            className="entrada" />
          <input required type="number" placeholder="Capacidade máxima" value={formulario.capacidade_maxima}
            onChange={(e) => setFormulario({ ...formulario, capacidade_maxima: e.target.value })}
            className="entrada" />
          <button type="submit" disabled={aSubmeter} className="botao col-span-2 mt-2">
            {aSubmeter ? 'A criar…' : 'Criar turma'}
          </button>
        </form>
      )}

      {turmas === null && <p className="text-sm text-aco">A carregar…</p>}

      {turmas && turmas.length === 0 && (
        <p className="text-sm text-aco">
          {soAsMinhas ? 'Ainda não tem disciplinas atribuídas neste período.' : 'Ainda não há turmas criadas.'}
        </p>
      )}

      {turmas && turmas.length > 0 && (
        <div className="tabela-wrap">
          <table className="tabela">
            <thead>
              <tr>
                <th>Disciplina</th>
                <th>Turma</th>
                <th>Docente</th>
                <th className="num">Vagas</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {turmas.map((t) => (
                <tr key={t.id}>
                  <td>{t.disciplina.codigo} — {t.disciplina.designacao}</td>
                  <td>{t.designacao}</td>
                  <td>{t.docente}</td>
                  <td className="num">{t.vagas} / {t.capacidade_maxima}</td>
                  <td className="space-x-3 text-right">
                    <Link to={`/turmas/${t.id}/inscritos`} className="text-tinta hover:underline">Alunos</Link>
                    <Link to={`/turmas/${t.id}/avaliacoes`} className="botao inline-block px-3 py-1 text-sm">Lançar notas</Link>
                    <Link to={`/turmas/${t.id}/pauta`} className="text-tinta hover:underline">Pauta</Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      {coleccao && <Paginacao coleccao={coleccao} aoMudar={setPagina} />}
    </div>
  )
}
