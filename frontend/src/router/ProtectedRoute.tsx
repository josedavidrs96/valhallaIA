import { Navigate } from 'react-router-dom'
import { useAuth } from '@/contexts/AuthContext'

interface Props {
  children: React.ReactNode
  skipPasswordCheck?: boolean
}

export function ProtectedRoute({ children, skipPasswordCheck = false }: Props) {
  const { user, isAuthenticated, isLoading } = useAuth()

  if (isLoading) return null
  if (!isAuthenticated) return <Navigate to="/login" replace />
  if (!skipPasswordCheck && user?.must_change_password) return <Navigate to="/cambiar-contrasena" replace />

  return <>{children}</>
}
