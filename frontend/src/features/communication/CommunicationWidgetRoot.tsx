import { useEffect } from 'react'
import { FloatingChatWidget } from './widget/FloatingChatWidget'
import { initCommunicationTracking } from './tracking/visitorTracker'
import { getOfficeSubdomain } from '@/lib/subdomain'

/** Public-site only — not panel or office tenant subdomains */
export function CommunicationWidgetRoot() {
  const officeSub = getOfficeSubdomain()
  const isPanel = typeof window !== 'undefined' && window.location.hostname.startsWith('panel.')

  useEffect(() => {
    if (officeSub || isPanel) return
    initCommunicationTracking()
  }, [officeSub, isPanel])

  if (officeSub || isPanel) return null

  return <FloatingChatWidget />
}
