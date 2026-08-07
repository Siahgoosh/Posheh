import { useCallback, useEffect, useRef } from 'react'
import api from '@/lib/api'

const SESSION_KEY = 'vt-session-id'

function getOrCreateSessionId(): string {
  let id = sessionStorage.getItem(SESSION_KEY)
  if (!id) {
    id = crypto.randomUUID()
    sessionStorage.setItem(SESSION_KEY, id)
  }
  return id
}

function detectDevice(): string {
  const ua = navigator.userAgent.toLowerCase()
  if (/mobile|android|iphone/.test(ua)) return 'mobile'
  if (/tablet|ipad/.test(ua)) return 'tablet'
  if (/tv|smart-tv/.test(ua)) return 'tv'
  return 'desktop'
}

export interface TourAnalyticsEvent {
  event_type: string
  scene_id?: number
  hotspot_id?: number | string
  position_x?: number
  position_y?: number
  meta?: Record<string, unknown>
}

export function useTourSessionAnalytics(slug: string | undefined, enabled = true) {
  const queueRef = useRef<TourAnalyticsEvent[]>([])
  const visitedScenesRef = useRef<Set<number>>(new Set())
  const startTimeRef = useRef(Date.now())
  const sessionId = getOrCreateSessionId()

  const flush = useCallback(async () => {
    if (!slug || queueRef.current.length === 0) return
    const events = [...queueRef.current]
    queueRef.current = []
    const duration = Math.round((Date.now() - startTimeRef.current) / 1000)
    try {
      await api.post(`/tour/${slug}/events`, {
        session_id: sessionId,
        events,
        duration_seconds: duration,
      })
    } catch {
      queueRef.current.unshift(...events)
    }
  }, [slug, sessionId])

  const track = useCallback((event: TourAnalyticsEvent) => {
    if (!enabled || !slug) return
    queueRef.current.push(event)
    if (queueRef.current.length >= 10) flush()
  }, [enabled, slug, flush])

  const trackSceneView = useCallback((sceneId: number) => {
    if (visitedScenesRef.current.has(sceneId)) return
    visitedScenesRef.current.add(sceneId)
    track({ event_type: 'scene_view', scene_id: sceneId })
  }, [track])

  const trackHotspotClick = useCallback((hotspotId: number | string, sceneId: number, x?: number, y?: number) => {
    track({
      event_type: 'hotspot_click',
      scene_id: sceneId,
      hotspot_id: typeof hotspotId === 'number' ? hotspotId : undefined,
      position_x: x,
      position_y: y,
      meta: typeof hotspotId === 'string' ? { temp_id: hotspotId } : undefined,
    })
  }, [track])

  const trackTourComplete = useCallback((totalScenes: number) => {
    if (visitedScenesRef.current.size >= totalScenes && totalScenes > 0) {
      track({ event_type: 'tour_complete', meta: { scenes_visited: visitedScenesRef.current.size } })
    }
  }, [track])

  useEffect(() => {
    if (!enabled || !slug) return
    track({ event_type: 'session_start', meta: {
      device_type: detectDevice(),
      screen_width: window.innerWidth,
      screen_height: window.innerHeight,
    } })
    const interval = window.setInterval(flush, 15000)
    const onHide = () => {
      track({ event_type: 'session_end' })
      flush()
    }
    window.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') onHide()
    })
    return () => {
      window.clearInterval(interval)
      onHide()
    }
  }, [enabled, slug, track, flush])

  return {
    sessionId,
    track,
    trackSceneView,
    trackHotspotClick,
    trackTourComplete,
    flush,
  }
}

export function getTourSessionQueryParams() {
  return {
    session_id: getOrCreateSessionId(),
    device_type: detectDevice(),
    screen_width: window.innerWidth,
    screen_height: window.innerHeight,
  }
}
