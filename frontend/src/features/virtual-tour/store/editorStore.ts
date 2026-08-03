import { create } from 'zustand'
import type { SceneFilter, SceneSort, TourScene, UploadTask } from '../types'

interface EditorState {
  activeSceneId: number | null
  search: string
  filter: SceneFilter
  sort: SceneSort
  uploadTasks: UploadTask[]
  isPanelCollapsed: boolean

  setActiveSceneId: (id: number | null) => void
  setSearch: (search: string) => void
  setFilter: (filter: SceneFilter) => void
  setSort: (sort: SceneSort) => void
  setPanelCollapsed: (collapsed: boolean) => void
  addUploadTask: (task: UploadTask) => void
  updateUploadTask: (id: string, patch: Partial<UploadTask>) => void
  removeUploadTask: (id: string) => void
  clearCompletedUploads: () => void
}

export const useTourEditorStore = create<EditorState>((set) => ({
  activeSceneId: null,
  search: '',
  filter: 'all',
  sort: 'order',
  uploadTasks: [],
  isPanelCollapsed: false,

  setActiveSceneId: (id) => set({ activeSceneId: id }),
  setSearch: (search) => set({ search }),
  setFilter: (filter) => set({ filter }),
  setSort: (sort) => set({ sort }),
  setPanelCollapsed: (collapsed) => set({ isPanelCollapsed: collapsed }),
  addUploadTask: (task) => set((s) => ({ uploadTasks: [...s.uploadTasks, task] })),
  updateUploadTask: (id, patch) =>
    set((s) => ({
      uploadTasks: s.uploadTasks.map((t) => (t.id === id ? { ...t, ...patch } : t)),
    })),
  removeUploadTask: (id) =>
    set((s) => ({ uploadTasks: s.uploadTasks.filter((t) => t.id !== id) })),
  clearCompletedUploads: () =>
    set((s) => ({
      uploadTasks: s.uploadTasks.filter((t) => t.status === 'uploading' || t.status === 'pending'),
    })),
}))

export function filterAndSortScenes(
  scenes: TourScene[],
  search: string,
  filter: SceneFilter,
  sort: SceneSort,
): TourScene[] {
  let result = [...scenes]

  if (search.trim()) {
    const q = search.trim().toLowerCase()
    result = result.filter((s) => s.name.toLowerCase().includes(q))
  }

  switch (filter) {
    case 'draft':
      result = result.filter((s) => s.status === 'draft')
      break
    case 'published':
      result = result.filter((s) => s.status === 'published')
      break
    case 'visible':
      result = result.filter((s) => s.is_visible)
      break
    case 'hidden':
      result = result.filter((s) => !s.is_visible)
      break
  }

  switch (sort) {
    case 'name':
      result.sort((a, b) => a.name.localeCompare(b.name, 'fa'))
      break
    case 'status':
      result.sort((a, b) => a.status.localeCompare(b.status))
      break
    default:
      result.sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
  }

  return result
}
