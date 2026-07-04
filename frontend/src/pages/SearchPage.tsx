import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Search, SlidersHorizontal } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice, formatNumber } from '@/lib/utils'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

export function SearchPage() {
  const [filters, setFilters] = useState({
    q: '',
    type: '',
    min_price: '',
    max_price: '',
    min_area: '',
    max_area: '',
    rooms: '',
    city: '',
    has_parking: false,
    has_elevator: false,
  })
  const [showAdvanced, setShowAdvanced] = useState(false)

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['search', filters],
    queryFn: async () => {
      const params = new URLSearchParams()
      Object.entries(filters).forEach(([k, v]) => {
        if (v) params.set(k, String(v))
      })
      const res = await api.get(`/properties?${params}`)
      return res.data
    },
    enabled: false,
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold">جستجوی پیشرفته</h1>
        <p className="text-muted mt-1">فیلتر و جستجوی دقیق املاک</p>
      </div>

      <Card>
        <div className="space-y-4">
          <div className="flex gap-3">
            <div className="relative flex-1">
              <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted" />
              <Input
                placeholder="کد، آدرس، نام مالک..."
                value={filters.q}
                onChange={(e) => setFilters({ ...filters, q: e.target.value })}
                className="pr-10"
              />
            </div>
            <Button onClick={() => refetch()}>
              <Search className="h-4 w-4" />
              جستجو
            </Button>
            <Button variant="secondary" onClick={() => setShowAdvanced(!showAdvanced)}>
              <SlidersHorizontal className="h-4 w-4" />
            </Button>
          </div>

          {showAdvanced && (
            <div className="grid sm:grid-cols-3 gap-4 pt-4 border-t border-card-border">
              <div>
                <label className="text-sm text-muted mb-1 block">نوع</label>
                <select
                  value={filters.type}
                  onChange={(e) => setFilters({ ...filters, type: e.target.value })}
                  className="flex h-11 w-full rounded-xl border border-card-border bg-white/5 px-4 text-sm"
                >
                  <option value="">همه</option>
                  <option value="sale">فروش</option>
                  <option value="rent">اجاره</option>
                  <option value="mortgage">رهن</option>
                </select>
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">حداقل قیمت</label>
                <Input type="number" value={filters.min_price} onChange={(e) => setFilters({ ...filters, min_price: e.target.value })} dir="ltr" />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">حداکثر قیمت</label>
                <Input type="number" value={filters.max_price} onChange={(e) => setFilters({ ...filters, max_price: e.target.value })} dir="ltr" />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">حداقل متراژ</label>
                <Input type="number" value={filters.min_area} onChange={(e) => setFilters({ ...filters, min_area: e.target.value })} dir="ltr" />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">حداکثر متراژ</label>
                <Input type="number" value={filters.max_area} onChange={(e) => setFilters({ ...filters, max_area: e.target.value })} dir="ltr" />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">تعداد خواب</label>
                <Input type="number" value={filters.rooms} onChange={(e) => setFilters({ ...filters, rooms: e.target.value })} dir="ltr" />
              </div>
            </div>
          )}
        </div>
      </Card>

      {isLoading && (
        <div className="flex justify-center py-20">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      )}

      {data && (
        <div className="space-y-4">
          <p className="text-muted">{formatNumber(data.meta?.total ?? 0)} نتیجه</p>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {data.data?.map((property: { id: number; code: string; type_label: string; price?: number; city?: string }) => (
              <Card key={property.id} className="!p-4 glass-hover">
                <div className="flex justify-between items-start">
                  <span className="font-bold">{property.code}</span>
                  <Badge>{property.type_label}</Badge>
                </div>
                {property.price && (
                  <p className="text-primary font-semibold mt-2">{formatPrice(property.price)}</p>
                )}
                {property.city && <p className="text-sm text-muted mt-1">{property.city}</p>}
              </Card>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}
