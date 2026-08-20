/**
 * Cliente HTTP mínimo para a API do SGA.
 *
 * Espelha o contrato definido em docs/openapi.yaml (Fase 3). Numa fase
 * posterior, este ficheiro pode ser substituído por um cliente gerado
 * automaticamente a partir da especificação (openapi-typescript-codegen),
 * mantendo o mesmo contrato de chamada.
 */

const BASE_URL = import.meta.env.VITE_API_URL ?? '/api/v1'

export class ErroApi extends Error {
  constructor(
    message: string,
    public readonly estado: number,
    public readonly codigoRegra?: string,
  ) {
    super(message)
  }
}

function obterToken(): string | null {
  return localStorage.getItem('sga_token')
}

async function pedido<T>(caminho: string, opcoes: RequestInit = {}): Promise<T> {
  const token = obterToken()

  const resposta = await fetch(`${BASE_URL}${caminho}`, {
    ...opcoes,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...opcoes.headers,
    },
  })

  if (resposta.status === 204) {
    return undefined as T
  }

  const corpo = await resposta.json().catch(() => null)

  if (!resposta.ok) {
    throw new ErroApi(
      corpo?.mensagem ?? 'Ocorreu um erro inesperado.',
      resposta.status,
      corpo?.codigo,
    )
  }

  return corpo as T
}

export const api = {
  get: <T>(caminho: string) => pedido<T>(caminho),
  post: <T>(caminho: string, dados?: unknown) =>
    pedido<T>(caminho, { method: 'POST', body: dados ? JSON.stringify(dados) : undefined }),
  put: <T>(caminho: string, dados?: unknown) =>
    pedido<T>(caminho, { method: 'PUT', body: dados ? JSON.stringify(dados) : undefined }),
  patch: <T>(caminho: string, dados?: unknown) =>
    pedido<T>(caminho, { method: 'PATCH', body: dados ? JSON.stringify(dados) : undefined }),
  delete: <T>(caminho: string) => pedido<T>(caminho, { method: 'DELETE' }),
}
