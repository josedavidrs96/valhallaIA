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

export default function MemberBookingsPage() {
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['my-bookings'],
    queryFn: () => bookingsService.myBookings().then(r => r.data.data),
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
      const msg  = code === 'BOOKING_ALREADY_CANCELLED'
        ? 'Esta reserva ya estaba cancelada'
        : axiosErr?.response?.data?.message ?? 'Error al cancelar'
      toast.error(msg)
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

  const bookings = data ?? []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-white">Mis reservas</h1>
        <p className="text-slate-400 text-sm mt-1">Tus reservas de clases</p>
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
                  Dia
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
              {bookings.map(booking => (
                <tr key={booking.id}>
                  <td className="px-4 py-3 text-white text-sm">
                    {booking.session.class_type_name}
                  </td>
                  <td className="px-4 py-3 text-slate-300 text-sm">
                    {DAY_LABELS[booking.session.day_of_week] ?? booking.session.day_of_week}
                  </td>
                  <td className="px-4 py-3 text-slate-300 text-sm font-mono">
                    {booking.session.time_slot}
                  </td>
                  <td className="px-4 py-3">
                    <StatusBadge status={booking.status} />
                  </td>
                  <td className="px-4 py-3 text-right">
                    {booking.status === 'confirmed' && (
                      <button
                        onClick={() => handleCancel(booking)}
                        disabled={cancelMutation.isPending}
                        className="text-xs text-red-400 hover:text-red-300 font-medium transition-colors disabled:opacity-50"
                      >
                        Cancelar
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
