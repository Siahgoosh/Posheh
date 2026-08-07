import api from '@/lib/api'

export interface CommConfig {
  provinces: string[]
  activity_types: Record<string, string>
  request_types: Record<string, string>
  heartbeat_interval: number
}

export const communicationApi = {
  config: () => api.get('/communication/config'),

  initVisitor: (payload: Record<string, unknown>) =>
    api.post('/communication/visitors/init', payload),

  heartbeat: (payload: Record<string, unknown>) =>
    api.post('/communication/visitors/heartbeat', payload),

  trackEvent: (payload: Record<string, unknown>) =>
    api.post('/communication/visitors/events', payload),

  captureLead: (payload: Record<string, unknown>) =>
    api.post('/communication/leads', payload),

  getMessages: (conversationUuid: string, visitorToken: string) =>
    api.get(`/communication/conversations/${conversationUuid}/messages`, {
      params: { visitor_token: visitorToken },
    }),

  sendMessage: (conversationUuid: string, visitorToken: string, body: string) =>
    api.post(`/communication/conversations/${conversationUuid}/messages`, {
      visitor_token: visitorToken,
      body,
    }),
}
