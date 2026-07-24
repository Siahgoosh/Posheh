import { isPanelSubdomain } from './subdomain'

/** Base path for admin routes: '' on panel.posheapp.ir, '/admin' on main site */
export function adminBase(): string {
  return isPanelSubdomain() ? '' : '/admin'
}

export function adminPath(segment: string): string {
  const base = adminBase()
  if (!segment || segment === '/') return base || '/'
  return `${base}/${segment.replace(/^\//, '')}`
}
