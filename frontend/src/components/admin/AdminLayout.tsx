import { NavLink, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '@/contexts/AuthContext'

const navLinks = [
  { to: '/admin/socios',         label: '👥 Socios' },
  { to: '/admin/clases',         label: '🗓 Clases' },
  { to: '/admin/pagos',          label: '💳 Pagos' },
  { to: '/admin/pagos/morosos',  label: '⚠ Morosos' },
]

export default function AdminLayout() {
  const { logout } = useAuth()
  const location   = useLocation()

  function isActive(to: string): boolean {
    if (to === '/admin/socios') return location.pathname.startsWith('/admin/socios')
    if (to === '/admin/pagos/morosos') return location.pathname === '/admin/pagos/morosos'
    if (to === '/admin/pagos') return location.pathname === '/admin/pagos'
    return location.pathname.startsWith(to)
  }

  return (
    <div className="flex min-h-screen">
      {/* Sidebar */}
      <aside className="w-64 bg-slate-900 flex flex-col min-h-screen fixed top-0 left-0 z-10">
        {/* Logo */}
        <div className="px-6 py-6 border-b border-slate-800">
          <span className="text-xl font-bold text-white tracking-wide">Valhalla</span>
          <p className="text-blue-400 text-xs mt-1">Panel de Admin</p>
        </div>

        {/* Nav */}
        <nav className="flex-1 px-3 py-4 space-y-1">
          {navLinks.map(({ to, label }) => (
            <NavLink
              key={to}
              to={to}
              className={() =>
                `flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                  isActive(to)
                    ? 'bg-blue-600 text-white'
                    : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                }`
              }
            >
              {label}
            </NavLink>
          ))}
        </nav>

        {/* Logout */}
        <div className="px-3 py-4 border-t border-slate-800">
          <button
            onClick={logout}
            className="w-full flex items-center px-3 py-2 rounded-lg text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition-colors"
          >
            🚪 Cerrar sesion
          </button>
        </div>
      </aside>

      {/* Main content */}
      <main className="flex-1 ml-64 bg-slate-950 min-h-screen p-6">
        <Outlet />
      </main>
    </div>
  )
}
