import { useState } from 'react'
import type { TourScene } from '../../types'

interface Props {
  scenes: TourScene[]
  activeSceneId: number | null
  visitedSceneIds: number[]
  floorPlanUrl?: string | null
  onSelectScene: (id: number) => void
  className?: string
}

export function SmartWalkMiniMap({
  scenes,
  activeSceneId,
  visitedSceneIds,
  floorPlanUrl,
  onSelectScene,
  className = '',
}: Props) {
  const [collapsed, setCollapsed] = useState(false)

  if (collapsed) {
    return (
      <button
        type="button"
        onClick={() => setCollapsed(false)}
        className={`sw-panel absolute top-4 right-4 z-30 px-3 py-1.5 text-xs font-medium rounded-lg ${className}`}
      >
        نقشه
      </button>
    )
  }

  return (
    <div className={`sw-panel absolute top-4 right-4 z-30 w-44 rounded-xl overflow-hidden shadow-2xl ${className}`}>
      <div className="flex items-center justify-between px-3 py-2 border-b border-[var(--sw-border)]">
        <span className="text-xs font-semibold">نقشه مسیر</span>
        <button type="button" className="text-[10px] text-[var(--sw-muted)]" onClick={() => setCollapsed(true)}>
          −
        </button>
      </div>
      <div className="relative aspect-square bg-black/30">
        {floorPlanUrl ? (
          <img src={floorPlanUrl} alt="پلان" className="w-full h-full object-contain opacity-80" />
        ) : (
          <div className="absolute inset-0 grid grid-cols-3 grid-rows-3 gap-1 p-2 opacity-40">
            {Array.from({ length: 9 }).map((_, i) => (
              <div key={i} className="rounded bg-white/10" />
            ))}
          </div>
        )}
        {scenes.map((s) => {
          if (!s.floor_plan_x && !s.floor_plan_y) return null
          const visited = visitedSceneIds.includes(s.id)
          const active = s.id === activeSceneId
          return (
            <button
              key={s.id}
              type="button"
              title={s.name}
              onClick={() => onSelectScene(s.id)}
              className={`absolute w-3 h-3 rounded-full border-2 -translate-x-1/2 -translate-y-1/2 transition-all ${
                active ? 'bg-primary border-primary scale-125' : visited ? 'bg-emerald-400/80 border-white' : 'bg-white/40 border-white/60'
              }`}
              style={{
                left: `${s.floor_plan_x ?? 50}%`,
                top: `${s.floor_plan_y ?? 50}%`,
              }}
            />
          )
        })}
      </div>
      <div className="px-2 py-1.5 flex flex-wrap gap-1 max-h-20 overflow-y-auto">
        {scenes.map((s) => (
          <button
            key={s.id}
            type="button"
            onClick={() => onSelectScene(s.id)}
            className={`text-[9px] px-1.5 py-0.5 rounded border transition-all ${
              s.id === activeSceneId
                ? 'border-primary text-primary bg-primary/10'
                : visitedSceneIds.includes(s.id)
                  ? 'border-emerald-500/40 text-emerald-300'
                  : 'border-[var(--sw-border)] text-[var(--sw-muted)]'
            }`}
          >
            {s.name}
          </button>
        ))}
      </div>
    </div>
  )
}
