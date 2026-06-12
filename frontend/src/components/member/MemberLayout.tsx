import { NavLink, Outlet } from 'react-router-dom'
import { useEffect } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { useAuth } from '@/contexts/AuthContext'
import { memberProfileService } from '@/services/memberProfile'
import { bookingsService } from '@/services/bookings'

const navLinks = [
  { to: '/socio/inicio',    label: 'Inicio' },
  { to: '/socio/reservas',  label: 'Mis reservas' },
  { to: '/socio/horario',   label: 'Horario' },
  { to: '/socio/pagos',     label: 'Mis pagos' },
]

export default function MemberLayout() {
  const { logout } = useAuth()
  const queryClient = useQueryClient()

  // Prefetch common member queries on layout mount so page transitions feel instant
  useEffect(() => {
    queryClient.prefetchQuery({
      queryKey: ['my-bookings'],
      queryFn: () => bookingsService.myBookings().then(r => r.data),
      staleTime: 30_000,
    })
    queryClient.prefetchQuery({
      queryKey: ['member-schedule'],
      queryFn: () => memberProfileService.getSchedule().then(r => r.data),
      staleTime: 30_000,
    })
    queryClient.prefetchQuery({
      queryKey: ['member-profile'],
      queryFn: () => memberProfileService.getProfile().then(r => r.data),
      staleTime: 60_000,
    })
  }, [])

  return (
    <div className="min-h-screen bg-slate-950">
      {/* Top nav */}
      <nav className="bg-slate-900 border-b border-slate-800 sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-14">
            {/* Logo */}
            <div className="flex-shrink-0">
              <span className="text-lg font-bold text-white tracking-wide">Valhalla</span>
            </div>

            {/* Nav links */}
            <div className="flex items-center space-x-1">
              {navLinks.map(({ to, label }) => (
                <NavLink
                  key={to}
                  to={to}
                  className={({ isActive }) =>
                    `px-4 py-2 text-sm font-medium transition-colors ${
                      isActive
                        ? 'text-blue-400 border-b-2 border-blue-400'
                        : 'text-slate-300 hover:text-white'
                    }`
                  }
                >
                  {label}
                </NavLink>
              ))}
            </div>

            {/* Logout */}
            <button
              onClick={logout}
              className="text-slate-400 hover:text-white text-sm font-medium transition-colors px-3 py-2"
            >
              Cerrar sesion
            </button>
          </div>
        </div>
      </nav>

      {/* Content */}
      <main className="bg-slate-950 min-h-screen p-6">
        <div className="max-w-7xl mx-auto">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
