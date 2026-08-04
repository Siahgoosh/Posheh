import {
  Share2, Heart, Printer, Download, MessageCircle, Phone, X,
  ChevronLeft, ChevronRight, Sun, Moon, Monitor,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import type { TourData } from '../../types'
import { useSmartWalkTheme, type SmartWalkThemeMode } from '../theme/SmartWalkTheme'

interface Props {
  tour: TourData
  open: boolean
  onToggle: () => void
  onContact?: () => void
  onShare?: () => void
  favorite?: boolean
  onToggleFavorite?: () => void
}

export function SmartWalkSidebar({
  tour,
  open,
  onToggle,
  onContact,
  onShare,
  favorite = false,
  onToggleFavorite,
}: Props) {
  const { mode, setMode } = useSmartWalkTheme()
  const p = tour.property
  const settings = tour.settings ?? {}
  const brand = settings.brand_color || '#2dd4bf'

  const specs = [
    { label: 'قیمت', value: p?.price ? `${p.price.toLocaleString('fa-IR')} تومان` : settings.property_price },
    { label: 'متراژ', value: p?.area ? `${p.area} متر` : settings.property_area },
    { label: 'اتاق', value: settings.property_bedrooms },
    { label: 'حمام', value: settings.property_bathrooms },
    { label: 'پارکینگ', value: settings.property_parking },
    { label: 'انبار', value: settings.property_warehouse },
    { label: 'سال ساخت', value: settings.property_year_built },
    { label: 'کد ملک', value: p?.code },
  ].filter((s) => s.value)

  const themeButtons: { m: SmartWalkThemeMode; icon: typeof Sun; label: string }[] = [
    { m: 'light', icon: Sun, label: 'روشن' },
    { m: 'dark', icon: Moon, label: 'تیره' },
    { m: 'auto', icon: Monitor, label: 'خودکار' },
  ]

  return (
    <>
      <button
        type="button"
        onClick={onToggle}
        className="sw-panel absolute top-4 left-4 z-30 p-2 rounded-lg hidden md:flex items-center gap-1 text-xs"
        aria-label="اطلاعات ملک"
      >
        {open ? <ChevronLeft className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
        <span>ملک</span>
      </button>

      <aside
        className={`sw-sidebar absolute top-0 left-0 z-40 h-full w-full max-w-sm border-r border-[var(--sw-border)] bg-[var(--sw-sidebar-bg)] backdrop-blur-xl transition-transform duration-300 ease-out ${
          open ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="flex items-center justify-between p-4 border-b border-[var(--sw-border)]">
          <h2 className="font-bold text-sm">{tour.title}</h2>
          <Button variant="ghost" size="icon" onClick={onToggle}><X className="h-4 w-4" /></Button>
        </div>

        <div className="p-4 space-y-4 overflow-y-auto max-h-[calc(100%-4rem)]">
          {specs.length > 0 && (
            <div className="grid grid-cols-2 gap-2">
              {specs.map((s) => (
                <div key={s.label} className="sw-spec rounded-lg p-2.5">
                  <p className="text-[10px] text-[var(--sw-muted)]">{s.label}</p>
                  <p className="text-sm font-semibold mt-0.5">{String(s.value)}</p>
                </div>
              ))}
            </div>
          )}

          {(tour.description || settings.property_description) && (
            <p className="text-xs text-[var(--sw-muted)] leading-relaxed">
              {tour.description || settings.property_description}
            </p>
          )}

          <div className="flex flex-wrap gap-2">
            {onShare && (
              <Button size="sm" variant="outline" className="sw-btn" onClick={onShare}>
                <Share2 className="h-3.5 w-3.5" />اشتراک
              </Button>
            )}
            {onToggleFavorite && (
              <Button size="sm" variant="outline" className="sw-btn" onClick={onToggleFavorite}>
                <Heart className={`h-3.5 w-3.5 ${favorite ? 'fill-red-400 text-red-400' : ''}`} />
                علاقه‌مندی
              </Button>
            )}
            <Button size="sm" variant="outline" className="sw-btn" onClick={() => window.print()}>
              <Printer className="h-3.5 w-3.5" />چاپ
            </Button>
            {settings.property_pdf_url && (
              <a href={settings.property_pdf_url} target="_blank" rel="noreferrer">
                <Button size="sm" variant="outline" className="sw-btn">
                  <Download className="h-3.5 w-3.5" />PDF
                </Button>
              </a>
            )}
          </div>

          <div className="flex flex-wrap gap-2">
            {onContact && (
              <Button size="sm" className="flex-1" style={{ background: brand }} onClick={onContact}>
                تماس با مشاور
              </Button>
            )}
            {(settings.whatsapp || settings.phone) && (
              <a
                href={`https://wa.me/98${(settings.whatsapp || settings.phone || '').replace(/^0/, '').replace(/\D/g, '')}`}
                target="_blank"
                rel="noreferrer"
                className="flex-1"
              >
                <Button size="sm" variant="outline" className="w-full sw-btn">
                  <MessageCircle className="h-3.5 w-3.5" />واتساپ
                </Button>
              </a>
            )}
            {(settings.phone || tour.office?.phone) && (
              <a href={`tel:${settings.phone || tour.office?.phone}`} className="flex-1">
                <Button size="sm" variant="outline" className="w-full sw-btn">
                  <Phone className="h-3.5 w-3.5" />تماس
                </Button>
              </a>
            )}
          </div>

          <div className="pt-2 border-t border-[var(--sw-border)]">
            <p className="text-[10px] text-[var(--sw-muted)] mb-2">تم نمایش</p>
            <div className="flex gap-1">
              {themeButtons.map(({ m, icon: Icon, label }) => (
                <button
                  key={m}
                  type="button"
                  onClick={() => setMode(m)}
                  className={`flex items-center gap-1 px-2 py-1 rounded text-[10px] border transition-all ${
                    mode === m ? 'border-primary text-primary bg-primary/10' : 'border-[var(--sw-border)]'
                  }`}
                >
                  <Icon className="h-3 w-3" />{label}
                </button>
              ))}
            </div>
          </div>
        </div>
      </aside>
    </>
  )
}
