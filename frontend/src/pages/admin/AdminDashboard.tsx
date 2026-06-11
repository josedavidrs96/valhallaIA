import { Navigate } from 'react-router-dom'

// Legacy dashboard — redirect to new socios page
export default function AdminDashboard() {
  return <Navigate to="/admin/socios" replace />
}
