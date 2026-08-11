import { useEffect } from 'react'
import { useAuthStore } from '@/stores/auth'
import api from '@/lib/api'

/**
 * Hydrates auth after persist rehydrate.
 * Also accepts one-time impersonation tokens from admin panel (?impersonation_token=...).
 */
export function AuthBootstrap({ children }: { children: React.ReactNode }) {
  const hydrated = useAuthStore((s) => s.hydrated)
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated)
  const refreshUser = useAuthStore((s) => s.refreshUser)
  const setAuth = useAuthStore((s) => s.setAuth)

  useEffect(() => {
    if (!hydrated) return

    const params = new URLSearchParams(window.location.search)
    const impersonationToken = params.get('impersonation_token') || params.get('token')
    if (impersonationToken) {
      localStorage.setItem('token', impersonationToken)
      api.get('/auth/me')
        .then(({ data }) => {
          setAuth(data.user, impersonationToken)
          params.delete('impersonation_token')
          params.delete('token')
          params.delete('impersonation_session')
          const qs = params.toString()
          const clean = `${window.location.pathname}${qs ? `?${qs}` : ''}`
          window.history.replaceState({}, '', clean)
        })
        .catch(() => {
          localStorage.removeItem('token')
        })
      return
    }

    if (isAuthenticated) {
      refreshUser()
    }
  }, [hydrated, isAuthenticated, refreshUser, setAuth])

  return <>{children}</>
}
