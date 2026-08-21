import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { api, ErroApi } from './api'

function mockarResposta(corpo: unknown, estado: number) {
  return {
    status: estado,
    ok: estado >= 200 && estado < 300,
    json: () => Promise.resolve(corpo),
  } as Response
}

describe('api', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('devolve o corpo da resposta quando o pedido é bem sucedido', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(mockarResposta({ nome: 'Ana' }, 200)))

    const resultado = await api.get<{ nome: string }>('/qualquer')

    expect(resultado).toEqual({ nome: 'Ana' })
  })

  it('usa "mensagem" quando a resposta segue o formato de erro de regra de negócio', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(mockarResposta({ codigo: 'RN-04', mensagem: 'Turma esgotada.' }, 409)),
    )

    await expect(api.post('/inscricoes')).rejects.toMatchObject({
      message: 'Turma esgotada.',
      estado: 409,
      codigoRegra: 'RN-04',
    })
  })

  it('usa a primeira mensagem de "errors" quando a resposta é uma validação padrão do Laravel', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(
        mockarResposta(
          { message: 'The given data was invalid.', errors: { email: ['O campo email é obrigatório.'] } },
          422,
        ),
      ),
    )

    await expect(api.post('/auth/login')).rejects.toMatchObject({
      message: 'O campo email é obrigatório.',
      estado: 422,
    })
  })

  it('recorre a "message" quando não há "mensagem" nem "errors"', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(mockarResposta({ message: 'Não autenticado.' }, 401)))

    await expect(api.get('/auth/me')).rejects.toMatchObject({ message: 'Não autenticado.', estado: 401 })
  })

  it('usa a mensagem genérica quando a resposta não tem corpo interpretável', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        status: 500,
        ok: false,
        json: () => Promise.reject(new Error('não é JSON')),
      } as unknown as Response),
    )

    await expect(api.get('/qualquer')).rejects.toMatchObject({
      message: 'Ocorreu um erro inesperado.',
      estado: 500,
    })
  })

  it('não envia cabeçalho de autorização sem sessão iniciada', async () => {
    const pedidoFetch = vi.fn().mockResolvedValue(mockarResposta({}, 200))
    vi.stubGlobal('fetch', pedidoFetch)

    await api.get('/painel')

    const [, opcoes] = pedidoFetch.mock.calls[0]
    expect((opcoes.headers as Record<string, string>).Authorization).toBeUndefined()
  })

  it('envia o token guardado como cabeçalho de autorização', async () => {
    localStorage.setItem('sga_token', 'token-123')
    const pedidoFetch = vi.fn().mockResolvedValue(mockarResposta({}, 200))
    vi.stubGlobal('fetch', pedidoFetch)

    await api.get('/painel')

    const [, opcoes] = pedidoFetch.mock.calls[0]
    expect((opcoes.headers as Record<string, string>).Authorization).toBe('Bearer token-123')
  })

  it('erro criado tem sempre uma instância de ErroApi', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(mockarResposta({ mensagem: 'Falhou.' }, 400)))

    await expect(api.get('/qualquer')).rejects.toBeInstanceOf(ErroApi)
  })
})
