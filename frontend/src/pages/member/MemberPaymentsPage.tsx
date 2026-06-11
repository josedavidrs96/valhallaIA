import { useQuery } from '@tanstack/react-query'
import { memberProfileService } from '@/services/memberProfile'

function formatCents(cents: number): string {
  return (cents / 100).toFixed(2) + ' EUR'
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('es-ES', {
    day: '2-digit', month: '2-digit', year: 'numeric',
  })
}

function formatBillingMonth(month: string): string {
  // month format: "2025-05"
  const [year, mon] = month.split('-')
  const date = new Date(Number(year), Number(mon) - 1, 1)
  return date.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })
}

export default function MemberPaymentsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['member-payments'],
    queryFn: () => memberProfileService.getPayments().then(r => r.data.data),
  })

  if (isLoading) {
    return (
      <div className="space-y-3 animate-pulse">
        <div className="h-10 bg-slate-800 rounded-xl" />
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className="h-14 bg-slate-800 rounded-xl" />
        ))}
      </div>
    )
  }

  const payments = data ?? []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-white">Mis pagos</h1>
        <p className="text-slate-400 text-sm mt-1">Historial de pagos registrados</p>
      </div>

      {payments.length === 0 ? (
        <div className="bg-slate-800 rounded-xl p-8 text-center">
          <p className="text-slate-400">Sin pagos registrados</p>
        </div>
      ) : (
        <div className="bg-slate-800 rounded-xl overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-slate-700">
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                  Fecha pago
                </th>
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                  Mes facturacion
                </th>
                <th className="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                  Plan
                </th>
                <th className="text-right text-xs font-medium text-slate-400 uppercase tracking-wider px-4 py-3">
                  Importe
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-700">
              {payments.map(payment => (
                <tr key={payment.id}>
                  <td className="px-4 py-3 text-slate-300 text-sm">
                    {formatDate(payment.payment_date)}
                  </td>
                  <td className="px-4 py-3 text-slate-300 text-sm capitalize">
                    {formatBillingMonth(payment.billing_month)}
                  </td>
                  <td className="px-4 py-3 text-white text-sm">
                    {payment.plan_name}
                  </td>
                  <td className="px-4 py-3 text-right text-blue-400 font-medium text-sm">
                    {formatCents(payment.amount_cents)}
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
