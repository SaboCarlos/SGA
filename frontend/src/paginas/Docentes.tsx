import { FormEvent, useEffect, useState } from 'react'
import { api, ErroApi } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import type { Docente } from '@/tipos/api'

const ESTADO_INICIAL = { nome_completo: '', email: '', codigo: '', grau_academico: '', categoria: '' }

/** Cadastro de docentes (UC07 / US-07). */
export function Docentes() {
  const [docentes, setDocentes] = useState<Docente[] | null>(null)
  const [aMostrarFormulario, setAMostrarFormulario] = useState(false)
  const [formulario, setFormulario] = useState(ESTADO_INICIAL)
  const [erro, setErro] = useState<string | null>(null)
  const [aSubmeter, setASubmeter] = useState(false)

  useEffect(() => {
    carregar()
  }, [])

  function carregar() {
    api.get<Docente[]>('/docentes').then(setDocentes).catch(() => setErro('Não foi possível carregar os docentes.'))
  }

  async function submeter(evento: FormEvent) {
    evento.preventDefault()
    setErro(null)
    setASubmeter(true)

    try {
      await api.post('/docentes', formulario)
      setFormulario(ESTADO_INICIAL)
      setAMostrarFormulario(false)
      carregar()
    } catch (e: unknown) {
      setErro(e instanceof ErroApi ? e.message : 'Não foi possível registar o docente.')
    } finally {
      setASubmeter(false)
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="titulo-pagina">Docentes</h1>
        <button onClick={() => setAMostrarFormulario((v) => !v)} className="botao">
          {aMostrarFormulario ? 'Cancelar' : 'Novo docente'}
        </button>
      </div>

      {erro && <Alerta>{erro}</Alerta>}

      {aMostrarFormulario && (
        <form onSubmit={submeter} className="cartao mb-6 grid grid-cols-2 gap-3 p-4">
          <input required placeholder="Nome completo" value={formulario.nome_completo}
            onChange={(e) => setFormulario({ ...formulario, nome_completo: e.target.value })}
            className="entrada col-span-2" />
          <input required type="email" placeholder="E-mail institucional" value={formulario.email}
            onChange={(e) => setFormulario({ ...formulario, email: e.target.value })}
            className="entrada" />
          <input required placeholder="Código (ex.: DOC003)" value={formulario.codigo}
            onChange={(e) => setFormulario({ ...formulario, codigo: e.target.value })}
            className="entrada" />
          <input placeholder="Grau académico" value={formulario.grau_academico}
            onChange={(e) => setFormulario({ ...formulario, grau_academico: e.target.value })}
            className="entrada" />
          <input placeholder="Categoria" value={formulario.categoria}
            onChange={(e) => setFormulario({ ...formulario, categoria: e.target.value })}
            className="entrada" />
          <button type="submit" disabled={aSubmeter} className="botao col-span-2 mt-2">
            {aSubmeter ? 'A registar…' : 'Registar docente'}
          </button>
        </form>
      )}

      {docentes === null && <p className="text-sm text-aco">A carregar…</p>}

      {docentes && (
        <div className="tabela-wrap">
          <table className="tabela">
            <thead>
              <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Grau</th>
                <th>Categoria</th>
              </tr>
            </thead>
            <tbody>
              {docentes.map((d) => (
                <tr key={d.id}>
                  <td className="num">{d.codigo}</td>
                  <td>{d.nome_completo}</td>
                  <td>{d.grau_academico ?? '—'}</td>
                  <td>{d.categoria ?? '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
