import { getOfficeSubdomain, isPanelSubdomain } from '@/lib/subdomain'

/** Paths on the main marketing app where the floating chat widget is shown. */
const PUBLIC_CHAT_PATH_PREFIXES = [
  '/blog',
  '/r/',
  '/contact',
  '/download',
  '/terms',
  '/privacy',
  '/login',
  '/register',
  '/forgot-password',
  '/reset-password',
  '/tour/',
  '/embed/tour/',
  '/p/',
  '/o/',
  '/site/',
]

export function isCommunicationWidgetPublicPath(pathname: string): boolean {
  if (pathname === '/') return true
  return PUBLIC_CHAT_PATH_PREFIXES.some(
    (prefix) => pathname === prefix.replace(/\/$/, '') || pathname.startsWith(prefix),
  )
}

export function shouldHideCommunicationWidget(pathname: string): boolean {
  if (getOfficeSubdomain()) return true
  if (isPanelSubdomain()) return true
  if (pathname.startsWith('/admin')) return true
  return !isCommunicationWidgetPublicPath(pathname)
}
