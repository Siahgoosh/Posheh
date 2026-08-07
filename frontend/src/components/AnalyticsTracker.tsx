import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'
import { trackPageView } from '@/lib/analytics'

const PUBLIC_PREFIXES = [
  '/',
  '/blog',
  '/download',
  '/login',
  '/register',
  '/contact',
  '/r/',
  '/tour/',
  '/o/',
  '/site/',
]

export function AnalyticsTracker() {
  const location = useLocation()

  useEffect(() => {
    const path = location.pathname
    const isPublic = PUBLIC_PREFIXES.some((p) => {
      if (p === '/') return path === '/'
      return path.startsWith(p)
    })
    if (isPublic) trackPageView(path)
  }, [location.pathname])

  return null
}
