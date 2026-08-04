import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import type { TourScene } from '../types'

interface Props {
  scene: TourScene | null
  onUpdate: (data: Partial<TourScene>) => void
  onSave: () => void
  isSaving?: boolean
}

export function SceneSettingsPanel({ scene, onUpdate, onSave, isSaving }: Props) {
  if (!scene) {
    return <p className="text-sm text-muted p-4">صحنه‌ای انتخاب نشده</p>
  }

  return (
    <div className="p-4 space-y-4 overflow-y-auto">
      <h2 className="font-semibold text-sm">تنظیمات صحنه — {scene.name}</h2>

      <div className="grid grid-cols-2 gap-3">
        <div>
          <label className="text-xs text-muted">Yaw اولیه</label>
          <Input type="number" value={scene.default_yaw ?? 0} onChange={(e) => onUpdate({ default_yaw: Number(e.target.value) })} />
        </div>
        <div>
          <label className="text-xs text-muted">Pitch اولیه</label>
          <Input type="number" value={scene.default_pitch ?? 0} onChange={(e) => onUpdate({ default_pitch: Number(e.target.value) })} />
        </div>
        <div>
          <label className="text-xs text-muted">FOV</label>
          <Input type="number" min={30} max={120} value={scene.default_fov ?? 75} onChange={(e) => onUpdate({ default_fov: Number(e.target.value) })} />
        </div>
        <div>
          <label className="text-xs text-muted">جهت اولیه</label>
          <Input type="number" value={scene.scene_settings?.initial_direction ?? 0} onChange={(e) => onUpdate({ scene_settings: { ...scene.scene_settings, initial_direction: Number(e.target.value) } })} />
        </div>
      </div>

      <div>
        <label className="text-xs text-muted">افکت انتقال</label>
        <select
          value={scene.transition_effect || 'fade'}
          onChange={(e) => onUpdate({ transition_effect: e.target.value as TourScene['transition_effect'] })}
          className="w-full mt-1 text-sm bg-black/30 border border-card-border rounded-lg px-3 py-2"
        >
          <option value="fade">Fade</option>
          <option value="crossfade">Cross Fade</option>
          <option value="none">بدون افکت</option>
        </select>
      </div>

      <Input placeholder="URL موسیقی پس‌زمینه" value={scene.background_music || ''} onChange={(e) => onUpdate({ background_music: e.target.value })} />
      <Input placeholder="URL صدای محیطی" value={scene.ambient_sound || ''} onChange={(e) => onUpdate({ ambient_sound: e.target.value })} />

      <div className="grid grid-cols-2 gap-3">
        <div>
          <label className="text-xs text-muted">موقعیت پلان X%</label>
          <Input type="number" value={scene.floor_plan_x ?? ''} onChange={(e) => onUpdate({ floor_plan_x: Number(e.target.value) })} />
        </div>
        <div>
          <label className="text-xs text-muted">موقعیت پلان Y%</label>
          <Input type="number" value={scene.floor_plan_y ?? ''} onChange={(e) => onUpdate({ floor_plan_y: Number(e.target.value) })} />
        </div>
      </div>

      <Button className="w-full" onClick={onSave} disabled={isSaving}>
        {isSaving ? 'در حال ذخیره...' : 'ذخیره تنظیمات صحنه'}
      </Button>
    </div>
  )
}
