interface Props {
  tipo?: 'erro' | 'sucesso'
  children: React.ReactNode
}

const ESTILOS = {
  erro: 'bg-red-50 text-red-700',
  sucesso: 'bg-green-50 text-green-700',
}

/** Caixa de aviso repetida em quase todos os ecrãs (erro de validação, erro de regra, confirmação). */
export function Alerta({ tipo = 'erro', children }: Props) {
  return (
    <div role="alert" className={`mb-4 rounded px-4 py-3 text-sm ${ESTILOS[tipo]}`}>
      {children}
    </div>
  )
}
