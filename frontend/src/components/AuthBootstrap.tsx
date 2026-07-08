import { useEffect } from 'react'
import { useAuthStore } from '@/stores/auth'

export function AuthBootstrap({ children }: { children: React.ReactNode }) {
  const hydrated = useAuthStore((s) => s.hydrated)
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated)
  const refreshUser = useAuthStore((s) => s.refreshUser)

  useEffect(() => {
    if (hydrated && isAuthenticated) {
      refreshUser()
    }
  }, [hydrated, isAuthenticated, refreshUser])

  return <>{children}</>
}
