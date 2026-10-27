import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { ProvedorAutenticacao } from '@/contextos/AutenticacaoContexto'
import { ProvedorTema } from '@/contextos/TemaContexto'
import { RotaProtegida } from '@/componentes/RotaProtegida'
import { Layout } from '@/componentes/Layout'
import { Login } from '@/paginas/Login'
import { RecuperarPalavraPasse } from '@/paginas/RecuperarPalavraPasse'
import { RedefinirPalavraPasse } from '@/paginas/RedefinirPalavraPasse'
import { Painel } from '@/paginas/Painel'
import { Inscricoes } from '@/paginas/Inscricoes'
import { Estudantes } from '@/paginas/Estudantes'
import { Aproveitamento } from '@/paginas/Aproveitamento'
import { Docentes } from '@/paginas/Docentes'
import { Cursos } from '@/paginas/Cursos'
import { Disciplinas } from '@/paginas/Disciplinas'
import { Turmas } from '@/paginas/Turmas'
import { Avaliacoes } from '@/paginas/Avaliacoes'
import { Pautas } from '@/paginas/Pautas'
import { Rectificacoes } from '@/paginas/Rectificacoes'
import { Relatorios } from '@/paginas/Relatorios'
import { AnoLectivo } from '@/paginas/AnoLectivo'
import { Notificacoes } from '@/paginas/Notificacoes'
import { Auditoria } from '@/paginas/Auditoria'
import { Utilizadores } from '@/paginas/Utilizadores'
import { Perfis } from '@/paginas/Perfis'
import { Notas } from '@/paginas/Notas'
import { Inscritos } from '@/paginas/Inscritos'

/**
 * Árvore de rotas. Tudo o que exige sessão vive dentro de <RotaProtegida>,
 * que por sua vez usa <Layout> para a casca comum (barra lateral, cabeçalho).
 *
 * Os perfis aqui só filtram a interface (usabilidade). A autorização real
 * está no servidor (RNF-08, RNF-10), que aplica as mesmas regras ou mais
 * estritas — ver docs/fase6-devops-seguranca.md, secção 3.
 */
export function App() {
  return (
    <BrowserRouter>
      <ProvedorTema>
        <ProvedorAutenticacao>
          <Routes>
            <Route path="/login" element={<Login />} />
            <Route path="/recuperar-palavra-passe" element={<RecuperarPalavraPasse />} />
            <Route path="/redefinir-palavra-passe" element={<RedefinirPalavraPasse />} />

            <Route element={<RotaProtegida />}>
              <Route element={<Layout />}>
                <Route path="/painel" element={<Painel />} />

                <Route element={<RotaProtegida perfisPermitidos={['ESTUDANTE']} />}>
                  <Route path="/inscricoes" element={<Inscricoes />} />
                  <Route path="/notas" element={<Notas />} />
                </Route>

                <Route element={<RotaProtegida perfisPermitidos={['ADMINISTRADOR', 'COORDENADOR']} />}>
                  <Route path="/estudantes" element={<Estudantes />} />
                </Route>
                <Route path="/estudantes/:id/aproveitamento" element={<Aproveitamento />} />

                <Route element={<RotaProtegida perfisPermitidos={['ADMINISTRADOR']} />}>
                  <Route path="/docentes" element={<Docentes />} />
                  <Route path="/cursos" element={<Cursos />} />
                  <Route path="/disciplinas" element={<Disciplinas />} />
                  <Route path="/ano-lectivo" element={<AnoLectivo />} />
                  <Route path="/utilizadores" element={<Utilizadores />} />
                  <Route path="/perfis" element={<Perfis />} />
                </Route>

                {/* Turmas, inscritos, avaliações e pauta: direcção e docentes (o
                    servidor limita o docente às suas turmas). O estudante vê
                    as suas disciplinas e notas em /notas. */}
                <Route element={<RotaProtegida perfisPermitidos={['ADMINISTRADOR', 'COORDENADOR', 'DOCENTE']} />}>
                  <Route path="/turmas" element={<Turmas />} />
                  <Route path="/turmas/:turmaId/inscritos" element={<Inscritos />} />
                  <Route path="/turmas/:turmaId/avaliacoes" element={<Avaliacoes />} />
                  <Route path="/turmas/:turmaId/pauta" element={<Pautas />} />
                </Route>
                <Route path="/rectificacoes" element={<Rectificacoes />} />
                <Route path="/notificacoes" element={<Notificacoes />} />

                <Route element={<RotaProtegida perfisPermitidos={['ADMINISTRADOR', 'COORDENADOR']} />}>
                  <Route path="/relatorios" element={<Relatorios />} />
                  <Route path="/auditoria" element={<Auditoria />} />
                </Route>
              </Route>
            </Route>

            <Route path="/" element={<Navigate to="/painel" replace />} />
            <Route path="*" element={<Navigate to="/painel" replace />} />
          </Routes>
        </ProvedorAutenticacao>
      </ProvedorTema>
    </BrowserRouter>
  )
}
