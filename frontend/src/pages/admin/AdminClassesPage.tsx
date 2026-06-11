import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import PageHeader from '@/components/admin/PageHeader'
import StatusBadge from '@/components/admin/StatusBadge'
import Modal from '@/components/admin/Modal'
import { classSessionsService } from '@/services/classSessions'
import { classTypesService, coachesService } from '@/services/staff'
import type { ClassSession } from '@/types/classSession'
import type { ClassType, Coach } from '@/types/classType'

const DAY_LABELS: Record<string, string> = {
  monday:    'Lunes',
  tuesday:   'Martes',
  wednesday: 'Miercoles',
  thursday:  'Jueves',
  friday:    'Viernes',
}
const DAYS    = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
const SLOTS   = ['07:45', '12:15', '16:15', '17:30', '18:45', '20:00', '21:15']

// ─── Session Action Modal ───────────────────────────────────────────────────

interface SessionActionsModalProps {
  session: ClassSession | null
  onClose: () => void
}

function SessionActionsModal({ session, onClose }: SessionActionsModalProps) {
  const qc = useQueryClient()

  function makeHandler(fn: () => Promise<unknown>, successMsg: string) {
    return () =>
      fn()
        .then(() => {
          qc.invalidateQueries({ queryKey: ['class-sessions'] })
          toast.success(successMsg)
          onClose()
        })
        .catch((err: unknown) => {
          const msg = (err as { response?: { data?: { error?: string } } })?.response?.data?.error
          toast.error(msg ?? 'Error inesperado')
        })
  }

  if (!session) return null

  const isCancelled = session.status === 'cancelled'

  return (
    <Modal open={!!session} onClose={onClose} title="Opciones de Sesion">
      <div className="space-y-2 text-sm text-slate-300 mb-4">
        <p><span className="text-slate-500">Clase:</span> {session.class_type.name}</p>
        <p><span className="text-slate-500">Dia:</span> {DAY_LABELS[session.day_of_week]}</p>
        <p><span className="text-slate-500">Hora:</span> {session.time_slot}</p>
        <p><span className="text-slate-500">Estado:</span> <StatusBadge status={session.status} /></p>
      </div>

      <div className="flex flex-col gap-2">
        {isCancelled ? (
          <button
            onClick={makeHandler(() => classSessionsService.restore(session.id), 'Sesion restaurada')}
            className="px-4 py-2 bg-green-700 hover:bg-green-600 text-white text-sm rounded transition-colors"
          >
            Restaurar sesion
          </button>
        ) : (
          <button
            onClick={makeHandler(() => classSessionsService.cancel(session.id), 'Sesion cancelada')}
            className="px-4 py-2 bg-yellow-700 hover:bg-yellow-600 text-white text-sm rounded transition-colors"
          >
            Cancelar sesion
          </button>
        )}
        <button
          onClick={makeHandler(() => classSessionsService.delete(session.id), 'Sesion eliminada')}
          className="px-4 py-2 bg-red-800 hover:bg-red-700 text-white text-sm rounded transition-colors"
        >
          Eliminar sesion
        </button>
        <button
          onClick={onClose}
          className="px-4 py-2 text-slate-400 hover:text-white text-sm transition-colors"
        >
          Cerrar
        </button>
      </div>
    </Modal>
  )
}

// ─── Create Session Modal ───────────────────────────────────────────────────

interface CreateModalProps {
  open: boolean
  onClose: () => void
  classTypes: ClassType[]
  coaches: Coach[]
}

