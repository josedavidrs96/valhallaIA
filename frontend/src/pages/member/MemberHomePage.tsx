import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { memberProfileService } from '@/services/memberProfile'
import StatusBadge from '@/components/admin/StatusBadge'

export default function MemberHomePage() {
  const { data: profile, isLoading } = useQuery({
    queryKey: ['member-profile'],
    queryFn: () => memberProfileService.getProfile().then(r => r.data),
  })

  if (isLoading) {
    return (
      <div className="space-y-4 animate-pulse">
        <div className="h-28 bg-slate-800 rounded-xl" />
        <div className="h-28 bg-slate-800 rounded-xl" />
      </div>
    )
  }

  const formatCents = (cents: number) => (cents / 100).toFixed(2) + ' EUR'

  return (
    <div className="space-y-6">
      {/* Welcome card */}
      <div className="bg-slate-800 rounded-xl p-6">
        <div className="flex items-start justify-between">
          <div>
            <h1 className="text-2xl font-bold text-white">
              Bienvenido, {profile?.first_name ?? ''}
            </h1>
            <p className="text-slate-400 text-sm mt-1">{profile?.email}</p>
            <p className="text-slate-500 text-xs mt-0.5">Socio n.° {profile?.member_number}</p>
          </div>
          {profile?.status && (
            <StatusBadge status={profile.status} />
          )}
        </div>
      </div>

      {/* Plan card */}
      <div className="bg-slate-800 rounded-xl p-6">
        <h2 className="text-base font-semibold text-slate-300 mb-3">Tu plan</h2>
        {profile?.plan ? (
          <div className="space-y-2">
            <p className="text-white font-medium text-lg">{profile.plan.name}</p>
            <p className="text-slate-400 text-sm">
              {profile.plan.classes_per_month} clases / mes
            </p>
            <p className="text-blue-400 font-semibold">
              {formatCents(profile.plan.price_cents)} / mes
            </p>
          </div>
        ) : (
          <p className="text-slate-500 text-sm">Sin plan asignado</p>
        )}
      </div>

      {/* Quick links */}
      <div className="grid grid-cols-2 gap-4">
        <Link
          to="/socio/horario"
          className="bg-blue-600 hover:bg-blue-700 text-white text-center font-medium py-3 px-4 rounded-xl transition-colors"
        >
          Ver horario
        </Link>
        <Link
          to="/socio/reservas"
          className="bg-slate-700 hover:bg-slate-600 text-white text-center font-medium py-3 px-4 rounded-xl transition-colors"
        >
          Mis reservas
        </Link>
      </div>
    </div>
  )
}
