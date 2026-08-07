import commPublicApi from './commPublicApi'

export interface CommConfig {
  provinces: string[]
  activity_types: Record<string, string>
  request_types: Record<string, string>
  heartbeat_interval: number
}

export const communicationApi = {
  config: () => commPublicApi.get('/communication/config'),

  health: () => commPublicApi.get('/communication/health'),

  initVisitor: (payload: Record<string, unknown>) =>
    commPublicApi.post('/communication/visitors/init', payload),

  heartbeat: (payload: Record<string, unknown>) =>
    commPublicApi.post('/communication/visitors/heartbeat', payload),

  trackEvent: (payload: Record<string, unknown>) =>
    commPublicApi.post('/communication/visitors/events', payload),

  captureLead: (payload: Record<string, unknown>) =>
    commPublicApi.post('/communication/leads', payload),

  getMessages: (conversationUuid: string, visitorToken: string) =>
    commPublicApi.get(`/communication/conversations/${conversationUuid}/messages`, {
      params: { visitor_token: visitorToken },
    }),

  sendMessage: (conversationUuid: string, visitorToken: string, body: string) =>
    commPublicApi.post(`/communication/conversations/${conversationUuid}/messages`, {
      visitor_token: visitorToken,
      body,
    }),
}
