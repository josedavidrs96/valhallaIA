import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { bookingsService } from '@/services/bookings'
import type { Booking } from '@/types/booking'

const DAY_LABELS: Record<string, string> = {
  monday:    'Lunes',
  tuesday:   'Martes',
  wednesday: 'Miercoles',
  thursday:  'Jueves',
  friday:    'Viernes',
}

type Tab = 'upcoming' | 'past' | 'cancelled'

function sessionDateTime(sessionDate: string, timeSlot: string): Date {
  const [h, m] = timeSlot.split(':').map(Number)
  return new Date(`${sessionDate}T${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:00`)
}

function isSessionPast(sessionDate: string, timeSlot: string): boolean {
  return sessionDateTime(sessionDate, timeSlot) <= new Date()
}

function formatDate(sessionDate: string): string {
  const d = new Date(sessionDate + 'T12:00:00')
  return d.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' })
}

function daysUntil(sessionDate: string): number {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const target = new Date(sessionDate + 'T00:00:00')
  return Math.round((target.getTime() - today.getTime()) / 86_400_000)
}

export default function MemberBookingsPage() {
  const queryClient = useQueryClient()
  const [tab, setTab] = useState<Tab>('upcoming')

  const { data, isLoading } = useQuery({
    queryKey: ['my-bookings'],
    queryFn: () => bookingsService.myBookings().then(r => r.data),
  })

  const cancelMutation = useMutation({
    mutationFn: (bookingId: string) =>
      bookingsService.cancel(bookingId).then(r => r.data),
    onSuccess: () => {
      toast.success('Reserva cancelada')
      queryClient.invalidateQueries({ queryKey: ['my-bookings'] })
    },
    onError: (err: unknown) => {
      const axiosErr = err as { response?: { data?: { code?: string; message?: string } } }
      const code = axiosErr?.response?.data?.code ?? ''
      const msgs: Record<string, string> = {
        BOOKING_ALREADY_CANCELLED:   'Esta reserva ya estaba cancelada',
        CANCELLATION_WINDOW_EXPIRED: 'No puedes cancelar una sesion que ya ha comenzado',
      }
      toast.error(msgs[code] ?? axiosErr?.response?.data?.message ?? 'Error al cancelar')
    },
  })

  function handleCancel(booking: Booking) {
    if (!window.confirm('¿Seguro que quieres cancelar esta reserva?')) return
    cancelMutation.mutate(booking.id)
  }

  if (isLoading) {
    return (
      <div className="space-y-3 animate-pulse">
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className="h-20 bg-slate-800 rounded-xl" />
        ))}
      </div>
    )
  }

  const allBookings  = data?.data ?? []
  const weeklyUsed   = data?.weekly_used ?? 0
  const weeklyMax    = data?.weekly_max ?? 0

  const upcoming  = allBookings
    .filter(b => b.status === 'confirmed' && !isSessionPast(b.session_date, b.session.time_slot))
    .sort((a, b) => sessionDateTime(a.session_date, a.session.time_slot).getTime()
                  - sessionDateTime(b.session_date, b.session.time_slot).getTime())

  const past      = allBookings
    .filter(b => b.status === 'confirmed' && isSessionPast(b.session_date, b.session.time_slot))
    .sort((a, b) => sessionDateTime(b.session_date, b.session.time_slot).getTime()
                  - sessionDateTime(a.session_date, a.session.time_slot).getTime())

  const cancelled = allBookings
    .filter(b => b.status === 'cancelled')
    .sort((a, b) => sessionDateTime(b.session_date, b.session.time_slot).getTime()
                  - sessionDateTime(a.session_date, a.session.time_slot).getTime())

  const tabs: { key: Tab; label: string; count: number }[] = [
    { key: 'upcoming',  label: 'Proximas',   count: upcoming.length },
    { key: 'past',      label: 'Pasadas',    count: past.length },
    { key: 'cancelled', label: 'Canceladas', count: cancelled.length },
  ]

  const current = tab === 'upcoming' ? upcoming : tab === 'past' ? past : cancelled

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-white">Mis reservas</h1>
          <p className="text-slate-400 text-sm mt-1">Historial de tus reservas de clases</p>
        </div>
        {weeklyMax > 0 && (
          <div className="bg-slate-800 rounded-xl px-4 py-3 text-right flex-shrink-0">
            <p className="text-xs text-slate-400 uppercase tracking-wider">Esta semana</p>
            <p className="text-2xl font-bold text-white mt-0.5">
              {weeklyUsed}
              <span className="text-slate-500 text-base font-normal"> / {weeklyMax}</span>
            </p>
          </div>
        )}
      </div>

      {/* Tabs */}
      <div className="flex gap-1 bg-slate-800/50 p-1 rounded-xl">
        {tabs.map(t => (
          <button
            key={t.key}
            onClick={() => setTab(t.key)}
            className={`flex-1 flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-sm font-medium transition-colors ${
              tab === t.key
                ? 'bg-slate-700 text-white shadow-sm'
                : 'text-slate-400 hover:text-slate-200'
            }`}
          >
            {t.label}
            {t.count > 0 && (
              <span className={`text-xs px-1.5 py-0.5 rounded-full font-semibold ${
                tab === t.key ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300'
              }`}>
                {t.count}
              </span>
            )}
          </button>
        ))}
      </div>

      {/* Tab content */}
      {current.length === 0 ? (
        <div className="bg-slate-800 rounded-xl p-10 text-center">
          <p className="text-slate-400">
            {tab === 'upcoming'  && 'No tienes reservas proximas'}
            {tab === 'past'      && 'No tienes clases pasadas'}
            {tab === 'cancelled' && 'No tienes reservas canceladas'}
          </p>
        </div>
      ) : tab === 'upcoming' ? (
        <div className="space-y-3">
          {upcoming.map(booking => {
            const days = daysUntil(booking.session_date)
            return (
              <div
                key={booking.id}
                className="bg-slate-800 rounded-xl p-4 flex items-center justify-between gap-4 border border-slate-700"
              >
                <div className="flex items-center gap-4 flex-1 min-w-0">
                  {/* Day chip */}
                  <div className="flex-shrink-0 bg-blue-600/20 border border-blue-600/40 rounded-lg px-3 py-2 text-center min-w-[64px]">
                    <p className="text-blue-400 text-xs font-medium uppercase">
                      {DAY_LABELS[booking.session.day_of_week]?.slice(0, 3) ?? '—'}
                    </p>
                    <p className="text-white text-lg font-bold leading-none mt-0.5">
                      {booking.session.time_slot}
                    </p>
                  </div>
                  {/* Info */}
                  <div className="min-w-0">
                    <p className="text-white font-semibold truncate">{booking.session.class_type_name}</p>
                    <p className="text-slate-400 text-sm mt-0.5">{formatDate(booking.session_date)}</p>
                    <p className="text-xs mt-0.5 font-medium">
                      {days === 0
                        ? <span className="text-amber-400">Hoy</span>
                        : days === 1
                        ? <span className="text-green-400">Manana</span>
                        : <span className="text-slate-500">En {days} dias</span>
                      }
                    </p>
                  </div>
                </div>
                <button
                  onClick={() => handleCancel(booking)}
                  disabled={cancelMutation.isPending}
                  className="flex-shrink-0 text-xs text-red-400 hover:text-red-300 font-medium border border-red-800/50 hover:border-red-600/50 px-3 py-1.5 rounded-lg transition-colors disabled:opacity-50"
                >
                  Cancelar
                </button>
              </div>
            )
          })}
        </div>
      ) : (
        <div className="bg-slate-800 rounded-xl overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-slate-700">
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">Clase</th>
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">Fecha</th>
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">Hora</th>
                {tab === 'cancelled' && (
                  <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">Estado</th>
                )}
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-700">
              {current.map(booking => (
                <tr key={booking.id} className="opacity-70">
                  <td className="px-4 py-3 text-white text-sm">{booking.session.class_type_name}</td>
                  <td className="px-4 py-3 text-slate-300 text-sm">
                    <span className="block">{DAY_LABELS[booking.session.day_of_week] ?? booking.session.day_of_week}</span>
                    <span className="text-slate-500 text-xs">{booking.session_date}</span>
                  </td>
                  <td className="px-4 py-3 text-slate-300 text-sm font-mono">{booking.session.time_slot}</td>
                  {tab === 'cancelled' && (
                    <td className="px-4 py-3">
                      <span className="text-xs text-slate-500 bg-slate-700 px-2 py-0.5 rounded">Cancelada</span>
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
