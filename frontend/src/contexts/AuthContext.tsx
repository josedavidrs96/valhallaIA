import { createContext, useContext, useEffect, useState } from 'react'
import api from '@/services/api'

export type UserRole = 'admin' | 'coach' | 'member'

export interface AuthUser {
  id: string
  email: string
  role: UserRole
  status: string
  must_change_password: boolean
}

interface AuthContextValue {
  user: AuthUser | null
  isAuthenticated: boolean
  isLoading: boolean
  login: (email: string, password: string, rememberMe: boolean) => Promise<AuthUser>
  logout: () => Promise<void>
  refreshUser: () => Promise<AuthUser>
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser]         = useState<AuthUser | null>(null)
  const [isLoading, setLoading] = useState(true)

  useEffect(() => {
    const token = localStorage.getItem('auth_token')
    if (!token) { setLoading(false); return }

    api.get('/auth/me')
      .then(res => setUser(res.data))
      .catch(() => { localStorage.removeItem('auth_token') })
      .finally(() => setLoading(false))
  }, [])

  async function login(email: string, password: string, rememberMe: boolean) {
    const res = await api.post('/auth/login', { email, password, remember_me: rememberMe })
    const { token, user: userData } = res.data
    localStorage.setItem('auth_token', token)
    setUser(userData)
    return userData as AuthUser
  }

  async function refreshUser(): Promise<AuthUser> {
    const res = await api.get('/auth/me')
    setUser(res.data)
    return res.data as AuthUser
  }

  async function logout() {
    await api.post('/auth/logout').catch(() => {})
    localStorage.removeItem('auth_token')
    setUser(null)
    window.location.href = '/login'
  }

  return (
    <AuthContext.Provider value={{ user, isAuthenticated: !!user, isLoading, login, logout, refreshUser }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
