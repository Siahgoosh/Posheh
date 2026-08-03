import { MapPin, GripVertical } from 'lucide-react'
import { HotspotForm } from './HotspotForm'
import { getHotspotTypeDef } from './constants'
import type { TourHotspot, TourScene } from '../types'

interface Props {
  scene: TourScene | null
  scenes: TourScene[]
  selectedHotspotId: number | string | null
  isPlacing: boolean
  onSelectHotspot: (id: number | string | null) => void
  onUpdateHotspot: (hotspot: TourHotspot) => void
  onDeleteHotspot: (id: number | string) => void
  onTogglePlacing: () => void
  onSave: () => void
  isSaving?: boolean
}

export function HotspotEditorPanel(props: Props) {
  const { scene, selectedHotspotId, onSelectHotspot } = props

  return (
    <div className="flex flex-col h-full">
      <div className="p-4 border-b border-card-border/50">
        <h2 className="font-semibold text-sm flex items-center gap-2">
          <MapPin className="h-4 w-4 text-primary" />
          هات‌اسپات‌ها
        </h2>
        <p className="text-[11px] text-muted mt-0.5">
          {scene ? scene.name : 'صحنه‌ای انتخاب نشده'}
        </p>
      </div>

      {scene && scene.hotspots.length > 0 && !selectedHotspotId && (
        <div className="p-3 space-y-1.5 border-b border-card-border/50 max-h-40 overflow-y-auto">
          {scene.hotspots.map((h) => {
            const def = getHotspotTypeDef(h.type)
            return (
              <button
                key={h.id}
                type="button"
                onClick={() => onSelectHotspot(h.id)}
                className="w-full flex items-center gap-2 p-2 rounded-lg bg-black/20 border border-card-border/50 hover:border-primary/30 text-right transition-all"
              >
                <GripVertical className="h-3 w-3 text-muted shrink-0" />
                <span className="text-sm">{def.emoji}</span>
                <span className="text-xs flex-1 truncate">{h.label || h.title || def.label}</span>
              </button>
            )
          })}
        </div>
      )}

      <div className="flex-1 overflow-y-auto">
        <HotspotForm {...props} />
      </div>
    </div>
  )
}
