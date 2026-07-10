import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'
import { trackPageView } from '@/lib/analytics'

const PUBLIC_PREFIXES = ['/', '/blog', '/download', '/login']

export function AnalyticsTracker() {
  const location = useLocation()

  useEffect(() => {
    const path = location.pathname
    const isPublic = PUBLIC_PREFIXES.some((p) => (p === '/' ? path === '/' : path.startsWith(p)))
    if (isPublic) trackPageView(path)
  }, [location.pathname])

  return null
}
