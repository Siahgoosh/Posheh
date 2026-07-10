import { Navigate, useLocation } from 'react-router-dom'
import { useAuthStore } from '@/stores/auth'

const RENEWAL_ALLOWED = ['/renew', '/subscription', '/settings']

export function SubscriptionGuard({ children }: { children: React.ReactNode }) {
  const { user } = useAuthStore()
  const location = useLocation()

  if (user?.role === 'super_admin') {
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
  if (user?.role === 'super_admin') return true
  const features = user?.office?.plan?.features ?? []
  return features.includes(feature)
}
