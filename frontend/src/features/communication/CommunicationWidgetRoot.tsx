import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'
import { FloatingChatWidget } from './widget/FloatingChatWidget'
import { initCommunicationTracking } from './tracking/visitorTracker'
import { shouldHideCommunicationWidget } from './communicationWidgetVisibility'

/**
 * Floating chat on public marketing pages (landing, blog, contact, public tours).
 * Hidden on panel subdomain, office sites, admin routes, and inside the tenant panel app (/dashboard, …).
 */
export function CommunicationWidgetRoot() {
  const { pathname } = useLocation()
  const hideWidget = shouldHideCommunicationWidget(pathname)

  useEffect(() => {
    if (hideWidget) return
    initCommunicationTracking()
  }, [hideWidget])

  if (hideWidget) return null

  return <FloatingChatWidget />
}
