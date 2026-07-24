import { Navigate, useLocation } from 'react-router-dom'
import { useAuthStore } from '@/stores/auth'
import { isPlatformStaffRole } from '@/lib/subdomain'

const RENEWAL_ALLOWED = ['/renew', '/subscription', '/settings', '/admin']

export function SubscriptionGuard({ children }: { children: React.ReactNode }) {
  const { user } = useAuthStore()
  const location = useLocation()

  if (isPlatformStaffRole(user?.role)) {
    return <>{children}</>
  }

  const expired = user?.office?.subscription_expired === true
    || user?.office?.has_access === false

  if (expired && !RENEWAL_ALLOWED.some((p) => location.pathname.startsWith(p))) {
    return <Navigate to="/renew" replace />
  }

  return <>{children}</>
}

export function usePlanFeature(feature: string): boolean {
  const { user } = useAuthStore()
  if (isPlatformStaffRole(user?.role)) return true
  const features = user?.office?.plan?.features ?? []
  return features.includes(feature)
}
