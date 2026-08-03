import { Trash2, MapPin, Save } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { HOTSPOT_TYPES } from './constants'
import type { TourHotspot, TourScene, HotspotType } from '../types'

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

export function HotspotForm({
  scene,
  scenes,
  selectedHotspotId,
  isPlacing,
  onSelectHotspot,
  onUpdateHotspot,
  onDeleteHotspot,
  onTogglePlacing,
  onSave,
  isSaving,
}: Props) {
  const hotspot = scene?.hotspots.find((h) => h.id === selectedHotspotId)

  if (!scene) {
    return <p className="text-sm text-muted p-4">ابتدا یک صحنه انتخاب کنید</p>
  }

  if (!hotspot) {
    return (
      <div className="p-4 space-y-4">
        <Button
          className={`w-full ${isPlacing ? 'bg-primary' : ''}`}
          variant={isPlacing ? 'default' : 'outline'}
          onClick={onTogglePlacing}
        >
          <MapPin className="h-4 w-4" />
          {isPlacing ? 'روی تصویر کلیک کنید...' : 'افزودن هات‌اسپات'}
        </Button>
        <p className="text-xs text-muted text-center">
          {scene.hotspots.length} هات‌اسپات در این صحنه
        </p>
        {scene.hotspots.length > 0 && (
          <Button className="w-full" onClick={onSave} disabled={isSaving}>
            <Save className="h-4 w-4" />
            {isSaving ? 'در حال ذخیره...' : 'ذخیره هات‌اسپات‌ها'}
          </Button>
        )}
      </div>
    )
  }

  const update = (patch: Partial<TourHotspot>) => onUpdateHotspot({ ...hotspot, ...patch })
  const updateStyle = (key: string, value: unknown) =>
    update({ style: { ...hotspot.style, [key]: value } })

  return (
    <div className="p-4 space-y-3 overflow-y-auto max-h-full">
      <div className="flex items-center justify-between">
        <h3 className="font-semibold text-sm">ویرایش هات‌اسپات</h3>
        <Button variant="ghost" size="icon" className="text-danger" onClick={() => onDeleteHotspot(hotspot.id)}>
          <Trash2 className="h-4 w-4" />
        </Button>
      </div>

      <div>
        <label className="text-xs text-muted">نوع</label>
        <select
          value={hotspot.type}
          onChange={(e) => update({ type: e.target.value as HotspotType })}
          className="w-full mt-1 text-sm bg-black/30 border border-card-border rounded-lg px-3 py-2"
        >
          {HOTSPOT_TYPES.map((t) => (
            <option key={t.type} value={t.type}>{t.emoji} {t.label}</option>
          ))}
        </select>
      </div>

      <Input placeholder="عنوان" value={hotspot.title || ''} onChange={(e) => update({ title: e.target.value })} />
      <Input placeholder="برچسب" value={hotspot.label || ''} onChange={(e) => update({ label: e.target.value })} />
      <Input placeholder="Tooltip" value={hotspot.tooltip || ''} onChange={(e) => update({ tooltip: e.target.value })} />

      {hotspot.type === 'scene' && (
        <div>
          <label className="text-xs text-muted">صحنه مقصد</label>
          <select
            value={hotspot.target_scene_id || ''}
            onChange={(e) => update({ target_scene_id: Number(e.target.value) || null })}
            className="w-full mt-1 text-sm bg-black/30 border border-card-border rounded-lg px-3 py-2"
          >
            <option value="">انتخاب صحنه</option>
            {scenes.filter((s) => s.id !== scene.id).map((s) => (
              <option key={s.id} value={s.id}>{s.name}</option>
            ))}
          </select>
        </div>
      )}

      {['website', 'link', 'telegram', 'pdf'].includes(hotspot.type) && (
        <Input placeholder="لینک URL" value={hotspot.link_url || ''} onChange={(e) => update({ link_url: e.target.value })} />
      )}

      {['phone', 'whatsapp'].includes(hotspot.type) && (
        <Input placeholder="شماره تماس" value={hotspot.action?.phone || ''} onChange={(e) => update({ action: { ...hotspot.action, phone: e.target.value } })} />
      )}

      {hotspot.type === 'email' && (
        <Input placeholder="ایمیل" value={hotspot.action?.email || ''} onChange={(e) => update({ action: { ...hotspot.action, email: e.target.value } })} />
      )}

      <div className="border-t border-card-border/50 pt-3 space-y-2">
        <p className="text-xs font-medium text-muted">ظاهر</p>
        <div className="grid grid-cols-2 gap-2">
          <div>
            <label className="text-[10px] text-muted">رنگ</label>
            <input type="color" value={hotspot.style?.color || '#2dd4bf'} onChange={(e) => updateStyle('color', e.target.value)} className="w-full h-8 rounded cursor-pointer" />
          </div>
          <div>
            <label className="text-[10px] text-muted">اندازه</label>
            <Input type="number" min={20} max={80} value={hotspot.style?.size || 36} onChange={(e) => updateStyle('size', Number(e.target.value))} />
          </div>
        </div>
        <div className="flex flex-wrap gap-2">
          {[
            ['glow', 'درخشش'],
            ['pulse', 'پالس'],
            ['shadow', 'سایه'],
          ].map(([key, label]) => (
            <label key={key} className="flex items-center gap-1.5 text-xs cursor-pointer">
              <input
                type="checkbox"
                checked={!!hotspot.style?.[key as keyof typeof hotspot.style]}
                onChange={(e) => updateStyle(key, e.target.checked)}
              />
              {label}
            </label>
          ))}
        </div>
        <div>
          <label className="text-[10px] text-muted">شفافیت</label>
          <input type="range" min={0.3} max={1} step={0.1} value={hotspot.style?.opacity ?? 1} onChange={(e) => updateStyle('opacity', Number(e.target.value))} className="w-full" />
        </div>
      </div>

      <div className="border-t border-card-border/50 pt-3 space-y-2">
        <p className="text-xs font-medium text-muted">محتوای Popup</p>
        <Input placeholder="عنوان Popup" value={hotspot.popup?.title || ''} onChange={(e) => update({ popup: { ...hotspot.popup, title: e.target.value } })} />
        <textarea
          placeholder="توضیحات"
          value={hotspot.popup?.description || hotspot.content || ''}
          onChange={(e) => update({ popup: { ...hotspot.popup, description: e.target.value }, content: e.target.value })}
          className="w-full text-sm bg-black/30 border border-card-border rounded-lg px-3 py-2 min-h-[80px] resize-none"
        />
        <Input placeholder="URL ویدئو" value={hotspot.popup?.video_url || ''} onChange={(e) => update({ popup: { ...hotspot.popup, video_url: e.target.value } })} />
        <label className="flex items-center gap-2 text-xs">
          <input type="checkbox" checked={!!hotspot.popup?.show_lead_form} onChange={(e) => update({ popup: { ...hotspot.popup, show_lead_form: e.target.checked } })} />
          نمایش فرم درخواست
        </label>
      </div>

      <div className="text-[10px] text-muted font-mono">
        Yaw: {hotspot.yaw.toFixed(1)}° | Pitch: {hotspot.pitch.toFixed(1)}°
      </div>

      <div className="flex gap-2">
        <Button variant="outline" className="flex-1" onClick={() => onSelectHotspot(null)}>بستن</Button>
        <Button className="flex-1" onClick={onSave} disabled={isSaving}>
          {isSaving ? '...' : 'ذخیره'}
        </Button>
      </div>
    </div>
  )
}
