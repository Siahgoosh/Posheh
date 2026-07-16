const ROOT_DOMAIN = 'posheapp.ir'

// Hostnames that are part of the main app, not office sites.
const RESERVED = new Set(['www', 'app', 'admin', 'api', 'panel', 'mail', 'ftp', 'cdn', 'static'])

/**
 * When the app is served from an office subdomain (e.g. tehran-amlak.posheapp.ir)
 * return that subdomain. Returns null for the apex domain, www, reserved names,
 * localhost and raw IPs so the normal app renders instead.
 */
export function getOfficeSubdomain(): string | null {
  if (typeof window === 'undefined') return null

  const host = window.location.hostname.toLowerCase()

  if (host === 'localhost' || /^\d{1,3}(\.\d{1,3}){3}$/.test(host)) return null
  if (!host.endsWith(`.${ROOT_DOMAIN}`)) return null

  const sub = host.slice(0, -(`.${ROOT_DOMAIN}`.length))
  if (!sub || sub.includes('.')) return null
  if (RESERVED.has(sub)) return null

  return sub
}
