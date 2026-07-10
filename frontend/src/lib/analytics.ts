import api from '@/lib/api'

export function trackPageView(path: string) {
  const key = `pv:${path}:${new Date().toISOString().slice(0, 10)}`
  if (sessionStorage.getItem(key)) return
  sessionStorage.setItem(key, '1')

  api.post('/analytics/track', {
    event_type: 'page_view',
    path,
    referrer: document.referrer || null,
  }).catch(() => {})
}

export function trackDownloadClick(platform: string, version?: string) {
  api.post('/analytics/track', {
    event_type: 'download_click',
    path: '/download',
    meta: { platform, version },
  }).catch(() => {})
}
