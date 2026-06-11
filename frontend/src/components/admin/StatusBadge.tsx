interface Props {
  status: string
}

const statusMap: Record<string, string> = {
  active:    'bg-green-900 text-green-300',
  inactive:  'bg-red-900 text-red-300',
  suspended: 'bg-red-900 text-red-300',
  confirmed: 'bg-blue-900 text-blue-300',
  cancelled: 'bg-slate-700 text-slate-400',
}

const labelMap: Record<string, string> = {
  active:    'Activo',
  inactive:  'Inactivo',
  suspended: 'Suspendido',
  confirmed: 'Confirmado',
  cancelled: 'Cancelado',
}

export default function StatusBadge({ status }: Props) {
  const colorClass = statusMap[status] ?? 'bg-slate-700 text-slate-300'
  const label      = labelMap[status] ?? status

  return (
    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${colorClass}`}>
      {label}
    </span>
  )
}
