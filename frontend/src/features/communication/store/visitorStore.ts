import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface VisitorState {
  visitorToken: string | null
  sessionKey: string | null
  conversationUuid: string | null
  leadScore: number
  setVisitorToken: (token: string | null) => void
  setSessionKey: (key: string) => void
  setConversationUuid: (uuid: string) => void
  setLeadScore: (score: number) => void
}

export const useCommVisitorStore = create<VisitorState>()(
  persist(
    (set) => ({
      visitorToken: null,
      sessionKey: null,
      conversationUuid: null,
      leadScore: 0,
      setVisitorToken: (token) => set({ visitorToken: token }),
      setSessionKey: (key) => set({ sessionKey: key }),
      setConversationUuid: (uuid) => set({ conversationUuid: uuid }),
      setLeadScore: (score) => set({ leadScore: score }),
    }),
    { name: 'posheh-comm-visitor' },
  ),
)

export function createSessionKey(): string {
  const stored = sessionStorage.getItem('posheh_comm_session')
  if (stored) return stored
  const key = `${Date.now()}-${Math.random().toString(36).slice(2, 12)}`
  sessionStorage.setItem('posheh_comm_session', key)
  return key
}

export function getUtmParams(): Record<string, string | undefined> {
  const params = new URLSearchParams(window.location.search)
  return {
    utm_source: params.get('utm_source') || undefined,
    utm_campaign: params.get('utm_campaign') || undefined,
    utm_medium: params.get('utm_medium') || undefined,
    utm_term: params.get('utm_term') || undefined,
    utm_content: params.get('utm_content') || undefined,
  }
}
