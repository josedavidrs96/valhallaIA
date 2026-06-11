import { useState, type FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '@/contexts/AuthContext'

const rolePaths = { admin: '/admin/dashboard', coach: '/entrenador/dashboard', member: '/socio/dashboard' }

export default function LoginPage() {
  const { login, user } = useAuth()
  const navigate = useNavigate()

  const [email, setEmail]         = useState('')
  const [password, setPassword]   = useState('')
  const [remember, setRemember]   = useState(false)
  const [error, setError]         = useState('')
  const [loading, setLoading]     = useState(false)

  if (user) {
    if (user.must_change_password) {
      navigate('/cambiar-contrasena', { replace: true })
    } else {
      navigate(rolePaths[user.role], { replace: true })
    }
    return null
  }

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError('')
    setLoading(true)

    try {
      const userData = await login(email, password, remember)
      if (userData.must_change_password) {
        navigate('/cambiar-contrasena', { replace: true })
      } else {
        navigate(rolePaths[userData.role], { replace: true })
      }
    } catch (err: any) {
      setError(err.response?.data?.error ?? 'Error al iniciar sesion')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen bg-[#0f172a] flex items-center justify-center px-4">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-4xl font-bold text-white tracking-tight">VALHALLA GYM</h1>
          <p className="text-[#60a5fa] mt-2 text-sm">Donde los guerreros se forjan</p>
        </div>

        <form onSubmit={handleSubmit} className="bg-slate-800 rounded-2xl p-8 shadow-xl space-y-5">
          <div>
            <label className="block text-sm font-medium text-slate-300 mb-1">Correo electronico</label>
            <input
              type="email"
              value={email}
              onChange={e => setEmail(e.target.value)}
              required
              className="w-full bg-slate-900 border border-slate-600 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-[#2563eb] focus:ring-1 focus:ring-[#2563eb]"
              placeholder="admin@valhallagym.com"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-300 mb-1">Contrasena</label>
            <input
              type="password"
              value={password}
              onChange={e => setPassword(e.target.value)}
              required
              className="w-full bg-slate-900 border border-slate-600 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-[#2563eb] focus:ring-1 focus:ring-[#2563eb]"
            />
          </div>

          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="remember"
              checked={remember}
              onChange={e => setRemember(e.target.checked)}
              className="rounded border-slate-600 bg-slate-900 text-[#2563eb]"
            />
            <label htmlFor="remember" className="text-sm text-slate-300">Recuerdame</label>
          </div>

          {error && (
            <p className="text-red-400 text-sm text-center">{error}</p>
          )}

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-[#2563eb] hover:bg-[#1d4ed8] disabled:opacity-60 text-white font-semibold py-2.5 rounded-lg transition-colors"
          >
            {loading ? 'Iniciando...' : 'Iniciar sesion'}
          </button>
        </form>
      </div>
    </div>
  )
}
