import { Navigate } from 'react-router-dom'
import { useAuth, type UserRole } from '@/contexts/AuthContext'

const roleDashboards: Record<UserRole, string> = {
  admin:  '/admin/socios',
  coach:  '/entrenador/clases',
  member: '/socio/inicio',
}

interface Props {
  allowedRoles: UserRole[]
  children: React.ReactNode
}

export function RoleRoute({ allowedRoles, children }: Props) {
  const { user } = useAuth()

  if (!user) return <Navigate to="/login" replace />

  if (!allowedRoles.includes(user.role)) {
    return <Navigate to={roleDashboards[user.role]} replace />
  }

  return <>{children}</>
}
