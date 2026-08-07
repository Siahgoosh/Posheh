import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Home, Search, X } from 'lucide-react'
import api from '@/lib/api'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'

interface PropertyOption {
  id: number
  code: string
  type?: string
  city?: string
  district?: string
  area?: number
}

interface Props {
  propertyId?: number | null
  linkedProperty?: { code: string; city?: string } | null
  onChange: (propertyId: number | null) => void
}

export function TourPropertyPicker({ propertyId, linkedProperty, onChange }: Props) {
  const [search, setSearch] = useState('')
  const [open, setOpen] = useState(false)

  const { data: options = [], isFetching } = useQuery({
    queryKey: ['tour-property-picker', search],
    queryFn: async () => {
      const res = await api.get('/properties', {
        params: { search: search.trim() || undefined, per_page: 12 },
      })
      return (res.data.data ?? []) as PropertyOption[]
    },
    enabled: open,
    staleTime: 15000,
  })

  const label = linkedProperty
    ? `${linkedProperty.code}${linkedProperty.city ? ` · ${linkedProperty.city}` : ''}`
    : propertyId
      ? `ملک #${propertyId}`
      : null

  return (
    <div className="space-y-2">
      <p className="text-xs font-medium text-muted">اتصال به فایل / ملک (اختیاری)</p>
      {label && !open ? (
        <div className="flex items-center gap-2 rounded-lg border border-card-border px-3 py-2 text-sm">
          <Home className="h-4 w-4 text-primary shrink-0" />
          <span className="flex-1 truncate">{label}</span>
          <Button
            type="button"
            size="sm"
            variant="ghost"
            className="h-7 px-2 text-muted"
            onClick={() => onChange(null)}
          >
            <X className="h-3.5 w-3.5" />
          </Button>
        </div>
      ) : (
        <div className="space-y-2">
          <div className="relative">
            <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted" />
            <Input
              placeholder="جستجوی کد ملک، شهر یا محله..."
              value={search}
              onChange={(e) => {
                setSearch(e.target.value)
                setOpen(true)
              }}
              onFocus={() => setOpen(true)}
              className="pr-9"
            />
          </div>
          {open && (
            <div className="rounded-lg border border-card-border bg-background max-h-48 overflow-y-auto">
              {isFetching && <p className="p-3 text-xs text-muted">در حال جستجو...</p>}
              {!isFetching && options.length === 0 && (
                <p className="p-3 text-xs text-muted">ملکی پیدا نشد</p>
              )}
              {options.map((p) => (
                <button
                  key={p.id}
                  type="button"
                  className={`w-full text-left px-3 py-2 text-sm hover:bg-primary/10 border-b border-card-border/50 last:border-0 ${
                    propertyId === p.id ? 'bg-primary/15' : ''
                  }`}
                  onClick={() => {
                    onChange(p.id)
                    setOpen(false)
                    setSearch('')
                  }}
                >
                  <span className="font-medium">{p.code}</span>
                  <span className="text-xs text-muted mr-2">
                    {[p.city, p.district, p.area ? `${p.area}م` : null].filter(Boolean).join(' · ')}
                  </span>
                </button>
              ))}
            </div>
          )}
          {propertyId && (
            <Button type="button" variant="ghost" size="sm" className="text-xs" onClick={() => onChange(null)}>
              حذف اتصال ملک
            </Button>
          )}
        </div>
      )}
      <p className="text-[10px] text-muted leading-relaxed">
        با اتصال تور به یک فایل، در صفحه عمومی تور و فایل ملک لینک تور نمایش داده می‌شود.
      </p>
    </div>
  )
}
