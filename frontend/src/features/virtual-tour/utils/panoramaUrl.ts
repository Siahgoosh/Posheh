/** Normalize panorama URLs for same-origin loading (fixes APP_URL=localhost on production). */
export function resolvePanoramaUrl(url: string | null | undefined): string {
  if (!url) return ''
  if (url.startsWith('/')) return url
  if (url.startsWith('demo/')) return `/demo/${url.split('/').pop()}`
  try {
    const parsed = new URL(url, window.location.origin)
    if (parsed.pathname.startsWith('/storage/') || parsed.pathname.startsWith('/demo/')) {
      return parsed.pathname
    }
    return url
  } catch {
    return url
  }
}

export function preloadPanorama(url: string, timeoutMs = 60000): Promise<void> {
  return new Promise((resolve, reject) => {
    const src = resolvePanoramaUrl(url)
    if (!src) {
      reject(new Error('empty panorama url'))
      return
    }
    const img = new Image()
    const timer = window.setTimeout(() => {
      img.src = ''
      reject(new Error('timeout'))
    }, timeoutMs)
    img.onload = () => {
      window.clearTimeout(timer)
      resolve()
    }
    img.onerror = () => {
      window.clearTimeout(timer)
      reject(new Error(`failed: ${src}`))
    }
    img.src = src
  })
}
