import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useQuery as useQueryPlans } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import PageHeader from '@/components/admin/PageHeader'
import DataTable, { type Column } from '@/components/admin/DataTable'
import StatusBadge from '@/components/admin/StatusBadge'
import Modal from '@/components/admin/Modal'
import { membersService } from '@/services/members'
import { plansService } from '@/services/plans'
import type { MemberListItem, CreateMemberPayload } from '@/types/member'
import type { MembershipPlan } from '@/types/plan'

// ─── Create Member Modal ────────────────────────────────────────────────────

interface CreateModalProps {
  open: boolean
  onClose: () => void
  plans: MembershipPlan[]
}

function CreateMemberModal({ open, onClose, plans }: CreateModalProps) {
  const qc = useQueryClient()

  const [form, setForm] = useState<CreateMemberPayload>({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    date_of_birth: '',
    join_date: new Date().toISOString().slice(0, 10),
    membership_plan_id: '',
  })

  const mutation = useMutation({
    mutationFn: () => membersService.create({
      ...form,
      phone: form.phone || undefined,
      date_of_birth: form.date_of_birth || undefined,
      membership_plan_id: form.membership_plan_id || undefined,
    }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['members'] })
      toast.success('Socio creado correctamente')
      onClose()
      setForm({
        first_name: '', last_name: '', email: '',
        phone: '', date_of_birth: '',
        join_date: new Date().toISOString().slice(0, 10),
        membership_plan_id: '',
      })
    },
    onError: (err: unknown) => {
      const msg = (err as { response?: { data?: { error?: string } } })?.response?.data?.error
      toast.error(msg ?? 'Error inesperado')
    },
  })

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) {
    setForm(prev => ({ ...prev, [e.target.name]: e.target.value }))
  }

  return (
    <Modal open={open} onClose={onClose} title="Nuevo Socio">
      <form
        onSubmit={(e) => { e.preventDefault(); mutation.mutate() }}
        className="space-y-4"
      >
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
          <label className="block text-xs text-slate-400 mb-1">Email</label>
          <input
            name="email"
            type="email"
            value={form.email}
            onChange={handleChange}
            required
            className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
          />
        </div>

        <div>
          <label className="block text-xs text-slate-400 mb-1">Telefono (opcional)</label>
          <input
            name="phone"
            value={form.phone}
            onChange={handleChange}
            className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
          />
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="block text-xs text-slate-400 mb-1">Fecha de nacimiento</label>
            <input
              name="date_of_birth"
              type="date"
              value={form.date_of_birth}
              onChange={handleChange}
              className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
            />
          </div>
          <div>
            <label className="block text-xs text-slate-400 mb-1">Fecha de alta</label>
            <input
              name="join_date"
              type="date"
              value={form.join_date}
              onChange={handleChange}
              required
              className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
            />
          </div>
        </div>

        <div>
          <label className="block text-xs text-slate-400 mb-1">Plan</label>
          <select
            name="membership_plan_id"
            value={form.membership_plan_id}
            onChange={handleChange}
            className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
          >
            <option value="">Sin plan</option>
            {plans.map(p => (
              <option key={p.id} value={p.id}>
                {p.name} — {(p.price_cents / 100).toFixed(2)} €
              </option>
            ))}
          </select>
        </div>

        <div className="flex justify-end gap-3 pt-2">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 text-sm text-slate-400 hover:text-white transition-colors"
          >
            Cancelar
          </button>
          <button
            type="submit"
            disabled={mutation.isPending}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded font-medium transition-colors disabled:opacity-50"
          >
            {mutation.isPending ? 'Creando...' : 'Crear socio'}
          </button>
        </div>
      </form>
    </Modal>
  )
}

// ─── Main Page ──────────────────────────────────────────────────────────────

export default function AdminMembersPage() {
  const navigate   = useNavigate()
  const [search, setSearch]     = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [showCreate, setShowCreate] = useState(false)

  // Debounce search
  function handleSearchChange(e: React.ChangeEvent<HTMLInputElement>) {
    const val = e.target.value
    setSearch(val)
    clearTimeout((window as unknown as { _searchTimer?: number })._searchTimer)
    ;(window as unknown as { _searchTimer?: number })._searchTimer = window.setTimeout(() => {
      setDebouncedSearch(val)
    }, 400)
  }

  const { data, isLoading } = useQuery({
    queryKey: ['members', debouncedSearch],
    queryFn: () => membersService.list(debouncedSearch ? { search: debouncedSearch } : undefined),
  })

  const { data: plansData } = useQueryPlans({
    queryKey: ['plans'],
    queryFn: () => plansService.list(),
  })

  const members = data?.data.data ?? []
  const plans   = plansData?.data.data ?? []

  const columns: Column<MemberListItem>[] = [
    { key: 'member_number', label: '#' },
    {
      key: 'name',
      label: 'Nombre',
      render: (row) => `${row.first_name} ${row.last_name}`,
    },
    { key: 'email', label: 'Email' },
    {
      key: 'status',
      label: 'Estado',
      render: (row) => <StatusBadge status={row.status} />,
    },
    {
      key: 'plan',
      label: 'Plan',
      render: (row) => row.plan?.name ?? <span className="text-slate-500">Sin plan</span>,
    },
    { key: 'join_date', label: 'Alta' },
  ]

  return (
    <div>
      <PageHeader
        title="Socios"
        action={
          <button
            onClick={() => setShowCreate(true)}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded font-medium transition-colors"
          >
            + Nuevo socio
          </button>
        }
      />

      {/* Search */}
      <div className="mb-4">
        <input
          type="text"
          placeholder="Buscar por nombre o email..."
          value={search}
          onChange={handleSearchChange}
          className="bg-slate-900 border border-slate-700 rounded px-4 py-2 text-white text-sm w-full max-w-sm focus:outline-none focus:border-blue-500"
        />
      </div>

      <DataTable
        columns={columns}
        data={members}
        loading={isLoading}
        emptyText="No hay socios registrados"
        onRowClick={(row) => navigate(`/admin/socios/${row.id}`)}
      />

      <CreateMemberModal
        open={showCreate}
        onClose={() => setShowCreate(false)}
        plans={plans}
      />
    </div>
  )
}
