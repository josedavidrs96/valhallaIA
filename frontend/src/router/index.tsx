import { lazy, Suspense } from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider, useAuth } from '@/contexts/AuthContext'
import { ProtectedRoute } from './ProtectedRoute'
import { RoleRoute } from './RoleRoute'

// Public / Auth pages
const PublicHomePage     = lazy(() => import('@/pages/public/PublicHomePage'))
const LoginPage          = lazy(() => import('@/pages/auth/LoginPage'))
const ChangePasswordPage = lazy(() => import('@/pages/auth/ChangePasswordPage'))

// Admin pages
const AdminLayout          = lazy(() => import('@/components/admin/AdminLayout'))
const AdminMembersPage     = lazy(() => import('@/pages/admin/AdminMembersPage'))
const AdminMemberDetailPage = lazy(() => import('@/pages/admin/AdminMemberDetailPage'))
const AdminClassesPage     = lazy(() => import('@/pages/admin/AdminClassesPage'))
const AdminPaymentsPage    = lazy(() => import('@/pages/admin/AdminPaymentsPage'))
const AdminOverduePage     = lazy(() => import('@/pages/admin/AdminOverduePage'))

// Member layout + pages
const MemberLayout        = lazy(() => import('@/components/member/MemberLayout'))
const MemberHomePage      = lazy(() => import('@/pages/member/MemberHomePage'))
const MemberSchedulePage  = lazy(() => import('@/pages/member/MemberSchedulePage'))
const MemberBookingsPage  = lazy(() => import('@/pages/member/MemberBookingsPage'))
const MemberPaymentsPage  = lazy(() => import('@/pages/member/MemberPaymentsPage'))

// Coach layout + pages
const CoachLayout        = lazy(() => import('@/components/coach/CoachLayout'))
const CoachSessionsPage  = lazy(() => import('@/pages/coach/CoachSessionsPage'))
const CoachRosterPage    = lazy(() => import('@/pages/coach/CoachRosterPage'))

function DefaultRedirect() {
  const { user } = useAuth()
  if (!user) return <Navigate to="/login" replace />
  const paths = { admin: '/admin/socios', coach: '/entrenador', member: '/socio' }
  return <Navigate to={paths[user.role]} replace />
}

function AdminGuard({ children }: { children: React.ReactNode }) {
  return (
    <ProtectedRoute>
      <RoleRoute allowedRoles={['admin']}>
        {children}
      </RoleRoute>
    </ProtectedRoute>
  )
}

export function AppRouter() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Suspense fallback={null}>
          <Routes>
            {/* Public */}
            <Route path="/" element={<PublicHomePage />} />
            <Route path="/login" element={<LoginPage />} />

            {/* Change password */}
            <Route
              path="/cambiar-contrasena"
              element={
                <ProtectedRoute skipPasswordCheck>
                  <ChangePasswordPage />
                </ProtectedRoute>
              }
            />

            {/* Admin — nested layout */}
            <Route
              path="/admin"
              element={
                <AdminGuard>
                  <AdminLayout />
                </AdminGuard>
              }
            >
              <Route index element={<Navigate to="socios" replace />} />
              <Route path="socios"          element={<AdminMembersPage />} />
              <Route path="socios/:id"      element={<AdminMemberDetailPage />} />
              <Route path="clases"          element={<AdminClassesPage />} />
              <Route path="pagos"           element={<AdminPaymentsPage />} />
              <Route path="pagos/morosos"   element={<AdminOverduePage />} />
              {/* Legacy dashboard redirect */}
              <Route path="dashboard"       element={<Navigate to="/admin/socios" replace />} />
            </Route>

            {/* Member — nested layout */}
            <Route
              path="/socio"
              element={
                <ProtectedRoute>
                  <RoleRoute allowedRoles={['member']}>
                    <MemberLayout />
                  </RoleRoute>
                </ProtectedRoute>
              }
            >
              <Route index element={<Navigate to="inicio" replace />} />
              <Route path="inicio"   element={<MemberHomePage />} />
              <Route path="horario"  element={<MemberSchedulePage />} />
              <Route path="reservas" element={<MemberBookingsPage />} />
              <Route path="pagos"    element={<MemberPaymentsPage />} />
              {/* Legacy dashboard redirect */}
              <Route path="dashboard" element={<Navigate to="/socio/inicio" replace />} />
            </Route>

            {/* Coach — nested layout */}
            <Route
              path="/entrenador"
              element={
                <ProtectedRoute>
                  <RoleRoute allowedRoles={['coach']}>
                    <CoachLayout />
                  </RoleRoute>
                </ProtectedRoute>
              }
            >
              <Route index element={<Navigate to="clases" replace />} />
              <Route path="clases"             element={<CoachSessionsPage />} />
              <Route path="clases/:id/lista"   element={<CoachRosterPage />} />
              {/* Legacy dashboard redirect */}
              <Route path="dashboard" element={<Navigate to="/entrenador/clases" replace />} />
            </Route>

            <Route path="*" element={<DefaultRedirect />} />
          </Routes>
        </Suspense>
      </AuthProvider>
    </BrowserRouter>
  )
}
