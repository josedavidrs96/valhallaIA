import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import PageHeader from '@/components/admin/PageHeader'
import StatusBadge from '@/components/admin/StatusBadge'
import DataTable, { type Column } from '@/components/admin/DataTable'
import Modal from '@/components/admin/Modal'
import { membersService } from '@/services/members'
import { plansService } from '@/services/plans'
import { bookingsService } from '@/services/bookings'
import type { Member, UpdateMemberPayload } from '@/types/member'
import type { MembershipPlan } from '@/types/plan'
import type { Booking } from '@/types/booking'

const DAY_LABELS: Record<string, string> = {
  monday:    'Lunes',
  tuesday:   'Martes',
  wednesday: 'Miercoles',
  thursday:  'Jueves',
  friday:    'Viernes',
}

// ─── Edit Member Modal ──────────────────────────────────────────────────────

interface EditModalProps {
  open: boolean
  onClose: () => void
  member: Member
}

function EditMemberModal({ open, onClose, member }: EditModalProps) {
  const qc = useQueryClient()

  const [form, setForm] = useState<UpdateMemberPayload>({
    first_name:    member.first_name,
    last_name:     member.last_name,
    phone:         member.phone ?? '',
    date_of_birth: member.date_of_birth ?? '',
  })

  const mutation = useMutation({
    mutationFn: () => membersService.update(member.id, {
      ...form,
      phone:         form.phone || undefined,
      date_of_birth: form.date_of_birth || undefined,
    }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['member', member.id] })
      toast.success('Socio actualizado')
      onClose()
    },
    onError: (err: unknown) => {
      const msg = (err as { response?: { data?: { error?: string } } })?.response?.data?.error
      toast.error(msg ?? 'Error inesperado')
    },
  })

  function handleChange(e: React.ChangeEvent<HTMLInputElement>) {
    setForm(prev => ({ ...prev, [e.target.name]: e.target.value }))
  }

  return (
    <Modal open={open} onClose={onClose} title="Editar Socio">
      <form onSubmit={(e) => { e.preventDefault(); mutation.mutate() }} className="space-y-4">
        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="block text-xs text-slate-400 mb-1">Nombre</label>
            <input
              name="first_name"
              value={form.first_name}
              onChange={handleChange}
              required
              className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
            />
          </div>
          <div>
            <label className="block text-xs text-slate-400 mb-1">Apellidos</label>
            <input
              name="last_name"
              value={form.last_name}
              onChange={handleChange}
              required
              className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
            />
          </div>
        </div>
        <div>
          <label className="block text-xs text-slate-400 mb-1">Telefono</label>
          <input
            name="phone"
            value={form.phone ?? ''}
            onChange={handleChange}
            className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
          />
        </div>
        <div>
          <label className="block text-xs text-slate-400 mb-1">Fecha de nacimiento</label>
          <input
            name="date_of_birth"
            type="date"
            value={form.date_of_birth ?? ''}
            onChange={handleChange}
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
            {mutation.isPending ? 'Guardando...' : 'Guardar'}
          </button>
        </div>
      </form>
    </Modal>
  )
}

// ─── Assign Plan Modal ──────────────────────────────────────────────────────

interface AssignPlanModalProps {
  open: boolean
  onClose: () => void
  memberId: string
  plans: MembershipPlan[]
  currentPlanId?: string | null
}

function AssignPlanModal({ open, onClose, memberId, plans, currentPlanId }: AssignPlanModalProps) {
  const qc = useQueryClient()
  const [selectedPlanId, setSelectedPlanId] = useState(currentPlanId ?? '')

  const mutation = useMutation({
    mutationFn: () => membersService.assignPlan(memberId, { membership_plan_id: selectedPlanId }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['member', memberId] })
      toast.success('Plan actualizado')
      onClose()
    },
    onError: (err: unknown) => {
      const msg = (err as { response?: { data?: { error?: string } } })?.response?.data?.error
      toast.error(msg ?? 'Error inesperado')
    },
  })

  return (
    <Modal open={open} onClose={onClose} title="Cambiar Plan">
      <div className="space-y-4">
        <select
          value={selectedPlanId}
          onChange={(e) => setSelectedPlanId(e.target.value)}
          className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
        >
          <option value="">Seleccionar plan...</option>
          {plans.map(p => (
            <option key={p.id} value={p.id}>
              {p.name} — {(p.price_cents / 100).toFixed(2)} €
            </option>
          ))}
        </select>
        <div className="flex justify-end gap-3">
          <button type="button" onClick={onClose} className="px-4 py-2 text-sm text-slate-400 hover:text-white">
            Cancelar
          </button>
          <button
            onClick={() => mutation.mutate()}
            disabled={mutation.isPending || !selectedPlanId}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded font-medium disabled:opacity-50"
          >
            {mutation.isPending ? 'Guardando...' : 'Asignar plan'}
          </button>
        </div>
      </div>
    </Modal>
  )
}

// ─── Main Page ──────────────────────────────────────────────────────────────

