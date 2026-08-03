import { create } from 'zustand'
import type { EditorTab, SceneFilter, SceneSort, TourHotspot, TourScene, TourSettings, UploadTask } from '../types'

interface EditorState {
  activeSceneId: number | null
  activeTab: EditorTab
  search: string
  filter: SceneFilter
  sort: SceneSort
  uploadTasks: UploadTask[]
  isPanelCollapsed: boolean
  selectedHotspotId: number | string | null
  isPlacingHotspot: boolean
  isLinkingScenes: boolean
  isRepositioningHotspot: boolean
  localHotspots: Record<number, TourHotspot[]>
  localScenePatches: Record<number, Partial<TourScene>>
  localTourSettings: Partial<TourSettings> | null

  setActiveSceneId: (id: number | null) => void
  setActiveTab: (tab: EditorTab) => void
  setSearch: (search: string) => void
  setFilter: (filter: SceneFilter) => void
  setSort: (sort: SceneSort) => void
  setPanelCollapsed: (collapsed: boolean) => void
  setSelectedHotspotId: (id: number | string | null) => void
  setIsPlacingHotspot: (v: boolean) => void
  setIsLinkingScenes: (v: boolean) => void
  setIsRepositioningHotspot: (v: boolean) => void
  initHotspots: (scenes: TourScene[]) => void
  setSceneHotspots: (sceneId: number, hotspots: TourHotspot[]) => void
  updateHotspot: (sceneId: number, hotspot: TourHotspot) => void
  addHotspot: (sceneId: number, hotspot: TourHotspot) => void
  removeHotspot: (sceneId: number, hotspotId: number | string) => void
  patchScene: (sceneId: number, patch: Partial<TourScene>) => void
  setLocalTourSettings: (settings: Partial<TourSettings>) => void
  addUploadTask: (task: UploadTask) => void
  updateUploadTask: (id: string, patch: Partial<UploadTask>) => void
  removeUploadTask: (id: string) => void
  clearCompletedUploads: () => void
}

export const useTourEditorStore = create<EditorState>((set) => ({
  activeSceneId: null,
  activeTab: 'scenes',
  search: '',
  filter: 'all',
  sort: 'order',
  uploadTasks: [],
  isPanelCollapsed: false,
  selectedHotspotId: null,
  isPlacingHotspot: true,
  isLinkingScenes: true,
  isRepositioningHotspot: false,
  localHotspots: {},
  localScenePatches: {},
  localTourSettings: null,

  setActiveSceneId: (id) => set({ activeSceneId: id, selectedHotspotId: null }),
  setActiveTab: (tab) => set({ activeTab: tab }),
  setSearch: (search) => set({ search }),
  setFilter: (filter) => set({ filter }),
  setSort: (sort) => set({ sort }),
  setPanelCollapsed: (collapsed) => set({ isPanelCollapsed: collapsed }),
  setSelectedHotspotId: (id) => set({ selectedHotspotId: id }),
  setIsPlacingHotspot: (v) => set((s) => ({ isPlacingHotspot: v, isRepositioningHotspot: v ? false : s.isRepositioningHotspot })),
  setIsLinkingScenes: (v) => set({ isLinkingScenes: v, isPlacingHotspot: v }),
  setIsRepositioningHotspot: (v) => set({ isRepositioningHotspot: v, isPlacingHotspot: false, isLinkingScenes: false }),
  initHotspots: (scenes) => {
    const map: Record<number, TourHotspot[]> = {}
    scenes.forEach((s) => { map[s.id] = [...s.hotspots] })
    set({ localHotspots: map })
  },
  setSceneHotspots: (sceneId, hotspots) =>
    set((s) => ({ localHotspots: { ...s.localHotspots, [sceneId]: hotspots } })),
  updateHotspot: (sceneId, hotspot) =>
    set((s) => ({
      localHotspots: {
        ...s.localHotspots,
        [sceneId]: (s.localHotspots[sceneId] || []).map((h) => (h.id === hotspot.id ? hotspot : h)),
      },
    })),
  addHotspot: (sceneId, hotspot) =>
    set((s) => ({
      localHotspots: {
        ...s.localHotspots,
        [sceneId]: [...(s.localHotspots[sceneId] || []), hotspot],
      },
      selectedHotspotId: hotspot.id,
      isPlacingHotspot: false,
    })),
  removeHotspot: (sceneId, hotspotId) =>
    set((s) => ({
      localHotspots: {
        ...s.localHotspots,
        [sceneId]: (s.localHotspots[sceneId] || []).filter((h) => h.id !== hotspotId),
      },
      selectedHotspotId: s.selectedHotspotId === hotspotId ? null : s.selectedHotspotId,
    })),
  patchScene: (sceneId, patch) =>
    set((s) => ({
      localScenePatches: {
        ...s.localScenePatches,
        [sceneId]: { ...s.localScenePatches[sceneId], ...patch },
      },
    })),
  setLocalTourSettings: (settings) =>
    set((s) => ({ localTourSettings: { ...(s.localTourSettings || {}), ...settings } })),
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

export function mergeSceneWithPatches(scene: TourScene, patches: Partial<TourScene>): TourScene {
  return { ...scene, ...patches }
}
