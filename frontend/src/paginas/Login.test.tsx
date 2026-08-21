import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ProvedorAutenticacao } from '@/contextos/AutenticacaoContexto'
import { Login } from './Login'

vi.mock('@/servicos/api', async (importarOriginal) => {
  const original = await importarOriginal<typeof import('@/servicos/api')>()
  return { ...original, api: { ...original.api, post: vi.fn(), get: vi.fn() } }
})

const { api } = await import('@/servicos/api')

function renderizar() {
  return render(
    <MemoryRouter initialEntries={['/login']}>
      <ProvedorAutenticacao>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/painel" element={<p>Painel de facto</p>} />
          <Route path="/recuperar-palavra-passe" element={<p>Recuperar de facto</p>} />
        </Routes>
      </ProvedorAutenticacao>
    </MemoryRouter>,
  )
}

describe('Login', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.mocked(api.post).mockReset()
    vi.mocked(api.get).mockReset()
  })

  it('mostra os campos de e-mail e palavra-passe', () => {
    renderizar()

    expect(screen.getByLabelText('E-mail institucional')).toBeInTheDocument()
    expect(screen.getByLabelText('Palavra-passe')).toBeInTheDocument()
  })

  it('autentica com sucesso e navega para o painel', async () => {
    vi.mocked(api.post).mockResolvedValueOnce({
      token: 'abc',
      utilizador: { id: '1', nome: 'Ana', email: 'ana@escn.ac.mz', perfis: ['ESTUDANTE'], permissoes: [] },
    })
    const utilizador = userEvent.setup()
    renderizar()

    await utilizador.type(screen.getByLabelText('E-mail institucional'), 'ana@escn.ac.mz')
    await utilizador.type(screen.getByLabelText('Palavra-passe'), 'palavra-passe')
    await utilizador.click(screen.getByRole('button', { name: 'Entrar' }))

    await waitFor(() => expect(screen.getByText('Painel de facto')).toBeInTheDocument())
    expect(api.post).toHaveBeenCalledWith('/auth/login', {
      email: 'ana@escn.ac.mz',
      palavra_passe: 'palavra-passe',
    })
  })

  it('mostra uma mensagem genérica em credenciais inválidas (401), sem indicar o campo', async () => {
    const { ErroApi } = await import('@/servicos/api')
    vi.mocked(api.post).mockRejectedValueOnce(new ErroApi('Unauthenticated.', 401))
    const utilizador = userEvent.setup()
    renderizar()

    await utilizador.type(screen.getByLabelText('E-mail institucional'), 'ana@escn.ac.mz')
    await utilizador.type(screen.getByLabelText('Palavra-passe'), 'errada')
    await utilizador.click(screen.getByRole('button', { name: 'Entrar' }))

    expect(await screen.findByRole('alert')).toHaveTextContent('Credenciais inválidas.')
  })

  it('avisa de bloqueio de conta em 423', async () => {
    const { ErroApi } = await import('@/servicos/api')
    vi.mocked(api.post).mockRejectedValueOnce(new ErroApi('Locked.', 423))
    const utilizador = userEvent.setup()
    renderizar()

    await utilizador.type(screen.getByLabelText('E-mail institucional'), 'ana@escn.ac.mz')
    await utilizador.type(screen.getByLabelText('Palavra-passe'), 'errada')
    await utilizador.click(screen.getByRole('button', { name: 'Entrar' }))

    expect(await screen.findByRole('alert')).toHaveTextContent('Conta bloqueada temporariamente')
  })

  it('a ligação "Esqueceu-se" leva à recuperação de palavra-passe', async () => {
    const utilizador = userEvent.setup()
    renderizar()

    await utilizador.click(screen.getByText('Esqueceu-se da palavra-passe?'))

    expect(await screen.findByText('Recuperar de facto')).toBeInTheDocument()
  })
})
