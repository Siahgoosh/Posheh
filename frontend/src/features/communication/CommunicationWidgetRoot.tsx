import { useEffect } from 'react'
import { FloatingChatWidget } from './widget/FloatingChatWidget'
import { initCommunicationTracking } from './tracking/visitorTracker'
import { getOfficeSubdomain, isPanelSubdomain } from '@/lib/subdomain'
import { useAuthStore } from '@/stores/auth'

/** Public marketing site only — hidden on panel, office sites, and logged-in users */
export function CommunicationWidgetRoot() {
  const officeSub = getOfficeSubdomain()
  const isPanel = isPanelSubdomain()
  const onAdminPath = typeof window !== 'undefined' && window.location.pathname.startsWith('/admin')
  const { isAuthenticated, hydrated } = useAuthStore()

  const hideWidget = officeSub || isPanel || onAdminPath || (hydrated && isAuthenticated)

  useEffect(() => {
    if (hideWidget) return
    initCommunicationTracking()
  }, [hideWidget])

  if (hideWidget) return null

  return <FloatingChatWidget />
}
