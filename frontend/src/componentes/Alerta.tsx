interface Props {
  tipo?: 'erro' | 'sucesso'
  children: React.ReactNode
}

const ESTILOS = {
  erro: 'alerta-erro',
  sucesso: 'alerta-sucesso',
}

/** Caixa de aviso repetida em quase todos os ecrãs (erro de validação, erro de regra, confirmação). */
export function Alerta({ tipo = 'erro', children }: Props) {
  return (
    <div role="alert" className={`alerta mb-4 ${ESTILOS[tipo]}`}>
      {children}
    </div>
  )
}
