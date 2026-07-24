import { Navigate } from 'react-router-dom'
import { useAuthStore } from '@/stores/auth'
import { isPlatformStaffRole } from '@/lib/subdomain'

export function SuperAdminRoute({ children }: { children: React.ReactNode }) {
  const { user, isAuthenticated, hydrated } = useAuthStore()

  if (!hydrated) {
    return (
      <div className="flex min-h-[40vh] items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  if (!isAuthenticated) return <Navigate to="/login" replace />
  if (!isPlatformStaffRole(user?.role)) return <Navigate to="/dashboard" replace />

  return <>{children}</>
}
