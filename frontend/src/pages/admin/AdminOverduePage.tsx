import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import PageHeader from '@/components/admin/PageHeader'
import DataTable, { type Column } from '@/components/admin/DataTable'
import { paymentsService } from '@/services/payments'
import type { OverdueMember } from '@/types/payment'

export default function AdminOverduePage() {
  const navigate = useNavigate()

  const { data, isLoading } = useQuery({
    queryKey: ['overdue-members'],
    queryFn: () => paymentsService.overdue(),
  })

  const members = data?.data.data ?? []

  const columns: Column<OverdueMember>[] = [
    {
      key: 'member_number',
      label: '#',
      render: (row) => String(row.member_number),
    },
    {
      key: 'name',
      label: 'Nombre',
      render: (row) => `${row.first_name} ${row.last_name}`,
    },
    { key: 'email', label: 'Email' },
    {
      key: 'plan_name',
      label: 'Plan',
      render: (row) => row.plan_name ?? <span className="text-slate-500">Sin plan</span>,
    },
    {
      key: 'last_payment_date',
      label: 'Ultimo pago',
      render: (row) =>
        row.last_payment_date ? (
          row.last_payment_date
        ) : (
          <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-900 text-red-300">
            Sin pagos
          </span>
        ),
    },
  ]

  return (
    <div>
      <PageHeader title="Socios Morosos" />

      <p className="text-slate-400 text-sm mb-4">
        Socios que no han pagado en el mes actual.
        Total: <span className="text-white font-semibold">{members.length}</span>
      </p>

      <DataTable
        columns={columns}
        data={members}
        loading={isLoading}
        emptyText="No hay socios morosos"
        onRowClick={(row) => navigate(`/admin/socios/${row.member_id}`)}
      />
    </div>
  )
}
