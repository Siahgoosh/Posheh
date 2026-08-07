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

  const sessionKey = createSessionKey()
  useCommVisitorStore.getState().setSessionKey(sessionKey)
  startTime = Date.now()

  const utm = getUtmParams()
  const store = useCommVisitorStore.getState()

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
  } catch {
    // silent — widget still works
  }

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
