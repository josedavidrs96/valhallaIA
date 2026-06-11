import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { PricingSection } from '../PricingSection'

describe('PricingSection', () => {
  it('renders three plan cards', () => {
    render(<PricingSection />)
    expect(screen.getByText('Plan 2 Dias')).toBeDefined()
    expect(screen.getByText('Plan 3 Dias')).toBeDefined()
    expect(screen.getByText('Plan 4-5 Dias')).toBeDefined()
  })

  it('shows correct prices for all plans', () => {
    render(<PricingSection />)
    // Prices rendered as text nodes alongside /mes
    expect(screen.getByText('€35')).toBeDefined()
    expect(screen.getByText('€38')).toBeDefined()
    expect(screen.getByText('€40')).toBeDefined()
  })

  it('shows "Mas popular" badge on the 4-5 dias plan', () => {
    render(<PricingSection />)
    expect(screen.getByText('Mas popular')).toBeDefined()
  })

  it('has no purchase button on any card', () => {
    render(<PricingSection />)
    expect(screen.queryAllByRole('button')).toHaveLength(0)
  })

  it('shows cash payment footnote', () => {
    render(<PricingSection />)
    expect(screen.getByText(/Pago en efectivo/i)).toBeDefined()
  })
})
