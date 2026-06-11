import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { ContactSection } from '../ContactSection'

describe('ContactSection', () => {
  it('renders the gym address', () => {
    render(<ContactSection />)
    expect(screen.getByText(/Agustina de Aragon/i)).toBeDefined()
  })

  it('renders email as a mailto link', () => {
    render(<ContactSection />)
    const emailLink = screen.getByRole('link', { name: /info@valhallagym\.com/i })
    expect(emailLink).toBeDefined()
    expect(emailLink.getAttribute('href')).toBe('mailto:info@valhallagym.com')
  })

  it('renders Instagram link that opens in a new tab', () => {
    render(<ContactSection />)
    const instaLink = screen.getByRole('link', { name: /@itsvallhallaworkout/i })
    expect(instaLink).toBeDefined()
    expect(instaLink.getAttribute('target')).toBe('_blank')
  })

  it('renders opening hours with Mon-Fri times', () => {
    render(<ContactSection />)
    // Mon-Fri open time
    expect(screen.getAllByText(/06:00/).length).toBeGreaterThan(0)
    expect(screen.getAllByText(/23:00/).length).toBeGreaterThan(0)
  })
})
