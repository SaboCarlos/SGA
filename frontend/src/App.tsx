import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { ProvedorAutenticacao } from '@/contextos/AutenticacaoContexto'
import { RotaProtegida } from '@/componentes/RotaProtegida'
import { Layout } from '@/componentes/Layout'
import { Login } from '@/paginas/Login'
import { Painel } from '@/paginas/Painel'
import { Inscricoes } from '@/paginas/Inscricoes'

/**
 * Árvore de rotas. Tudo o que exige sessão vive dentro de <RotaProtegida>,
 * que por sua vez usa <Layout> para a casca comum (barra lateral, cabeçalho).
 *
 * Cresce por perfil à medida que os ecrãs da Fase 4 avançam (ver backlog,
 * Fase 1 §8.2): a seguir a US-13 (esta), vêm US-16/17/18 (avaliação) e
 * US-22 (aproveitamento).
 */
export function App() {
  return (
    <BrowserRouter>
      <ProvedorAutenticacao>
        <Routes>
          <Route path="/login" element={<Login />} />

          <Route element={<RotaProtegida />}>
            <Route element={<Layout />}>
              <Route path="/painel" element={<Painel />} />

              <Route element={<RotaProtegida perfisPermitidos={['ESTUDANTE']} />}>
                <Route path="/inscricoes" element={<Inscricoes />} />
              </Route>
            </Route>
          </Route>

          <Route path="/" element={<Navigate to="/painel" replace />} />
          <Route path="*" element={<Navigate to="/painel" replace />} />
        </Routes>
      </ProvedorAutenticacao>
    </BrowserRouter>
  )
}