export default function AdminMemberDetailPage() {
  const { id }    = useParams<{ id: string }>()
  const navigate  = useNavigate()
  const qc        = useQueryClient()

  const [showEdit, setShowEdit]           = useState(false)
  const [showAssignPlan, setShowAssignPlan] = useState(false)

  const { data: memberData, isLoading } = useQuery({
    queryKey: ['member', id],
    queryFn: () => membersService.get(id!),
    enabled: !!id,
  })

  const { data: plansData } = useQuery({
    queryKey: ['plans'],
    queryFn: () => plansService.list(),
  })

  const { data: bookingsData } = useQuery({
    queryKey: ['member-bookings', id],
    queryFn: () => bookingsService.getMemberBookings(id!),
    enabled: !!id,
  })

  const activateMutation = useMutation({
    mutationFn: () => membersService.activate(id!),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['member', id] })
      toast.success('Socio activado')
    },
    onError: (err: unknown) => {
      const msg = (err as { response?: { data?: { error?: string } } })?.response?.data?.error
      toast.error(msg ?? 'Error inesperado')
    },
  })

  const deactivateMutation = useMutation({
    mutationFn: () => membersService.deactivate(id!),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['member', id] })
      toast.success('Socio desactivado')
    },
    onError: (err: unknown) => {
      const msg = (err as { response?: { data?: { error?: string } } })?.response?.data?.error
      toast.error(msg ?? 'Error inesperado')
    },
  })

  if (isLoading) {
    return <div className="text-slate-400 p-6">Cargando...</div>
  }

  const member = memberData?.data
  if (!member) {
    return <div className="text-slate-400 p-6">Socio no encontrado</div>
  }

  const plans    = plansData?.data.data ?? []
  const bookings = bookingsData?.data.data ?? []

  const bookingColumns: Column<Booking>[] = [
    {
      key: 'day',
      label: 'Dia',
      render: (row) => DAY_LABELS[row.session?.day_of_week] ?? row.session?.day_of_week ?? '-',
    },
    {
      key: 'time',
      label: 'Hora',
      render: (row) => row.session?.time_slot ?? '-',
    },
    {
      key: 'class_type',
      label: 'Clase',
      render: (row) => row.session?.class_type_name ?? '-',
    },
    {
      key: 'status',
      label: 'Estado',
      render: (row) => <StatusBadge status={row.status} />,
    },
  ]

  return (
    <div>
      <PageHeader
        title={`${member.first_name} ${member.last_name}`}
        action={
          <button
            onClick={() => navigate('/admin/socios')}
            className="text-slate-400 hover:text-white text-sm transition-colors"
          >
            ← Volver a socios
          </button>
        }
      />

      {/* Info card */}
      <div className="bg-slate-900 rounded-lg p-6 mb-6">
        <div className="flex items-start justify-between">
          <div className="space-y-2">
            <div className="flex items-center gap-3">
              <span className="text-slate-400 text-sm">#{member.member_number}</span>
              <StatusBadge status={member.status} />
            </div>
            <p className="text-slate-300 text-sm">
              <span className="text-slate-500">Email:</span> {member.email}
            </p>
            {member.phone && (
              <p className="text-slate-300 text-sm">
                <span className="text-slate-500">Telefono:</span> {member.phone}
              </p>
            )}
            {member.date_of_birth && (
              <p className="text-slate-300 text-sm">
                <span className="text-slate-500">Fecha de nacimiento:</span> {member.date_of_birth}
              </p>
            )}
            <p className="text-slate-300 text-sm">
              <span className="text-slate-500">Alta:</span> {member.join_date}
            </p>
          </div>

          <div className="flex gap-2 flex-wrap">
            <button
              onClick={() => setShowEdit(true)}
              className="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded transition-colors"
            >
              Editar
            </button>
            {member.status === 'active' ? (
              <button
                onClick={() => deactivateMutation.mutate()}
                disabled={deactivateMutation.isPending}
                className="px-3 py-1.5 bg-red-900 hover:bg-red-800 text-red-300 text-xs rounded transition-colors disabled:opacity-50"
              >
                Desactivar
              </button>
            ) : (
              <button
                onClick={() => activateMutation.mutate()}
                disabled={activateMutation.isPending}
                className="px-3 py-1.5 bg-green-900 hover:bg-green-800 text-green-300 text-xs rounded transition-colors disabled:opacity-50"
              >
                Activar
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Plan section */}
      <div className="bg-slate-900 rounded-lg p-6 mb-6">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-base font-semibold text-white">Plan de membresia</h2>
          <button
            onClick={() => setShowAssignPlan(true)}
            className="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition-colors"
          >
            Cambiar plan
          </button>
        </div>
        {member.plan ? (
          <div className="text-slate-300 text-sm space-y-1">
            <p><span className="text-slate-500">Plan:</span> {member.plan.name}</p>
            <p><span className="text-slate-500">Precio:</span> {(member.plan.price_cents / 100).toFixed(2)} €/mes</p>
            {member.plan.classes_per_month && (
              <p><span className="text-slate-500">Clases/mes:</span> {member.plan.classes_per_month}</p>
            )}
          </div>
        ) : (
          <p className="text-slate-500 text-sm">Sin plan asignado</p>
        )}
      </div>

      {/* Bookings */}
      <div>
        <h2 className="text-base font-semibold text-white mb-3">Reservas</h2>
        <DataTable
          columns={bookingColumns}
          data={bookings}
          emptyText="Sin reservas"
        />
      </div>

      {/* Modals */}
      <EditMemberModal
        open={showEdit}
        onClose={() => setShowEdit(false)}
        member={member}
      />
      <AssignPlanModal
        open={showAssignPlan}
        onClose={() => setShowAssignPlan(false)}
        memberId={member.id}
        plans={plans}
        currentPlanId={member.plan?.id}
      />
    </div>
  )
}