function CreateClassSessionModal({ open, onClose, classTypes, coaches }: CreateModalProps) {
  const qc = useQueryClient()

  const [form, setForm] = useState({
    class_type_id: '',
    coach_id:      '',
    day_of_week:   'monday',
    time_slot:     '07:45',
    max_capacity:  '15',
  })

  const mutation = useMutation({
    mutationFn: () =>
      classSessionsService.create({
        class_type_id:  form.class_type_id,
        coach_user_id:  form.coach_id,  // service sends as coach_user_id but backend reads coach_id
        day_of_week:    form.day_of_week,
        time_slot:      form.time_slot,
        max_capacity:   parseInt(form.max_capacity, 10),
      }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['class-sessions'] })
      toast.success('Sesion creada')
      onClose()
      setForm({ class_type_id: '', coach_id: '', day_of_week: 'monday', time_slot: '07:45', max_capacity: '15' })
    },
    onError: (err: unknown) => {
      const msg = (err as { response?: { data?: { error?: string } } })?.response?.data?.error
      toast.error(msg ?? 'Error inesperado')
    },
  })

  function handleChange(e: React.ChangeEvent<HTMLSelectElement | HTMLInputElement>) {
    setForm(prev => ({ ...prev, [e.target.name]: e.target.value }))
  }

  return (
    <Modal open={open} onClose={onClose} title="Nueva Sesion">
      <form onSubmit={(e) => { e.preventDefault(); mutation.mutate() }} className="space-y-4">
        <div>
          <label className="block text-xs text-slate-400 mb-1">Tipo de clase</label>
          <select
            name="class_type_id"
            value={form.class_type_id}
            onChange={handleChange}
            required
            className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
          >
            <option value="">Seleccionar tipo...</option>
            {classTypes.map(ct => (
              <option key={ct.id} value={ct.id}>{ct.name}</option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-xs text-slate-400 mb-1">Entrenador (opcional)</label>
          <select
            name="coach_id"
            value={form.coach_id}
            onChange={handleChange}
            className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
          >
            <option value="">Sin entrenador</option>
            {coaches.map(c => (
              <option key={c.user_id} value={c.user_id}>
                {c.first_name} {c.last_name}
              </option>
            ))}
          </select>
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="block text-xs text-slate-400 mb-1">Dia</label>
            <select
              name="day_of_week"
              value={form.day_of_week}
              onChange={handleChange}
              className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
            >
              {DAYS.map(d => (
                <option key={d} value={d}>{DAY_LABELS[d]}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="block text-xs text-slate-400 mb-1">Hora</label>
            <select
              name="time_slot"
              value={form.time_slot}
              onChange={handleChange}
              className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
            >
              {SLOTS.map(s => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>
          </div>
        </div>

        <div>
          <label className="block text-xs text-slate-400 mb-1">Capacidad maxima</label>
          <input
            name="max_capacity"
            type="number"
            min="1"
            max="50"
            value={form.max_capacity}
            onChange={handleChange}
            required
            className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
          />
        </div>

        <div className="flex justify-end gap-3 pt-2">
          <button type="button" onClick={onClose} className="px-4 py-2 text-sm text-slate-400 hover:text-white">
            Cancelar
          </button>
          <button
            type="submit"
            disabled={mutation.isPending}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded font-medium disabled:opacity-50"
          >
            {mutation.isPending ? 'Creando...' : 'Crear sesion'}
          </button>
        </div>
      </form>
    </Modal>
  )
}

// ─── Weekly Schedule Grid ───────────────────────────────────────────────────

interface CellProps {
  sessions: ClassSession[]
  onSelect: (s: ClassSession) => void
}

function ScheduleCell({ sessions, onSelect }: CellProps) {
  if (sessions.length === 0) {
    return <td className="border border-slate-800 px-2 py-2 align-top min-w-[120px]" />
  }

  return (
    <td className="border border-slate-800 px-2 py-2 align-top min-w-[120px]">
      <div className="space-y-1">
        {sessions.map(s => (
          <button
            key={s.id}
            onClick={() => onSelect(s)}
            className="w-full text-left px-2 py-1.5 rounded text-xs bg-slate-800 hover:bg-slate-700 transition-colors"
          >
            <div className="font-medium text-white truncate">{s.class_type.name}</div>
            {s.coach && (
              <div className="text-slate-400 truncate">{s.coach.email}</div>
            )}
            <div className="flex items-center gap-1 mt-0.5">
              <StatusBadge status={s.status} />
              <span className="text-slate-500">{s.max_capacity} plazas</span>
            </div>
          </button>
        ))}
      </div>
    </td>
  )
}

// ─── Main Page ──────────────────────────────────────────────────────────────

export default function AdminClassesPage() {
  const [showCreate, setShowCreate]       = useState(false)
  const [selectedSession, setSelected]    = useState<ClassSession | null>(null)

  const { data: sessionsData, isLoading } = useQuery({
    queryKey: ['class-sessions'],
    queryFn: () => classSessionsService.list(),
  })

  const { data: classTypesData } = useQuery({
    queryKey: ['class-types'],
    queryFn: () => classTypesService.list(),
  })

  const { data: coachesData } = useQuery({
    queryKey: ['coaches'],
    queryFn: () => coachesService.list(),
  })

  const sessions   = sessionsData?.data.data ?? []
  const classTypes = classTypesData?.data.data ?? []
  const coaches    = coachesData?.data.data ?? []

  // Build grid: slot → day → sessions[]
  const grid: Record<string, Record<string, ClassSession[]>> = {}
  for (const slot of SLOTS) {
    grid[slot] = {}
    for (const day of DAYS) {
      grid[slot][day] = []
    }
  }
  for (const s of sessions) {
    if (grid[s.time_slot]?.[s.day_of_week]) {
      grid[s.time_slot][s.day_of_week].push(s)
    }
  }

  return (
    <div>
      <PageHeader
        title="Clases"
        action={
          <button
            onClick={() => setShowCreate(true)}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded font-medium transition-colors"
          >
            + Nueva sesion
          </button>
        }
      />

      {isLoading ? (
        <div className="text-slate-400 p-4">Cargando...</div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-sm">
            <thead>
              <tr>
                <th className="border border-slate-800 px-3 py-2 text-left text-xs text-slate-400 font-semibold uppercase bg-slate-900 min-w-[70px]">
                  Hora
                </th>
                {DAYS.map(d => (
                  <th
                    key={d}
                    className="border border-slate-800 px-3 py-2 text-center text-xs text-slate-400 font-semibold uppercase bg-slate-900 min-w-[130px]"
                  >
                    {DAY_LABELS[d]}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {SLOTS.map(slot => (
                <tr key={slot}>
                  <td className="border border-slate-800 px-3 py-2 text-slate-400 text-xs font-mono bg-slate-900/50">
                    {slot}
                  </td>
                  {DAYS.map(day => (
                    <ScheduleCell
                      key={day}
                      sessions={grid[slot][day]}
                      onSelect={setSelected}
                    />
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <CreateClassSessionModal
        open={showCreate}
        onClose={() => setShowCreate(false)}
        classTypes={classTypes}
        coaches={coaches}
      />

      <SessionActionsModal
        session={selectedSession}
        onClose={() => setSelected(null)}
      />
    </div>
  )
}
