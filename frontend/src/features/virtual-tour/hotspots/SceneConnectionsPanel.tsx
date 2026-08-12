import { GitBranch } from 'lucide-react'
import type { TourScene } from '../types'

interface Props {
  scenes: TourScene[]
  activeSceneId: number | null
  onSelectScene: (id: number) => void
}

export function SceneConnectionsPanel({ scenes, activeSceneId, onSelectScene }: Props) {
  const links = scenes.flatMap((scene) =>
    (scene.hotspots || [])
      .filter((h) => h.type === 'scene' && h.target_scene_id)
      .map((h) => ({
        from: scene,
        to: scenes.find((s) => s.id === h.target_scene_id),
        label: h.label || h.title,
      }))
      .filter((l) => l.to),
  )

  if (!scenes.length) return null

  return (
    <div className="px-4 pb-3 border-b border-card-border/50">
      <div className="flex items-center gap-2 mb-2">
        <GitBranch className="h-3.5 w-3.5 text-primary" />
        <span className="text-xs font-medium">مسیرهای اتصال</span>
      </div>
      {links.length === 0 ? (
        <p className="text-[10px] text-muted">هنوز اتصالی بین صحنه‌ها نیست. روی تصویر کلیک کنید.</p>
      ) : (
        <div className="space-y-1 max-h-24 overflow-y-auto">
          {links.map((link, i) => (
            <button
              key={i}
              type="button"
              onClick={() => onSelectScene(link.from.id)}
              className={`w-full text-[10px] text-right px-2 py-1 rounded-lg border transition-all ${
                activeSceneId === link.from.id
                  ? 'border-primary/40 bg-primary/10 text-primary'
                  : 'border-card-border/40 text-muted hover:border-white/10'
              }`}
            >
              {link.from.name} → {link.to?.name}
            </button>
          ))}
        </div>
      )}
      <div className="flex flex-wrap gap-1 mt-2">
        {scenes.map((s) => (
          <span key={s.id} className="text-[9px] px-1.5 py-0.5 rounded bg-black/30 border border-white/5 text-muted">
            {s.name}: {(s.hotspots || []).filter((h) => h.type === 'scene').length} فلش
          </span>
        ))}
      </div>
    </div>
  )
}
