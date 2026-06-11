export interface Column<T> {
  key: string
  label: string
  render?: (row: T) => React.ReactNode
}

interface Props<T> {
  columns: Column<T>[]
  data: T[]
  loading?: boolean
  emptyText?: string
  onRowClick?: (row: T) => void
}

export default function DataTable<T extends object>({
  columns,
  data,
  loading = false,
  emptyText = 'Sin resultados',
  onRowClick,
}: Props<T>) {
  if (loading) {
    return (
      <div className="bg-slate-900 rounded-lg p-8 text-center text-slate-400">
        Cargando...
      </div>
    )
  }

  return (
    <div className="bg-slate-900 rounded-lg overflow-hidden">
      <table className="w-full">
        <thead>
          <tr className="border-b border-slate-800">
            {columns.map((col) => (
              <th
                key={col.key}
                className="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider"
              >
                {col.label}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {data.length === 0 ? (
            <tr>
              <td
                colSpan={columns.length}
                className="px-4 py-8 text-center text-slate-500"
              >
                {emptyText}
              </td>
            </tr>
          ) : (
            data.map((row, idx) => (
              <tr
                key={(row as { id?: string }).id ?? idx}
                className={`border-b border-slate-800 last:border-0 ${
                  onRowClick ? 'cursor-pointer hover:bg-slate-800' : 'hover:bg-slate-800/50'
                } transition-colors`}
                onClick={() => onRowClick?.(row)}
              >
                {columns.map((col) => (
                  <td key={col.key} className="px-4 py-3 text-sm text-slate-300">
                    {col.render
                      ? col.render(row)
                      : String((row as Record<string, unknown>)[col.key] ?? '')}
                  </td>
                ))}
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  )
}
