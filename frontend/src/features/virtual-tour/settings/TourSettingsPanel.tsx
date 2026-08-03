import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import type { TourData, TourSettings } from '../types'

interface Props {
  tour: TourData
  onUpdateSettings: (settings: Partial<TourSettings>) => void
  onSave: () => void
  isSaving?: boolean
}

export function TourSettingsPanel({ tour, onUpdateSettings, onSave, isSaving }: Props) {
  const s = tour.settings || {}
  const update = (patch: Partial<TourSettings>) => onUpdateSettings({ ...s, ...patch })

  return (
    <div className="p-4 space-y-4 overflow-y-auto">
      <h2 className="font-semibold text-sm">تنظیمات تور</h2>

      <div className="space-y-3">
        <p className="text-xs font-medium text-muted">قابلیت‌های تور</p>
        {[
          ['auto_rotate', 'چرخش خودکار'],
          ['auto_tour', 'تور خودکار'],
          ['guided_tour', 'تور راهنما'],
          ['bookmarks', 'بوکمارک'],
          ['favorites', 'علاقه‌مندی'],
          ['history', 'تاریخچه'],
          ['mini_map', 'مینی‌مپ'],
          ['floor_selector', 'انتخاب طبقه'],
          ['share_enabled', 'اشتراک‌گذاری'],
          ['qr_enabled', 'QR Code'],
          ['embed_enabled', 'Embed'],
          ['enable_vr', 'حالت VR'],
          ['enable_gyroscope', 'ژیروسکوپ'],
          ['show_floor_plan', 'پلان طبقه'],
          ['show_contact_form', 'فرم تماس'],
        ].map(([key, label]) => (
          <label key={key} className="flex items-center justify-between text-sm">
            <span>{label}</span>
            <input
              type="checkbox"
              checked={!!s[key as keyof TourSettings]}
              onChange={(e) => update({ [key]: e.target.checked })}
              className="rounded"
            />
          </label>
        ))}
      </div>

      <Input placeholder="شماره تماس" value={s.phone || ''} onChange={(e) => update({ phone: e.target.value })} />
      <Input placeholder="واتساپ" value={s.whatsapp || ''} onChange={(e) => update({ whatsapp: e.target.value })} />
      <Input placeholder="تلگرام" value={s.telegram || ''} onChange={(e) => update({ telegram: e.target.value })} />

      <div className="grid grid-cols-2 gap-3">
        <div>
          <label className="text-xs text-muted">عرض جغرافیایی</label>
          <Input type="number" step="any" value={s.map_lat ?? ''} onChange={(e) => update({ map_lat: Number(e.target.value) })} />
        </div>
        <div>
          <label className="text-xs text-muted">طول جغرافیایی</label>
          <Input type="number" step="any" value={s.map_lng ?? ''} onChange={(e) => update({ map_lng: Number(e.target.value) })} />
        </div>
      </div>

      <div>
        <label className="text-xs text-muted">رنگ برند</label>
        <input type="color" value={s.brand_color || '#2dd4bf'} onChange={(e) => update({ brand_color: e.target.value })} className="w-full h-9 rounded cursor-pointer mt-1" />
      </div>

      <div>
        <label className="text-xs text-muted">فاصله تور خودکار (ثانیه)</label>
        <Input type="number" min={3} max={60} value={s.auto_tour_interval ?? 8} onChange={(e) => update({ auto_tour_interval: Number(e.target.value) })} />
      </div>

      <Button className="w-full" onClick={onSave} disabled={isSaving}>
        {isSaving ? 'در حال ذخیره...' : 'ذخیره تنظیمات تور'}
      </Button>
    </div>
  )
}
