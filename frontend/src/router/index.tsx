import { lazy, Suspense } from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider, useAuth } from '@/contexts/AuthContext'
import { ProtectedRoute } from './ProtectedRoute'
import { RoleRoute } from './RoleRoute'

const PublicHomePage     = lazy(() => import('@/pages/public/PublicHomePage'))
const LoginPage          = lazy(() => import('@/pages/auth/LoginPage'))
const ChangePasswordPage = lazy(() => import('@/pages/auth/ChangePasswordPage'))
const AdminDashboard     = lazy(() => import('@/pages/admin/AdminDashboard'))
const CoachDashboard     = lazy(() => import('@/pages/coach/CoachDashboard'))
const MemberDashboard    = lazy(() => import('@/pages/member/MemberDashboard'))

function DefaultRedirect() {
  const { user } = useAuth()
  if (!user) return <Navigate to="/login" replace />
  const paths = { admin: '/admin/dashboard', coach: '/entrenador/dashboard', member: '/socio/dashboard' }
  return <Navigate to={paths[user.role]} replace />
}

export function AppRouter() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Suspense fallback={null}>
          <Routes>
            <Route path="/" element={<PublicHomePage />} />

            <Route path="/login" element={<LoginPage />} />

            <Route
              path="/cambiar-contrasena"
              element={
                <ProtectedRoute skipPasswordCheck>
                  <ChangePasswordPage />
                </ProtectedRoute>
              }
            />

            <Route
              path="/admin/dashboard"
              element={
                <ProtectedRoute>
                  <RoleRoute allowedRoles={['admin']}>
                    <AdminDashboard />
                  </RoleRoute>
                </ProtectedRoute>
              }
            />

            <Route
              path="/entrenador/dashboard"
              element={
                <ProtectedRoute>
                  <RoleRoute allowedRoles={['coach']}>
                    <CoachDashboard />
                  </RoleRoute>
                </ProtectedRoute>
              }
            />

            <Route
              path="/socio/dashboard"
              element={
                <ProtectedRoute>
                  <RoleRoute allowedRoles={['member']}>
                    <MemberDashboard />
                  </RoleRoute>
                </ProtectedRoute>
              }
            />

            <Route path="*" element={<DefaultRedirect />} />
          </Routes>
        </Suspense>
      </AuthProvider>
    </BrowserRouter>
  )
}
