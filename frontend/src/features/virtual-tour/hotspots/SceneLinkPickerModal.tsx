import { ArrowRight, X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import type { TourScene } from '../types'

interface Props {
  open: boolean
  scenes: TourScene[]
  currentSceneId: number
  onSelect: (sceneId: number) => void
  onCancel: () => void
}

export function SceneLinkPickerModal({ open, scenes, currentSceneId, onSelect, onCancel }: Props) {
  if (!open) return null

  const targets = scenes.filter((s) => s.id !== currentSceneId && s.is_visible !== false)

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
      <div className="w-full max-w-md rounded-2xl border border-card-border/60 bg-[#12121a] shadow-2xl overflow-hidden">
        <div className="flex items-center justify-between px-5 py-4 border-b border-white/10">
          <div>
            <h2 className="font-bold text-base">انتخاب صحنه مقصد</h2>
            <p className="text-xs text-muted mt-0.5">فلش به کدام صحنه منتقل شود؟</p>
          </div>
          <Button variant="ghost" size="icon" onClick={onCancel}><X className="h-4 w-4" /></Button>
        </div>

        <div className="p-3 space-y-2 max-h-[60vh] overflow-y-auto">
          {targets.length === 0 ? (
            <p className="text-sm text-muted text-center py-8">صحنه دیگری برای اتصال وجود ندارد. ابتدا صحنه جدید آپلود کنید.</p>
          ) : (
            targets.map((scene) => (
              <button
                key={scene.id}
                type="button"
                onClick={() => onSelect(scene.id)}
                className="w-full flex items-center gap-3 p-3 rounded-xl border border-card-border/50 bg-black/30 hover:border-primary/40 hover:bg-primary/5 transition-all text-right"
              >
                {scene.thumbnail_url ? (
                  <img src={scene.thumbnail_url} alt="" className="w-16 h-10 rounded-lg object-cover border border-white/10 shrink-0" />
                ) : (
                  <div className="w-16 h-10 rounded-lg bg-primary/20 flex items-center justify-center text-xs font-bold shrink-0">۳۶۰</div>
                )}
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-sm truncate">{scene.name}</p>
                  <p className="text-[10px] text-muted">{scene.hotspots?.length ?? 0} هات‌اسپات</p>
                </div>
                <ArrowRight className="h-4 w-4 text-primary shrink-0" />
              </button>
            ))
          )}
        </div>
      </div>
    </div>
  )
}
