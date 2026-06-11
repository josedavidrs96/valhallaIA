import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import PageHeader from '@/components/admin/PageHeader'
import DataTable, { type Column } from '@/components/admin/DataTable'
import Modal from '@/components/admin/Modal'
import { paymentsService } from '@/services/payments'
import { membersService } from '@/services/members'
import { plansService } from '@/services/plans'
import type { PaymentListItem, RecordPaymentPayload } from '@/types/payment'
import type { MemberListItem } from '@/types/member'
import type { MembershipPlan } from '@/types/plan'

function formatEuros(cents: number): string {
  return (cents / 100).toFixed(2) + ' €'
}

// ─── Record Payment Modal ───────────────────────────────────────────────────

interface RecordModalProps {
  open: boolean
  onClose: () => void
  members: MemberListItem[]
  plans: MembershipPlan[]
}

function RecordPaymentModal({ open, onClose, members, plans }: RecordModalProps) {
  const qc = useQueryClient()

  const [form, setForm] = useState({
    member_id:          '',
    membership_plan_id: '',
    amount_euros:       '',
    payment_date:       new Date().toISOString().slice(0, 10),
    notes:              '',
  })

  const [memberSearch, setMemberSearch] = useState('')

  const filteredMembers = members.filter(m => {
    const q = memberSearch.toLowerCase()
    return (
      q === '' ||
      `${m.first_name} ${m.last_name}`.toLowerCase().includes(q) ||
      String(m.member_number).includes(q) ||
      m.email.toLowerCase().includes(q)
    )
  })

  const mutation = useMutation({
    mutationFn: () => {
      const payload: RecordPaymentPayload = {
        member_id:          form.member_id,
        membership_plan_id: form.membership_plan_id,
        amount_cents:       Math.round(parseFloat(form.amount_euros) * 100),
        payment_date:       form.payment_date,
        notes:              form.notes || undefined,
      }
      return paymentsService.record(payload)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['payments'] })
      toast.success('Pago registrado')
      onClose()
      setForm({
        member_id: '', membership_plan_id: '', amount_euros: '',
        payment_date: new Date().toISOString().slice(0, 10), notes: '',
      })
      setMemberSearch('')
    },
    onError: (err: unknown) => {
      const msg = (err as { response?: { data?: { error?: string } } })?.response?.data?.error
      toast.error(msg ?? 'Error inesperado')
    },
  })

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) {
    setForm(prev => ({ ...prev, [e.target.name]: e.target.value }))
  }

  // Auto-fill amount from plan price
  function handlePlanChange(e: React.ChangeEvent<HTMLSelectElement>) {
    const planId = e.target.value
    const plan   = plans.find(p => p.id === planId)
    setForm(prev => ({
      ...prev,
      membership_plan_id: planId,
      amount_euros: plan ? (plan.price_cents / 100).toFixed(2) : prev.amount_euros,
    }))
  }

  return (
    <Modal open={open} onClose={onClose} title="Registrar Pago">
      <form onSubmit={(e) => { e.preventDefault(); mutation.mutate() }} className="space-y-4">
        {/* Member search */}
        <div>
          <label className="block text-xs text-slate-400 mb-1">Buscar socio</label>
          <input
            type="text"
            placeholder="Nombre, numero o email..."
            value={memberSearch}
            onChange={(e) => setMemberSearch(e.target.value)}
            className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500 mb-1"
          />
          <select
            name="member_id"
            value={form.member_id}
            onChange={handleChange}
            required
            size={4}
            className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
          >
            <option value="">-- Seleccionar socio --</option>
            {filteredMembers.slice(0, 20).map(m => (
              <option key={m.id} value={m.id}>
                #{m.member_number} — {m.first_name} {m.last_name}
              </option>
            ))}
          </select>
        </div>

        {/* Plan */}
        <div>
          <label className="block text-xs text-slate-400 mb-1">Plan</label>
          <select
            name="membership_plan_id"
            value={form.membership_plan_id}
            onChange={handlePlanChange}
            required
            className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
          >
            <option value="">Seleccionar plan...</option>
            {plans.map(p => (
              <option key={p.id} value={p.id}>
                {p.name} — {(p.price_cents / 100).toFixed(2)} €
              </option>
            ))}
          </select>
        </div>

        {/* Amount & date */}
        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="block text-xs text-slate-400 mb-1">Importe (€)</label>
            <input
              name="amount_euros"
              type="number"
              step="0.01"
              min="0"
              value={form.amount_euros}
              onChange={handleChange}
              required
              placeholder="35.00"
              className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
            />
          </div>
          <div>
            <label className="block text-xs text-slate-400 mb-1">Fecha de pago</label>
            <input
              name="payment_date"
              type="date"
              value={form.payment_date}
              onChange={handleChange}
              required
              className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500"
            />
          </div>
        </div>

        {/* Notes */}
        <div>
          <label className="block text-xs text-slate-400 mb-1">Notas (opcional)</label>
          <textarea
            name="notes"
            value={form.notes}
            onChange={handleChange}
            rows={2}
            className="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500 resize-none"
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
            {mutation.isPending ? 'Registrando...' : 'Registrar pago'}
          </button>
        </div>
      </form>
    </Modal>
  )
}

// ─── Main Page ──────────────────────────────────────────────────────────────

export default function AdminPaymentsPage() {
  const [showRecord, setShowRecord] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['payments'],
    queryFn: () => paymentsService.list(),
  })

  const { data: membersData } = useQuery({
    queryKey: ['members'],
    queryFn: () => membersService.list({ per_page: 200 }),
  })

  const { data: plansData } = useQuery({
    queryKey: ['plans'],
    queryFn: () => plansService.list(),
  })

  const payments = data?.data.data ?? []
  const members  = membersData?.data.data ?? []
  const plans    = plansData?.data.data ?? []

  const columns: Column<PaymentListItem>[] = [
    { key: 'member_number', label: '#' },
    { key: 'member_name', label: 'Socio' },
    { key: 'plan_name', label: 'Plan' },
    {
      key: 'amount_cents',
      label: 'Importe',
      render: (row) => formatEuros(row.amount_cents),
    },
    { key: 'payment_date', label: 'Fecha de pago' },
    { key: 'billing_month', label: 'Mes' },
  ]

  return (
    <div>
      <PageHeader
        title="Pagos"
        action={
          <button
            onClick={() => setShowRecord(true)}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded font-medium transition-colors"
          >
            + Registrar pago
          </button>
        }
      />

      <DataTable
        columns={columns}
        data={payments}
        loading={isLoading}
        emptyText="No hay pagos registrados"
      />

      <RecordPaymentModal
        open={showRecord}
        onClose={() => setShowRecord(false)}
        members={members}
        plans={plans}
      />
    </div>
  )
}
