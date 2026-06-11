import { usePublicSchedule } from '@/hooks/usePublicSchedule'

export function ScheduleSection() {
  const { schedule, isLoading, isMock } = usePublicSchedule()

  // Derive time slots from the first day (all days share the same slots)
  const slots = schedule[0]?.slots ?? []

  return (
    <section
      id="horario"
      className="py-20 px-4 sm:px-6 lg:px-8 bg-slate-900"
    >
      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-12">
          <h2 className="text-3xl sm:text-4xl font-bold text-white mb-3">
            Horario Semanal
          </h2>
          <p className="text-slate-400 text-base">
            Clases disponibles de lunes a viernes en 7 horarios diarios.
          </p>
          {isMock && (
            <span className="inline-block mt-3 text-xs text-amber-400 border border-amber-400/30 bg-amber-400/10 px-3 py-1 rounded-full">
              Datos aproximados
            </span>
          )}
        </div>

        {isLoading ? (
          // Loading skeleton
          <div className="overflow-x-auto" aria-label="Cargando horario">
            <div className="min-w-[640px] space-y-2">
              {Array.from({ length: 8 }).map((_, i) => (
                <div
                  key={i}
                  className="h-10 bg-slate-800 rounded-lg animate-pulse"
                />
              ))}
            </div>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-[640px] w-full border-collapse">
              <thead>
                <tr>
                  <th className="bg-slate-800 text-slate-400 text-sm font-medium px-4 py-3 text-left rounded-tl-lg w-20">
                    Hora
                  </th>
                  {schedule.map((day) => (
                    <th
                      key={day.day}
                      className="bg-slate-800 text-slate-300 text-sm font-semibold px-4 py-3 text-center"
                    >
                      {day.day_label}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {slots.map((slot, rowIndex) => (
                  <tr
                    key={slot}
                    className={rowIndex % 2 === 0 ? 'bg-slate-800/50' : 'bg-slate-800/30'}
                  >
                    <td className="text-[#60a5fa] text-sm font-mono px-4 py-3 font-medium">
                      {slot}
                    </td>
                    {schedule.map((day) => (
                      <td
                        key={day.day}
                        className="text-slate-300 text-sm px-4 py-3 text-center"
                      >
                        {day.class_type.name}
                      </td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </section>
  )
}
