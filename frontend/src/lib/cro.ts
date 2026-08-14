import api from '@/lib/api'

const SESSION_KEY = 'posheh_cro_session'
const FIRST_TOUCH_KEY = 'posheh_first_touch'
const UTM_KEY = 'posheh_utm'

export function getCroSessionId() {
  let id = sessionStorage.getItem(SESSION_KEY)
  if (!id) {
    id = crypto.randomUUID?.() || `s_${Date.now()}`
    sessionStorage.setItem(SESSION_KEY, id)
  }
  return id
}

export function rememberFirstTouch(path: string) {
  if (!sessionStorage.getItem(FIRST_TOUCH_KEY)) {
    sessionStorage.setItem(FIRST_TOUCH_KEY, path)
  }
}

export function getFirstTouch() {
  return sessionStorage.getItem(FIRST_TOUCH_KEY) || window.location.pathname
}

export function captureUtmFromUrl() {
  const params = new URLSearchParams(window.location.search)
  const utm = {
    utm_source: params.get('utm_source') || undefined,
    utm_medium: params.get('utm_medium') || undefined,
    utm_campaign: params.get('utm_campaign') || undefined,
    utm_content: params.get('utm_content') || undefined,
    gclid: params.get('gclid') || undefined,
  }
  if (Object.values(utm).some(Boolean)) {
    sessionStorage.setItem(UTM_KEY, JSON.stringify(utm))
  }
}

export function getStoredUtm(): Record<string, string | undefined> {
  try {
    return JSON.parse(sessionStorage.getItem(UTM_KEY) || '{}')
  } catch {
    return {}
  }
}

export function trackCro(
  eventType: string,
  payload: {
    path?: string
    article_slug?: string
    cro_cta_id?: number | null
    meta?: Record<string, unknown>
  } = {},
) {
  api.post('/cro/track', {
    event_type: eventType,
    path: payload.path || window.location.pathname,
    article_slug: payload.article_slug,
    cro_cta_id: payload.cro_cta_id || undefined,
    session_id: getCroSessionId(),
    meta: payload.meta,
  }).catch(() => {})
}
