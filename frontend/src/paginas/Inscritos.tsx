import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { api } from '@/servicos/api'
import { Alerta } from '@/componentes/Alerta'
import { Paginacao, usePaginacaoLocal } from '@/componentes/Paginacao'
import type { InscritoTurma, Turma } from '@/tipos/api'

const ROTULO_FREQUENCIA: Record<InscritoTurma['situacao_frequencia'], string> = {
  EM_CURSO: 'Em curso',
  DISPENSADO: 'Dispensado',
  ADMITIDO_EXAME: 'Admitido a exame',
  REPROVADO_FREQUENCIA: 'Excluído',
}

/**
 * Estudantes inscritos numa turma (RF-28). Só o docente da turma e a
 * direcção chegam aqui — o servidor recusa os restantes.
 */
export function Inscritos() {
  const { turmaId } = useParams<{ turmaId: string }>()
  const [turma, setTurma] = useState<Turma | null>(null)
  const [inscritos, setInscritos] = useState<InscritoTurma[] | null>(null)
  const [erro, setErro] = useState<string | null>(null)

  const pgInscritos = usePaginacaoLocal(inscritos)
  useEffect(() => {
    api.get<Turma>(`/turmas/${turmaId}`).then(setTurma).catch(() => undefined)
    api
      .get<InscritoTurma[]>(`/turmas/${turmaId}/inscritos`)
      .then(setInscritos)
      .catch(() => setErro('Não foi possível carregar os inscritos desta turma.'))
  }, [turmaId])

  if (erro) return <Alerta>{erro}</Alerta>

  return (
    <div>
      <h1 className="titulo-pagina">Alunos inscritos</h1>
      <p className="mb-6 text-sm text-aco">
        {turma ? `${turma.disciplina.codigo} — ${turma.disciplina.designacao}, turma ${turma.designacao}` : '…'}
        {inscritos && ` · ${inscritos.length} estudante${inscritos.length === 1 ? '' : 's'}`}
      </p>

      {inscritos === null && <p className="text-sm text-aco">A carregar…</p>}

      {inscritos && inscritos.length === 0 && <p className="text-sm text-aco">Ainda não há estudantes inscritos.</p>}

      {inscritos && inscritos.length > 0 && (
        <div className="tabela-wrap">
          <table className="tabela">
            <thead>
              <tr>
                <th>Número</th>
                <th>Nome</th>
                <th className="num">Frequência</th>
                <th>Situação</th>
              </tr>
            </thead>
            <tbody>
              {pgInscritos.visiveis.map((i) => (
                <tr key={i.inscricao_id}>
                  <td>{i.numero_estudante}</td>
                  <td>{i.nome_completo}</td>
                  <td className="num">{i.nota_frequencia ?? '—'}</td>
                  <td>{ROTULO_FREQUENCIA[i.situacao_frequencia]}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      <Paginacao coleccao={pgInscritos.coleccao} aoMudar={pgInscritos.setPagina} />

      <p className="mt-4 text-sm">
        <Link to={`/turmas/${turmaId}/avaliacoes`} className="text-tinta hover:underline">Lançar notas</Link>
        {' · '}
        <Link to={`/turmas/${turmaId}/pauta`} className="text-tinta hover:underline">Pauta</Link>
      </p>
    </div>
  )
}
