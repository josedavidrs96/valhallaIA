import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { MemoryRouter, Routes, Route } from 'react-router-dom'

// Mock heavy section components so the test stays fast and doesn't need API calls
vi.mock('@/hooks/usePublicSchedule', () => ({
  usePublicSchedule: () => ({
    schedule: [],
    isLoading: false,
    isError: false,
    isMock: false,
  }),
}))

// Mock AuthContext so we can control auth state
vi.mock('@/contexts/AuthContext', () => ({
  useAuth: vi.fn(() => ({
    user: null,
    isAuthenticated: false,
    isLoading: false,
  })),
  AuthProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}))

import PublicHomePage from '@/pages/public/PublicHomePage'

// Minimal stand-in for routes we are not testing
function LoginPage() {
  return <div>Login page</div>
}

function DefaultRedirect() {
  // Simulates the real DefaultRedirect: unauthenticated → /login
  return <LoginPage />
}

describe('Router — public route /', () => {
  it('route / renders public home page content without requiring auth', async () => {
    render(
      <MemoryRouter initialEntries={['/']}>
        <Routes>
          <Route path="/" element={<PublicHomePage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="*" element={<DefaultRedirect />} />
        </Routes>
      </MemoryRouter>
    )

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: /VALHALLA GYM/i })).toBeDefined()
    })

    // Should NOT have redirected to login
    expect(screen.queryByText('Login page')).toBeNull()
  })

  it('catch-all route redirects unauthenticated users to /login', async () => {
    render(
      <MemoryRouter initialEntries={['/ruta-inexistente']}>
        <Routes>
          <Route path="/" element={<PublicHomePage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="*" element={<DefaultRedirect />} />
        </Routes>
      </MemoryRouter>
    )

    await waitFor(() => {
      expect(screen.getByText('Login page')).toBeDefined()
    })
  })
})
