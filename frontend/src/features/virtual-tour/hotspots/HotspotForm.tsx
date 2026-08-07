import { Trash2, MapPin, Save, Move, Eye, EyeOff, Play } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { HOTSPOT_TYPES, SCENE_LINK_ICONS } from './constants'
import type { TourHotspot, TourScene, HotspotType } from '../types'

interface Props {
  scene: TourScene | null
  scenes: TourScene[]
  selectedHotspotId: number | string | null
  isPlacing: boolean
  isLinking: boolean
  isRepositioning: boolean
  onSelectHotspot: (id: number | string | null) => void
  onUpdateHotspot: (hotspot: TourHotspot) => void
  onDeleteHotspot: (id: number | string) => void
  onTogglePlacing: () => void
  onToggleLinking: () => void
  onToggleRepositioning: () => void
  onPreviewScene?: (sceneId: number) => void
  onSave: () => void
  isSaving?: boolean
}

export function HotspotForm({
  scene,
  scenes,
  selectedHotspotId,
  isLinking,
  isRepositioning,
  onSelectHotspot,
  onUpdateHotspot,
  onDeleteHotspot,
  onTogglePlacing,
  onToggleLinking,
  onToggleRepositioning,
  onPreviewScene,
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
          className={`w-full ${isLinking ? 'bg-primary' : ''}`}
          variant={isLinking ? 'default' : 'outline'}
          onClick={onToggleLinking}
        >
          <MapPin className="h-4 w-4" />
          {isLinking ? 'روی تصویر کلیک کنید — اتصال صحنه' : 'اتصال صحنه (فلش)'}
        </Button>
        <p className="text-xs text-muted text-center leading-relaxed">
          روی پله‌ها، درب یا هر نقطه کلیک کنید. مقصد را از لیست انتخاب کنید — بدون وارد کردن مختصات.
        </p>
        <Button variant="outline" className="w-full" onClick={onTogglePlacing}>
          هات‌اسپات دیگر (اطلاعات، گالری...)
        </Button>
        {scene.hotspots.length > 0 && (
          <Button className="w-full" onClick={onSave} disabled={isSaving}>
            <Save className="h-4 w-4" />
            {isSaving ? 'در حال ذخیره...' : 'ذخیره اتصال‌ها'}
          </Button>
        )}
      </div>
    )
  }

  const update = (patch: Partial<TourHotspot>) => onUpdateHotspot({ ...hotspot, ...patch })
  const updateStyle = (key: string, value: unknown) =>
    update({ style: { ...hotspot.style, [key]: value } })
  const updateAction = (key: string, value: unknown) =>
    update({ action: { ...hotspot.action, [key]: value } })

  const isSceneLink = hotspot.type === 'scene'

  return (
    <div className="p-4 space-y-3 overflow-y-auto max-h-full">
      <div className="flex items-center justify-between">
        <h3 className="font-semibold text-sm">{isSceneLink ? 'فلش اتصال صحنه' : 'ویرایش هات‌اسپات'}</h3>
        <Button variant="ghost" size="icon" className="text-danger" onClick={() => onDeleteHotspot(hotspot.id)}>
          <Trash2 className="h-4 w-4" />
        </Button>
      </div>

      {!isSceneLink && (
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
      )}

      {isSceneLink && (
        <div>
          <label className="text-xs text-muted">صحنه مقصد</label>
          <select
            value={hotspot.target_scene_id || ''}
            onChange={(e) => {
              const id = Number(e.target.value) || null
              const target = scenes.find((s) => s.id === id)
              update({
                target_scene_id: id,
                label: target?.name || hotspot.label,
                tooltip: target ? `رفتن به ${target.name}` : hotspot.tooltip,
                action: { ...hotspot.action, type: 'scene', target_scene_id: id ?? undefined },
              })
            }}
            className="w-full mt-1 text-sm bg-black/30 border border-card-border rounded-lg px-3 py-2"
          >
            <option value="">انتخاب صحنه مقصد</option>
            {scenes.filter((s) => s.id !== scene.id).map((s) => (
              <option key={s.id} value={s.id}>{s.name}</option>
            ))}
          </select>
        </div>
      )}

      <Input placeholder="برچسب" value={hotspot.label || ''} onChange={(e) => update({ label: e.target.value })} />
      <Input placeholder="Tooltip" value={hotspot.tooltip || ''} onChange={(e) => update({ tooltip: e.target.value })} />

      {isSceneLink && (
        <>
          <div>
            <label className="text-xs text-muted">آیکون فلش</label>
            <div className="grid grid-cols-5 gap-1.5 mt-1">
              {Object.entries(SCENE_LINK_ICONS).map(([key, { emoji, label }]) => (
                <button
                  key={key}
                  type="button"
                  title={label}
                  onClick={() => update({ icon: key })}
                  className={`py-2 rounded-lg border text-lg transition-all ${
                    (hotspot.icon || 'arrow') === key
                      ? 'border-primary bg-primary/20'
                      : 'border-card-border/50 hover:border-white/20'
                  }`}
                >
                  {emoji}
                </button>
              ))}
            </div>
          </div>

          <div className="grid grid-cols-2 gap-2">
            <div>
              <label className="text-[10px] text-muted">Transition</label>
              <select
                value={hotspot.action?.transition_effect || 'fade'}
                onChange={(e) => updateAction('transition_effect', e.target.value)}
                className="w-full mt-1 text-xs bg-black/30 border border-card-border rounded-lg px-2 py-1.5"
              >
                <option value="fade">Fade</option>
                <option value="crossfade">Cross Fade</option>
                <option value="none">بدون افکت</option>
              </select>
            </div>
            <div>
              <label className="text-[10px] text-muted">مدت (ms)</label>
              <Input
                type="number"
                min={200}
                max={3000}
                step={100}
                value={hotspot.action?.transition_duration ?? 800}
                onChange={(e) => updateAction('transition_duration', Number(e.target.value))}
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-2">
            <div>
              <label className="text-[10px] text-muted">زاویه ورود Yaw</label>
              <Input
                type="number"
                value={hotspot.action?.entrance_yaw ?? ''}
                placeholder="خودکار"
                onChange={(e) => updateAction('entrance_yaw', e.target.value ? Number(e.target.value) : undefined)}
              />
            </div>
            <div>
              <label className="text-[10px] text-muted">زاویه ورود Pitch</label>
              <Input
                type="number"
                value={hotspot.action?.entrance_pitch ?? ''}
                placeholder="خودکار"
                onChange={(e) => updateAction('entrance_pitch', e.target.value ? Number(e.target.value) : undefined)}
              />
            </div>
          </div>
        </>
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
          <label className="text-[10px] text-muted">Hover</label>
          <select
            value={hotspot.style?.hoverAnimation || 'scale'}
            onChange={(e) => updateStyle('hoverAnimation', e.target.value)}
            className="w-full text-xs bg-black/30 border border-card-border rounded-lg px-2 py-1.5"
          >
            <option value="scale">بزرگ‌شدن</option>
            <option value="bounce">پرش</option>
            <option value="none">بدون انیمیشن</option>
          </select>
        </div>
      </div>

      <div className="flex flex-wrap gap-2">
        <Button
          variant={isRepositioning ? 'default' : 'outline'}
          size="sm"
          className="flex-1"
          onClick={onToggleRepositioning}
        >
          <Move className="h-3 w-3" />{isRepositioning ? 'روی تصویر کلیک کنید' : 'جابجایی'}
        </Button>
        <Button
          variant="outline"
          size="sm"
          onClick={() => updateAction('hidden', !hotspot.action?.hidden)}
        >
          {hotspot.action?.hidden ? <EyeOff className="h-3 w-3" /> : <Eye className="h-3 w-3" />}
        </Button>
        {isSceneLink && hotspot.target_scene_id && onPreviewScene && (
          <Button variant="outline" size="sm" onClick={() => onPreviewScene(hotspot.target_scene_id!)}>
            <Play className="h-3 w-3" />
          </Button>
        )}
      </div>

      <div className="text-[10px] text-muted font-mono">
        موقعیت: Yaw {hotspot.yaw.toFixed(1)}° · Pitch {hotspot.pitch.toFixed(1)}°
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
