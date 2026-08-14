import api from '@/lib/api'
import { buildTourPublicHeaders, buildTourPublicParams } from '../utils/tourPublicAccess'
import type { SceneStatus, TourData, TourScene, TourType } from '../types'

export interface TourDashboardStats {
  total: number
  published: number
  draft: number
  archived: number
  total_views: number
  total_leads: number
  recent: TourListItem[]
}

export interface TourListItem extends Partial<TourData> {
  id: number
  title: string
  slug: string
  tour_type?: TourType
  status: SceneStatus | 'archived'
  view_count: number
  leads_count?: number
  scenes_count?: number
  visibility?: 'public' | 'private'
  version?: number
  expires_at?: string | null
  has_password?: boolean
  public_url?: string
  private_url?: string
  embed_url?: string
  share_token?: string
}

export const tourApi = {
  dashboard: () => api.get('/virtual-tours/dashboard'),
  list: (params?: { status?: string; search?: string }) =>
    api.get('/virtual-tours', { params }),

  get: (id: number | string) => api.get(`/virtual-tours/${id}`),
  create: (data: { title: string; description?: string; property_id?: number; tour_type?: TourType }) =>
    api.post('/virtual-tours', data),
  update: (id: number | string, data: Partial<{ title: string; description: string; status: string; property_id: number | null; settings: Record<string, unknown> }>) =>
    api.put(`/virtual-tours/${id}`, data),
  delete: (id: number | string) => api.delete(`/virtual-tours/${id}`),

  duplicate: (id: number | string) => api.post(`/virtual-tours/${id}/duplicate`),
  publish: (id: number | string) => api.post(`/virtual-tours/${id}/publish`),
  unpublish: (id: number | string) => api.post(`/virtual-tours/${id}/unpublish`),
  archive: (id: number | string) => api.post(`/virtual-tours/${id}/archive`),
  unarchive: (id: number | string) => api.post(`/virtual-tours/${id}/unarchive`),

  updateSharing: (id: number | string, data: Record<string, unknown>) =>
    api.put(`/virtual-tours/${id}/sharing`, data),

  exportJson: (id: number | string) => api.get(`/virtual-tours/${id}/export/json`),
  exportZip: (id: number | string) =>
    api.get(`/virtual-tours/${id}/export/zip`, { responseType: 'blob' }),
  importTour: (file: File) => {
    const form = new FormData()
    form.append('file', file)
    return api.post('/virtual-tours/import', form)
  },
  importJson: (tour: Record<string, unknown>) => api.post('/virtual-tours/import', { tour }),

  backup: (id: number | string, label?: string) =>
    api.post(`/virtual-tours/${id}/backup`, { label }),
  versions: (id: number | string) => api.get(`/virtual-tours/${id}/versions`),
  restoreVersion: (id: number | string, versionId: number) =>
    api.post(`/virtual-tours/${id}/versions/${versionId}/restore`),
  activity: (id: number | string) => api.get(`/virtual-tours/${id}/activity`),
  analytics: (id: number | string) => api.get(`/virtual-tours/${id}/analytics`),

  addScene: (tourId: number | string, data: FormData | Record<string, unknown>) =>
    api.post(`/virtual-tours/${tourId}/scenes`, data),

  updateScene: (tourId: number | string, sceneId: number, data: FormData | Record<string, unknown>) =>
    api.put(`/virtual-tours/${tourId}/scenes/${sceneId}`, data),

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

  syncHotspots: (tourId: number | string, sceneId: number, hotspots: unknown[]) =>
    api.put(`/virtual-tours/${tourId}/scenes/${sceneId}/hotspots`, { hotspots }),

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
      timeout: 300000,
      signal: options?.signal,
      onUploadProgress: (e) => {
        if (e.total && options?.onProgress) {
          options.onProgress(Math.round((e.loaded / e.total) * 100))
        }
      },
    })
  },

  uploadSceneImage: (
    tourId: number | string,
    file: File,
    options?: { name?: string; sceneId?: number; onProgress?: (pct: number) => void; signal?: AbortSignal },
  ) => {
    const form = new FormData()
    form.append('image', file)
    if (options?.name) form.append('name', options.name)
    if (options?.sceneId) form.append('scene_id', String(options.sceneId))

    return api.post(`/virtual-tours/${tourId}/scenes/upload-image`, form, {
      timeout: 300000,
      signal: options?.signal,
      onUploadProgress: (e) => {
        if (e.total && options?.onProgress) {
          options.onProgress(Math.round((e.loaded / e.total) * 100))
        }
      },
    })
  },

  verifyPublicPassword: (slug: string, password: string) =>
    api.post(`/tour/${slug}/verify-password`, { password }),

  submitPublicLead: (
    slug: string,
    data: { name: string; mobile: string; message?: string },
  ) =>
    api.post(`/tour/${slug}/lead`, data, {
      headers: buildTourPublicHeaders(slug),
      params: buildTourPublicParams(slug),
    }),
}

export function parseTourResponse(data: unknown): TourData {
  return data as TourData
}

export function parseSceneResponse(data: unknown): TourScene {
  return data as TourScene
}

export function downloadBlob(blob: Blob, filename: string) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}
