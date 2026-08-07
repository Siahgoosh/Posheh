import { communicationApi } from '../api/communicationApi'
import { createSessionKey, getUtmParams, useCommVisitorStore } from '../store/visitorStore'

let heartbeatTimer: ReturnType<typeof setInterval> | null = null
let startTime = Date.now()
let clickCount = 0
let mouseMoveCount = 0
let maxScroll = 0
let initialized = false

function scrollDepth(): number {
  const el = document.documentElement
  const scrollTop = window.scrollY || el.scrollTop
  const height = el.scrollHeight - el.clientHeight
  if (height <= 0) return 0
  return Math.min(100, Math.round((scrollTop / height) * 100))
}

export async function initCommunicationTracking(): Promise<void> {
  if (initialized || typeof window === 'undefined') return
  initialized = true

  await ensureVisitorInitialized()

  startTime = Date.now()

  document.addEventListener('click', () => {
    clickCount += 1
  }, { passive: true })

  document.addEventListener('mousemove', () => {
    mouseMoveCount += 1
  }, { passive: true })

  window.addEventListener('scroll', () => {
    maxScroll = Math.max(maxScroll, scrollDepth())
  }, { passive: true })

  const interval = (await communicationApi.config().catch(() => null))?.data?.data?.heartbeat_interval ?? 25
  heartbeatTimer = setInterval(sendHeartbeat, interval * 1000)
}

/** @returns token or throws with user-facing message */
export async function ensureVisitorInitialized(): Promise<string> {
  const store = useCommVisitorStore.getState()
  if (store.visitorToken) return store.visitorToken

  const sessionKey = store.sessionKey || createSessionKey()
  store.setSessionKey(sessionKey)

  const utm = getUtmParams()

  try {
    const res = await communicationApi.initVisitor({
      visitor_token: store.visitorToken,
      session_key: sessionKey,
      current_page: window.location.pathname,
      referrer: document.referrer || undefined,
      landing_page: sessionStorage.getItem('posheh_comm_landing') || window.location.pathname,
      language: navigator.language,
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
      screen_resolution: `${window.screen.width}x${window.screen.height}`,
      ...utm,
    })
    const token = res.data.data.visitor_token as string
    store.setVisitorToken(token)
    if (!sessionStorage.getItem('posheh_comm_landing')) {
      sessionStorage.setItem('posheh_comm_landing', window.location.pathname)
    }
    return token
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string }; status?: number } }
    console.error('[comm] visitor init failed', err?.response?.status, err?.response?.data)
    const apiMsg = err?.response?.data?.message
    if (err?.response?.status === 500 || err?.response?.status === 404) {
      throw new Error('سرویس چت هنوز روی سرور فعال نشده. لطفاً migration را اجرا کنید یا با پشتیبانی تماس بگیرید.')
    }
    throw new Error(apiMsg || 'اتصال به سرور برقرار نشد. لطفاً صفحه را رفرش کنید.')
  }
}

async function sendHeartbeat(): Promise<void> {
  const { visitorToken, sessionKey } = useCommVisitorStore.getState()
  if (!visitorToken || !sessionKey) return

  try {
    await communicationApi.heartbeat({
      visitor_token: visitorToken,
      session_key: sessionKey,
      current_page: window.location.pathname,
      time_on_site_seconds: Math.floor((Date.now() - startTime) / 1000),
      scroll_depth: maxScroll,
      click_count_delta: clickCount,
      mouse_movement_delta: mouseMoveCount,
    })
    clickCount = 0
    mouseMoveCount = 0
  } catch {
    // ignore
  }
}

export function trackCommEvent(eventType: string, path?: string): void {
  const { visitorToken, sessionKey } = useCommVisitorStore.getState()
  if (!visitorToken || !sessionKey) return
  communicationApi.trackEvent({
    visitor_token: visitorToken,
    session_key: sessionKey,
    event_type: eventType,
    path: path ?? window.location.pathname,
  }).catch(() => {})
}

export function stopCommunicationTracking(): void {
  if (heartbeatTimer) clearInterval(heartbeatTimer)
  heartbeatTimer = null
}
