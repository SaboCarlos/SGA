import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { Alerta } from './Alerta'

describe('Alerta', () => {
  it('mostra o texto passado como filho', () => {
    render(<Alerta>Algo correu mal.</Alerta>)

    expect(screen.getByRole('alert')).toHaveTextContent('Algo correu mal.')
  })

  it('usa o estilo de erro por omissão', () => {
    render(<Alerta>Erro</Alerta>)

    expect(screen.getByRole('alert').className).toContain('alerta-erro')
  })

  it('usa o estilo de sucesso quando pedido', () => {
    render(<Alerta tipo="sucesso">Feito.</Alerta>)

    expect(screen.getByRole('alert').className).toContain('alerta-sucesso')
  })
})
