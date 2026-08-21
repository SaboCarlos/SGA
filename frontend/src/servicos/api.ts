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
    const primeiroErroDeValidacao = corpo?.errors && Object.values(corpo.errors)[0]
    throw new ErroApi(
      corpo?.mensagem ??
        (Array.isArray(primeiroErroDeValidacao) ? primeiroErroDeValidacao[0] : corpo?.message) ??
        'Ocorreu um erro inesperado.',
      resposta.status,
      corpo?.codigo,
    )
  }

  return corpo as T
}

/**
 * Para respostas binárias (PDF/CSV de pautas e relatórios), que pedido<T>
 * não serve — força sempre .json() no corpo da resposta. Abre o ficheiro
 * numa nova aba em vez de o gravar directamente: o sandbox do browser aqui
 * bloqueia downloads iniciados por script, mas uma aba nova com um blob URL
 * continua a permitir ao utilizador guardar a partir do visualizador nativo.
 */
async function baixar(caminho: string): Promise<void> {
  const token = obterToken()

  const resposta = await fetch(`${BASE_URL}${caminho}`, {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  })

  if (!resposta.ok) {
    throw new ErroApi('Não foi possível obter o ficheiro.', resposta.status)
  }

  const blob = await resposta.blob()
  const url = URL.createObjectURL(blob)
  window.open(url, '_blank')
  setTimeout(() => URL.revokeObjectURL(url), 60_000)
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
  baixar,
}
