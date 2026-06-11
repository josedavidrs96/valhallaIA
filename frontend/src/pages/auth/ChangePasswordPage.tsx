import { useState, type FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '@/contexts/AuthContext'
import api from '@/services/api'

const rolePaths = { admin: '/admin/dashboard', coach: '/entrenador/dashboard', member: '/socio/dashboard' }

export default function ChangePasswordPage() {
  const { logout, refreshUser } = useAuth()
  const navigate = useNavigate()

  const [current, setCurrent]   = useState('')
  const [next, setNext]         = useState('')
  const [confirm, setConfirm]   = useState('')
  const [error, setError]       = useState('')
  const [loading, setLoading]   = useState(false)

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError('')

    if (next !== confirm) {
      setError('Las contrasenas nuevas no coinciden')
      return
    }

    setLoading(true)
    try {
      await api.put('/auth/password', {
        current_password:          current,
        new_password:              next,
        new_password_confirmation: confirm,
      })
      const updated = await refreshUser()
      navigate(rolePaths[updated.role], { replace: true })
    } catch (err: any) {
      setError(err.response?.data?.error ?? 'Error al cambiar la contrasena')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen bg-[#0f172a] flex items-center justify-center px-4">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-4xl font-bold text-white tracking-tight">VALHALLA GYM</h1>
        </div>

        <div className="bg-slate-800 rounded-2xl p-8 shadow-xl">
          <h2 className="text-xl font-bold text-white mb-2">Cambia tu contrasena</h2>
          <p className="text-slate-400 text-sm mb-6">
            Por seguridad, debes cambiar tu contrasena antes de continuar.
          </p>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-slate-300 mb-1">Contrasena actual</label>
              <input
                type="password"
                value={current}
                onChange={e => setCurrent(e.target.value)}
                required
                className="w-full bg-slate-900 border border-slate-600 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-[#2563eb] focus:ring-1 focus:ring-[#2563eb]"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-300 mb-1">Nueva contrasena</label>
              <input
                type="password"
                value={next}
                onChange={e => setNext(e.target.value)}
                required
                className="w-full bg-slate-900 border border-slate-600 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-[#2563eb] focus:ring-1 focus:ring-[#2563eb]"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-300 mb-1">Confirmar nueva contrasena</label>
              <input
                type="password"
                value={confirm}
                onChange={e => setConfirm(e.target.value)}
                required
                className="w-full bg-slate-900 border border-slate-600 text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-[#2563eb] focus:ring-1 focus:ring-[#2563eb]"
              />
            </div>

            {error && <p className="text-red-400 text-sm">{error}</p>}

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-[#2563eb] hover:bg-[#1d4ed8] disabled:opacity-60 text-white font-semibold py-2.5 rounded-lg transition-colors"
            >
              {loading ? 'Guardando...' : 'Cambiar contrasena'}
            </button>

            <button
              type="button"
              onClick={logout}
              className="w-full text-slate-400 hover:text-slate-200 text-sm py-1 transition-colors"
            >
              Cerrar sesion
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}
