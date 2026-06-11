import { useQuery } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { coachSessionsService } from '@/services/coachSessions'
import StatusBadge from '@/components/admin/StatusBadge'

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

export default function CoachSessionsPage() {
  const navigate = useNavigate()

  const { data, isLoading } = useQuery({
    queryKey: ['coach-sessions'],
    queryFn: () => coachSessionsService.getMySessions().then(r => r.data.data),
  })

  if (isLoading) {
    return (
      <div className="space-y-3 animate-pulse">
        {Array.from({ length: 5 }).map((_, i) => (
          <div key={i} className="h-16 bg-slate-800 rounded-xl" />
        ))}
      </div>
    )
  }

  const sessions = (data ?? []).slice().sort((a, b) => {
    const dayDiff = (DAY_ORDER[a.day_of_week] ?? 99) - (DAY_ORDER[b.day_of_week] ?? 99)
    if (dayDiff !== 0) return dayDiff
    return a.time_slot.localeCompare(b.time_slot)
  })

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-white">Mis clases</h1>
        <p className="text-slate-400 text-sm mt-1">Sesiones asignadas a ti</p>
      </div>

      {sessions.length === 0 ? (
        <div className="bg-slate-800 rounded-xl p-8 text-center">
          <p className="text-slate-400">No tienes clases asignadas</p>
        </div>
      ) : (
        <div className="bg-slate-800 rounded-xl overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-slate-700">
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                  Dia
                </th>
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                  Hora
                </th>
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                  Tipo de clase
                </th>
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                  Capacidad
                </th>
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                  Estado
                </th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-700">
              {sessions.map(session => (
                <tr key={session.id}>
                  <td className="px-4 py-3 text-slate-300 text-sm">
                    {DAY_LABELS[session.day_of_week] ?? session.day_of_week}
                  </td>
                  <td className="px-4 py-3 text-slate-300 text-sm font-mono">
                    {session.time_slot}
                  </td>
                  <td className="px-4 py-3 text-white text-sm font-medium">
                    {session.class_type.name}
                  </td>
                  <td className="px-4 py-3 text-slate-300 text-sm">
                    {session.max_capacity} plazas
                  </td>
                  <td className="px-4 py-3">
                    <StatusBadge status={session.status} />
                  </td>
                  <td className="px-4 py-3 text-right">
                    <button
                      onClick={() => navigate(`/entrenador/clases/${session.id}/lista`)}
                      className="text-xs text-blue-400 hover:text-blue-300 font-medium transition-colors"
                    >
                      Ver lista
                    </button>
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
