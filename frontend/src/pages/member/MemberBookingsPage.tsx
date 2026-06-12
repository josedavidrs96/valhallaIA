import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { bookingsService } from '@/services/bookings'
import StatusBadge from '@/components/admin/StatusBadge'
import type { Booking } from '@/types/booking'

const DAY_LABELS: Record<string, string> = {
  monday:    'Lunes',
  tuesday:   'Martes',
  wednesday: 'Miercoles',
  thursday:  'Jueves',
  friday:    'Viernes',
}

function isSessionPast(sessionDate: string, timeSlot: string): boolean {
  const [h, m] = timeSlot.split(':').map(Number)
  const dt = new Date(`${sessionDate}T${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:00`)
  return dt <= new Date()
}

export default function MemberBookingsPage() {
  const queryClient = useQueryClient()

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
        BOOKING_ALREADY_CANCELLED:    'Esta reserva ya estaba cancelada',
        CANCELLATION_WINDOW_EXPIRED:  'No puedes cancelar una sesion que ya ha comenzado',
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
          <div key={i} className="h-16 bg-slate-800 rounded-xl" />
        ))}
      </div>
    )
  }

  const bookings   = data?.data ?? []
  const weeklyUsed = data?.weekly_used ?? 0
  const weeklyMax  = data?.weekly_max ?? 0

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-white">Mis reservas</h1>
          <p className="text-slate-400 text-sm mt-1">Tus reservas de clases</p>
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

      {bookings.length === 0 ? (
        <div className="bg-slate-800 rounded-xl p-8 text-center">
          <p className="text-slate-400">No tienes reservas todavia</p>
        </div>
      ) : (
        <div className="bg-slate-800 rounded-xl overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-slate-700">
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                  Clase
                </th>
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                  Fecha
                </th>
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                  Hora
                </th>
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                  Estado
                </th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-700">
              {bookings.map(booking => {
                const past = isSessionPast(booking.session_date, booking.session.time_slot)
                const canCancel = booking.status === 'confirmed' && !past

                return (
                  <tr key={booking.id} className={past ? 'opacity-60' : ''}>
                    <td className="px-4 py-3 text-white text-sm">
                      {booking.session.class_type_name}
                    </td>
                    <td className="px-4 py-3 text-slate-300 text-sm">
                      <span className="block">
                        {DAY_LABELS[booking.session.day_of_week] ?? booking.session.day_of_week}
                      </span>
                      <span className="text-slate-500 text-xs">{booking.session_date}</span>
                    </td>
                    <td className="px-4 py-3 text-slate-300 text-sm font-mono">
                      {booking.session.time_slot}
                    </td>
                    <td className="px-4 py-3">
                      <StatusBadge status={booking.status} />
                    </td>
                    <td className="px-4 py-3 text-right">
                      {canCancel && (
                        <button
                          onClick={() => handleCancel(booking)}
                          disabled={cancelMutation.isPending}
                          className="text-xs text-red-400 hover:text-red-300 font-medium transition-colors disabled:opacity-50"
                        >
                          Cancelar
                        </button>
                      )}
                      {booking.status === 'confirmed' && past && (
                        <span className="text-xs text-slate-500">Finalizada</span>
                      )}
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
