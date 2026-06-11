import { useAuth } from '@/contexts/AuthContext'

export default function MemberDashboard() {
  const { user, logout } = useAuth()

  return (
    <div className="min-h-screen bg-[#0f172a] p-8">
      <div className="max-w-4xl mx-auto">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-2xl font-bold text-white">Mi Panel</h1>
            <p className="text-[#60a5fa] text-sm mt-1">Valhalla Gym</p>
          </div>
          <button
            onClick={logout}
            className="bg-slate-700 hover:bg-slate-600 text-white text-sm px-4 py-2 rounded-lg transition-colors"
          >
            Cerrar sesion
          </button>
        </div>

        <div className="bg-slate-800 rounded-xl p-6">
          <p className="text-slate-300">Bienvenido, <span className="text-white font-semibold">Socio</span></p>
          <p className="text-slate-500 text-sm mt-1">{user?.email}</p>
        </div>
      </div>
    </div>
  )
}
