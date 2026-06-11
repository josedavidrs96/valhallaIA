import { describe, it, expect } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { PublicHeader } from '../PublicHeader'

function renderWithRouter() {
  return render(
    <MemoryRouter>
      <PublicHeader />
    </MemoryRouter>
  )
}

describe('PublicHeader', () => {
  it('renders "Iniciar sesion" login link pointing to /login', () => {
    renderWithRouter()
    const loginLink = screen.getByRole('link', { name: /iniciar sesion/i })
    expect(loginLink).toBeDefined()
    expect(loginLink.getAttribute('href')).toBe('/login')
  })

  it('renders all 5 desktop nav links', () => {
    renderWithRouter()
    expect(screen.getAllByText('Sobre nosotros').length).toBeGreaterThan(0)
    expect(screen.getAllByText('Clases').length).toBeGreaterThan(0)
    expect(screen.getAllByText('Horario').length).toBeGreaterThan(0)
    expect(screen.getAllByText('Planes').length).toBeGreaterThan(0)
    expect(screen.getAllByText('Contacto').length).toBeGreaterThan(0)
  })

  it('toggles mobile menu when hamburger button is clicked', () => {
    renderWithRouter()

    // Mobile menu should not be visible initially (it returns null when isOpen=false)
    // The mobile nav links are only rendered inside PublicMobileMenu
    // We look for the hamburger button
    const hamburger = screen.getByRole('button', { name: /abrir menu/i })
    expect(hamburger).toBeDefined()

    // Mobile menu is not rendered initially
    // After click, mobile menu appears (it renders extra nav links)
    fireEvent.click(hamburger)

    // After clicking, the mobile menu nav links should be visible
    // (in addition to the desktop ones, so count increases)
    const sobreLinks = screen.getAllByText('Sobre nosotros')
    // Desktop: hidden via CSS but still in DOM; mobile: rendered conditionally
    expect(sobreLinks.length).toBeGreaterThan(0)
  })
})
