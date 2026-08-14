export function tourPasswordStorageKey(slug: string): string {
  return `vt-pwd-${slug}`
}

export function getTourPublicAccess(slug: string): { token: string | null; password: string } {
  const token = new URLSearchParams(window.location.search).get('token')
  const password = sessionStorage.getItem(tourPasswordStorageKey(slug)) || ''
  return { token, password }
}

export function buildTourPublicHeaders(slug: string): Record<string, string> {
  const { token, password } = getTourPublicAccess(slug)
  const headers: Record<string, string> = {}
  if (password) headers['X-Tour-Password'] = password
  if (token) headers['X-Tour-Token'] = token
  return headers
}

export function buildTourPublicParams(slug: string): Record<string, string> {
  const { token } = getTourPublicAccess(slug)
  return token ? { token } : {}
}
