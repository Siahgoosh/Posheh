import api from '@/lib/api'
import type { SceneStatus, TourData, TourScene } from '../types'

export const tourApi = {
  list: () => api.get('/virtual-tours'),
  get: (id: number | string) => api.get(`/virtual-tours/${id}`),
  create: (data: { title: string; description?: string; property_id?: number }) =>
    api.post('/virtual-tours', data),
  update: (id: number | string, data: Partial<{ title: string; description: string; status: SceneStatus; settings: Record<string, unknown> }>) =>
    api.put(`/virtual-tours/${id}`, data),

  addScene: (tourId: number | string, data: FormData | Record<string, unknown>) =>
  api.post(`/virtual-tours/${tourId}/scenes`, data, data instanceof FormData ? { headers: { 'Content-Type': 'multipart/form-data' } } : undefined),

  updateScene: (tourId: number | string, sceneId: number, data: FormData | Record<string, unknown>) =>
    api.put(`/virtual-tours/${tourId}/scenes/${sceneId}`, data, data instanceof FormData ? { headers: { 'Content-Type': 'multipart/form-data' } } : undefined),

  deleteScene: (tourId: number | string, sceneId: number) =>
    api.delete(`/virtual-tours/${tourId}/scenes/${sceneId}`),

  duplicateScene: (tourId: number | string, sceneId: number) =>
    api.post(`/virtual-tours/${tourId}/scenes/${sceneId}/duplicate`),

  reorderScenes: (tourId: number | string, sceneIds: number[]) =>
    api.put(`/virtual-tours/${tourId}/scenes/reorder`, { scene_ids: sceneIds }),

  publishScene: (tourId: number | string, sceneId: number) =>
    api.post(`/virtual-tours/${tourId}/scenes/${sceneId}/publish`),

  unpublishScene: (tourId: number | string, sceneId: number) =>
    api.post(`/virtual-tours/${tourId}/scenes/${sceneId}/unpublish`),

  setDefaultScene: (tourId: number | string, sceneId: number) =>
    api.post(`/virtual-tours/${tourId}/scenes/${sceneId}/default`),

  uploadPanorama: (
    tourId: number | string,
    file: File,
    options?: { name?: string; sceneId?: number; onProgress?: (pct: number) => void; signal?: AbortSignal },
  ) => {
    const form = new FormData()
    form.append('panorama', file)
    if (options?.name) form.append('name', options.name)
    if (options?.sceneId) form.append('scene_id', String(options.sceneId))

    return api.post(`/virtual-tours/${tourId}/scenes/upload`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 300000,
      signal: options?.signal,
      onUploadProgress: (e) => {
        if (e.total && options?.onProgress) {
          options.onProgress(Math.round((e.loaded / e.total) * 100))
        }
      },
    })
  },
}

export function parseTourResponse(data: unknown): TourData {
  return data as TourData
}

export function parseSceneResponse(data: unknown): TourScene {
  return data as TourScene
}
