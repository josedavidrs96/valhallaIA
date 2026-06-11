import { useQuery } from '@tanstack/react-query'
import { useParams, useNavigate } from 'react-router-dom'
import { coachSessionsService } from '@/services/coachSessions'
import StatusBadge from '@/components/admin/StatusBadge'

function formatDateTime(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleString('es-ES', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

export default function CoachRosterPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const { data, isLoading, isError } = useQuery({
    queryKey: ['coach-roster', id],
    queryFn: () => coachSessionsService.getSessionRoster(id!).then(r => r.data),
    enabled: !!id,
  })

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <button
          onClick={() => navigate('/entrenador/clases')}
          className="text-slate-400 hover:text-white text-sm font-medium transition-colors"
        >
          &larr; Volver
        </button>
        <div>
          <h1 className="text-2xl font-bold text-white">Lista de asistencia</h1>
          <p className="text-slate-400 text-sm mt-0.5">Reservas confirmadas para esta sesion</p>
        </div>
      </div>

      {isLoading && (
        <div className="space-y-3 animate-pulse">
          <div className="h-14 bg-slate-800 rounded-xl" />
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="h-12 bg-slate-800 rounded-xl" />
          ))}
        </div>
      )}

      {isError && (
        <div className="bg-slate-800 rounded-xl p-8 text-center">
          <p className="text-red-400">Error al cargar la lista. Intenta de nuevo.</p>
        </div>
      )}

      {data && (
        <>
          {/* Capacity summary */}
          <div className="grid grid-cols-3 gap-4">
            <div className="bg-slate-800 rounded-xl p-4 text-center">
              <p className="text-2xl font-bold text-blue-400">{data.capacity.confirmed}</p>
              <p className="text-slate-400 text-sm mt-1">Confirmados</p>
            </div>
            <div className="bg-slate-800 rounded-xl p-4 text-center">
              <p className="text-2xl font-bold text-green-400">{data.capacity.available}</p>
              <p className="text-slate-400 text-sm mt-1">Disponibles</p>
            </div>
            <div className="bg-slate-800 rounded-xl p-4 text-center">
              <p className="text-2xl font-bold text-white">{data.capacity.max}</p>
              <p className="text-slate-400 text-sm mt-1">Total</p>
            </div>
          </div>

          {/* Roster table */}
          {data.roster.length === 0 ? (
            <div className="bg-slate-800 rounded-xl p-8 text-center">
              <p className="text-slate-400">No hay reservas para esta sesion</p>
            </div>
          ) : (
            <div className="bg-slate-800 rounded-xl overflow-hidden">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-slate-700">
                    <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                      N.° Socio
                    </th>
                    <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                      Nombre
                    </th>
                    <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                      Apellidos
                    </th>
                    <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                      Estado
                    </th>
                    <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                      Hora reserva
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-700">
                  {data.roster.map(item => (
                    <tr key={item.booking_id}>
                      <td className="px-4 py-3 text-slate-400 text-sm font-mono">
                        #{item.member_number}
                      </td>
                      <td className="px-4 py-3 text-white text-sm font-medium">
                        {item.first_name}
                      </td>
                      <td className="px-4 py-3 text-slate-300 text-sm">
                        {item.last_name}
                      </td>
                      <td className="px-4 py-3">
                        <StatusBadge status={item.status} />
                      </td>
                      <td className="px-4 py-3 text-slate-400 text-sm">
                        {formatDateTime(item.booked_at)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </>
      )}
    </div>
  )
}
