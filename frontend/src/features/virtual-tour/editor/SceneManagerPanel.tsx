import { useRef, useState } from 'react'
import { Search, Filter, PanelLeftClose, PanelLeft } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { SceneListItem } from './SceneListItem'
import { PanoramaUploader } from './PanoramaUploader'
import { filterAndSortScenes, useTourEditorStore } from '../store/editorStore'
import type { SceneFilter, SceneSort, TourScene } from '../types'

interface Props {
  tourId: number | string
  scenes: TourScene[]
  onSceneSelect: (sceneId: number) => void
  onSceneRename: (sceneId: number, name: string) => void
  onSceneDuplicate: (sceneId: number) => void
  onSceneDelete: (sceneId: number) => void
  onScenePublish: (sceneId: number) => void
  onSceneUnpublish: (sceneId: number) => void
  onSceneToggleVisibility: (sceneId: number) => void
  onSceneSetDefault: (sceneId: number) => void
  onSceneReorder: (sceneIds: number[]) => void
  onRefresh: () => void
}

const FILTERS: { value: SceneFilter; label: string }[] = [
  { value: 'all', label: 'همه' },
  { value: 'published', label: 'منتشر شده' },
  { value: 'draft', label: 'پیش‌نویس' },
  { value: 'visible', label: 'قابل نمایش' },
  { value: 'hidden', label: 'مخفی' },
]

const SORTS: { value: SceneSort; label: string }[] = [
  { value: 'order', label: 'ترتیب' },
  { value: 'name', label: 'نام' },
  { value: 'status', label: 'وضعیت' },
]

export function SceneManagerPanel({
  tourId,
  scenes,
  onSceneSelect,
  onSceneRename,
  onSceneDuplicate,
  onSceneDelete,
  onScenePublish,
  onSceneUnpublish,
  onSceneToggleVisibility,
  onSceneSetDefault,
  onSceneReorder,
  onRefresh,
}: Props) {
  const {
    activeSceneId,
    search,
    filter,
    sort,
    isPanelCollapsed,
    setSearch,
    setFilter,
    setSort,
    setPanelCollapsed,
  } = useTourEditorStore()

  const dragSceneId = useRef<number | null>(null)
  const [showFilters, setShowFilters] = useState(false)

  const filtered = filterAndSortScenes(scenes, search, filter, sort)

  const handleDragStart = (sceneId: number) => (e: React.DragEvent) => {
    dragSceneId.current = sceneId
    e.dataTransfer.effectAllowed = 'move'
  }

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault()
    e.dataTransfer.dropEffect = 'move'
  }

  const handleDrop = (targetId: number) => (e: React.DragEvent) => {
    e.preventDefault()
    const sourceId = dragSceneId.current
    dragSceneId.current = null
    if (!sourceId || sourceId === targetId) return

    const ids = scenes.map((s) => s.id)
    const fromIdx = ids.indexOf(sourceId)
    const toIdx = ids.indexOf(targetId)
    if (fromIdx < 0 || toIdx < 0) return

    ids.splice(fromIdx, 1)
    ids.splice(toIdx, 0, sourceId)
    onSceneReorder(ids)
  }

  if (isPanelCollapsed) {
    return (
      <div className="flex flex-col items-center py-4 px-2">
        <Button variant="ghost" size="icon" onClick={() => setPanelCollapsed(false)}>
          <PanelLeft className="h-5 w-5" />
        </Button>
        <p className="text-[10px] text-muted mt-2 writing-mode-vertical">{scenes.length} صحنه</p>
      </div>
    )
  }

  return (
    <div className="flex flex-col h-full">
      <div className="flex items-center justify-between p-4 border-b border-card-border/50">
        <div>
          <h2 className="font-semibold text-sm">مدیریت صحنه‌ها</h2>
          <p className="text-[11px] text-muted">{scenes.length} صحنه</p>
        </div>
        <Button variant="ghost" size="icon" onClick={() => setPanelCollapsed(true)}>
          <PanelLeftClose className="h-4 w-4" />
        </Button>
      </div>

      <div className="p-4 space-y-3 border-b border-card-border/50">
        <div className="relative">
          <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted" />
          <Input
            placeholder="جستجوی صحنه..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pr-9 text-sm"
          />
        </div>

        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            className="flex-1 text-xs"
            onClick={() => setShowFilters(!showFilters)}
          >
            <Filter className="h-3 w-3" />
            فیلتر
          </Button>
          <select
            value={sort}
            onChange={(e) => setSort(e.target.value as SceneSort)}
            className="flex-1 text-xs bg-black/30 border border-card-border rounded-lg px-2 py-1.5 outline-none"
          >
            {SORTS.map((s) => (
              <option key={s.value} value={s.value}>{s.label}</option>
            ))}
          </select>
        </div>

        {showFilters && (
          <div className="flex flex-wrap gap-1.5">
            {FILTERS.map((f) => (
              <button
                key={f.value}
                type="button"
                onClick={() => setFilter(f.value)}
                className={`px-2.5 py-1 rounded-lg text-[11px] transition-all ${
                  filter === f.value
                    ? 'bg-primary/20 text-primary border border-primary/30'
                    : 'bg-black/20 text-muted border border-card-border/50 hover:border-white/10'
                }`}
              >
                {f.label}
              </button>
            ))}
          </div>
        )}
      </div>

      <div className="flex-1 overflow-y-auto p-4 space-y-2">
        {filtered.map((scene) => (
          <SceneListItem
            key={scene.id}
            scene={scene}
            isActive={activeSceneId === scene.id}
            isDragging={dragSceneId.current === scene.id}
            onSelect={() => onSceneSelect(scene.id)}
            onRename={(name) => onSceneRename(scene.id, name)}
            onDuplicate={() => onSceneDuplicate(scene.id)}
            onDelete={() => onSceneDelete(scene.id)}
            onPublish={() => onScenePublish(scene.id)}
            onUnpublish={() => onSceneUnpublish(scene.id)}
            onToggleVisibility={() => onSceneToggleVisibility(scene.id)}
            onSetDefault={() => onSceneSetDefault(scene.id)}
            onDragStart={handleDragStart(scene.id)}
            onDragOver={handleDragOver}
            onDrop={handleDrop(scene.id)}
            onDragEnd={() => { dragSceneId.current = null }}
          />
        ))}

        {filtered.length === 0 && (
          <div className="text-center py-8 text-muted text-sm">
            <p>صحنه‌ای یافت نشد</p>
          </div>
        )}
      </div>

      <div className="p-4 border-t border-card-border/50 space-y-3">
        <PanoramaUploader tourId={tourId} onUploaded={onRefresh} />
      </div>
    </div>
  )
}
