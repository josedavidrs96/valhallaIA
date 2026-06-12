import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { memberProfileService } from '@/services/memberProfile'
import { bookingsService } from '@/services/bookings'
import type { ClassSession } from '@/types/classSession'

const DAY_LABELS: Record<string, string> = {
  monday:    'Lunes',
  tuesday:   'Martes',
  wednesday: 'Miercoles',
  thursday:  'Jueves',
  friday:    'Viernes',
}

const DAY_ORDER: Record<string, number> = {
  monday: 1, tuesday: 2, wednesday: 3, thursday: 4, friday: 5,
}

const ERROR_MESSAGES: Record<string, string> = {
  SESSION_FULL:              'La sesion esta completa',
  BOOKING_ALREADY_EXISTS:    'Ya tienes una reserva para esta sesion',
  SESSION_NOT_AVAILABLE:     'La sesion no esta disponible',
  MEMBER_HAS_NO_PLAN:        'No tienes un plan activo',
  WEEKLY_LIMIT_REACHED:      'Has alcanzado el limite de clases de tu plan esta semana',
  DAILY_LIMIT_REACHED:       'Solo puedes reservar una clase por dia',
}

type ScheduleResponse = Record<string, ClassSession[]>

export default function MemberSchedulePage() {
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['member-schedule'],
    queryFn: () => memberProfileService.getSchedule().then(r => r.data as ScheduleResponse),
  })

  const { data: bookingsData } = useQuery({
    queryKey: ['my-bookings'],
    queryFn: () => bookingsService.myBookings().then(r => r.data),
  })

  const weeklyUsed = bookingsData?.weekly_used ?? 0
  const weeklyMax  = bookingsData?.weekly_max ?? 0
  const limitReached = weeklyMax > 0 && weeklyUsed >= weeklyMax

  const bookMutation = useMutation({
    mutationFn: (sessionId: string) =>
      bookingsService.create(sessionId).then(r => r.data),
    onSuccess: () => {
      toast.success('Reserva confirmada')
      queryClient.invalidateQueries({ queryKey: ['my-bookings'] })
    },
    onError: (err: unknown) => {
      const axiosErr = err as { response?: { data?: { code?: string; message?: string } } }
      const code = axiosErr?.response?.data?.code ?? ''
      toast.error(ERROR_MESSAGES[code] ?? axiosErr?.response?.data?.message ?? 'Error al reservar')
    },
  })

  const sessions: ClassSession[] = data
    ? Object.entries(data)
        .sort(([a], [b]) => (DAY_ORDER[a] ?? 99) - (DAY_ORDER[b] ?? 99))
        .flatMap(([, daySessions]) => daySessions)
    : []

  if (isLoading) {
    return (
      <div className="space-y-3 animate-pulse">
        {Array.from({ length: 7 }).map((_, i) => (
          <div key={i} className="h-20 bg-slate-800 rounded-xl" />
        ))}
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-white">Horario de clases</h1>
          <p className="text-slate-400 text-sm mt-1">Selecciona una clase para reservar tu plaza</p>
        </div>
        {weeklyMax > 0 && (
          <div className={`rounded-xl px-4 py-3 text-right flex-shrink-0 ${limitReached ? 'bg-red-900/30 border border-red-800' : 'bg-slate-800'}`}>
            <p className="text-xs text-slate-400 uppercase tracking-wider">Clases esta semana</p>
            <p className={`text-2xl font-bold mt-0.5 ${limitReached ? 'text-red-400' : 'text-white'}`}>
              {weeklyUsed}
              <span className="text-slate-500 text-base font-normal"> / {weeklyMax}</span>
            </p>
            {limitReached && (
              <p className="text-xs text-red-400 mt-1">Limite alcanzado</p>
            )}
          </div>
        )}
      </div>

      {sessions.length === 0 ? (
        <div className="bg-slate-800 rounded-xl p-8 text-center">
          <p className="text-slate-400">No hay sesiones disponibles</p>
        </div>
      ) : (
        <div className="space-y-3">
          {sessions.map(session => {
            const isFull      = session.max_capacity <= 0
            const isInactive  = session.status !== 'active'
            const isDisabled  = isFull || isInactive || limitReached || bookMutation.isPending

            return (
              <div
                key={session.id}
                className="bg-slate-800 rounded-xl p-4 flex items-center justify-between gap-4"
              >
                <div className="flex-1 min-w-0">
                  <p className="text-white font-medium truncate">{session.class_type.name}</p>
                  <p className="text-slate-400 text-sm mt-0.5">
                    {DAY_LABELS[session.day_of_week] ?? session.day_of_week} &middot; {session.time_slot}
                  </p>
                  <p className="text-slate-500 text-xs mt-0.5">
                    {session.max_capacity} plazas disponibles
                  </p>
                </div>

                <div className="flex items-center gap-3 flex-shrink-0">
                  {isInactive && (
                    <span className="text-xs text-slate-500 bg-slate-700 px-2 py-0.5 rounded">
                      No disponible
                    </span>
                  )}
                  {isFull && !isInactive && (
                    <span className="text-xs text-red-400 bg-red-900/30 px-2 py-0.5 rounded">
                      Completa
                    </span>
                  )}
                  {limitReached && !isFull && !isInactive && (
                    <span className="text-xs text-amber-400 bg-amber-900/30 px-2 py-0.5 rounded">
                      Limite semanal
                    </span>
                  )}
                  <button
                    onClick={() => bookMutation.mutate(session.id)}
                    disabled={isDisabled}
                    className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                      isDisabled
                        ? 'bg-slate-700 text-slate-500 cursor-not-allowed'
                        : 'bg-blue-600 hover:bg-blue-700 text-white'
                    }`}
                  >
                    Reservar
                  </button>
                </div>
              </div>
            )
          })}
        </div>
      )}
    </div>
  )
}
