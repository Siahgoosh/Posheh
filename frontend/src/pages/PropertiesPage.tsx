import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Plus, Search, Filter, MapPin, Bed, Maximize } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice, formatNumber } from '@/lib/utils'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

interface Property {
  id: number
  code: string
  type: string
  type_label: string
  property_category_label?: string
  status_label: string
  price?: number
  rent?: number
  deposit?: number
  area?: number
  rooms?: number
  city?: string
  district?: string
  permission_label: string
  created_at_jalali: string
  cover_image?: { url: string }
}

export function PropertiesPage() {
  const [search, setSearch] = useState('')
  const [type, setType] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['properties', search, type],
    queryFn: async () => {
      const params = new URLSearchParams()
      if (search) params.set('q', search)
      if (type) params.set('type', type)
      const res = await api.get(`/properties?${params}`)
      return res.data
    },
  })

  const propertyTypes = [
    { value: '', label: 'همه' },
    { value: 'sale', label: 'فروش' },
    { value: 'rent', label: 'اجاره' },
    { value: 'mortgage', label: 'رهن' },
    { value: 'pre_sale', label: 'پیش‌فروش' },
    { value: 'land', label: 'زمین' },
    { value: 'commercial', label: 'تجاری' },
  ]

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">املاک</h1>
          <p className="text-muted mt-1">
            {formatNumber(data?.meta?.total ?? 0)} ملک ثبت شده
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm" onClick={async () => {
            const res = await api.get('/properties-export', { responseType: 'blob' })
            const url = URL.createObjectURL(res.data)
            const a = document.createElement('a')
            a.href = url
            a.download = 'properties.xlsx'
            a.click()
          }}>اکسل</Button>
          <label className="cursor-pointer">
            <Button variant="outline" size="sm" asChild><span>ایمپورت</span></Button>
            <input type="file" className="hidden" accept=".xlsx,.xls,.csv" onChange={async (e) => {
              const file = e.target.files?.[0]
              if (!file) return
              const body = new FormData()
              body.append('file', file)
              await api.post('/properties-import', body, { headers: { 'Content-Type': 'multipart/form-data' } })
              window.location.reload()
            }} />
          </label>
          <Link to="/properties/new">
            <Button>
              <Plus className="h-4 w-4" />
              ثبت ملک
            </Button>
          </Link>
        </div>
      </div>

      <div className="flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted" />
          <Input
            placeholder="جستجوی سریع..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pr-10"
          />
        </div>
        <div className="flex gap-2 overflow-x-auto pb-1">
          {propertyTypes.map((t) => (
            <Button
              key={t.value}
              variant={type === t.value ? 'default' : 'secondary'}
              size="sm"
              onClick={() => setType(t.value)}
            >
              {t.label}
            </Button>
          ))}
        </div>
      </div>

      {isLoading ? (
        <div className="flex justify-center py-20">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {data?.data?.map((property: Property) => (
            <Link key={property.id} to={`/properties/${property.id}`}>
              <Card className="!p-0 overflow-hidden glass-hover cursor-pointer h-full">
                <div className="h-40 bg-gradient-to-br from-primary/20 to-accent/20 flex items-center justify-center overflow-hidden">
                  {property.cover_image?.url ? (
                    <img src={property.cover_image.url} alt={property.code} className="w-full h-full object-cover" />
                  ) : (
                    <Building2Placeholder />
                  )}
                </div>
                <div className="p-4 space-y-3">
                  <div className="flex items-center justify-between gap-2">
                    <span className="font-bold text-lg">{property.code}</span>
                    <div className="flex gap-1 shrink-0">
                      {property.property_category_label && (
                        <Badge variant="outline" className="text-[10px]">{property.property_category_label}</Badge>
                      )}
                      <Badge>{property.type_label}</Badge>
                    </div>
                  </div>
                  {(property.price || property.rent || property.deposit) && (
                    <p className="text-primary font-semibold">
                      {property.price
                        ? formatPrice(property.price)
                        : property.rent
                          ? `اجاره ${formatPrice(property.rent)}`
                          : property.deposit
                            ? `رهن ${formatPrice(property.deposit)}`
                            : ''}
                    </p>
                  )}
                  <div className="flex items-center gap-4 text-sm text-muted">
                    {property.area && (
                      <span className="flex items-center gap-1">
                        <Maximize className="h-3.5 w-3.5" />
                        {formatNumber(property.area)} متر
                      </span>
                    )}
                    {property.rooms && (
                      <span className="flex items-center gap-1">
                        <Bed className="h-3.5 w-3.5" />
                        {formatNumber(property.rooms)} خواب
                      </span>
                    )}
                  </div>
                  {property.city && (
                    <p className="flex items-center gap-1 text-sm text-muted">
                      <MapPin className="h-3.5 w-3.5" />
                      {property.city}{property.district ? `، ${property.district}` : ''}
                    </p>
                  )}
                  <div className="flex items-center justify-between pt-2 border-t border-card-border">
                    <Badge variant="outline">{property.permission_label}</Badge>
                    <span className="text-xs text-muted">{property.created_at_jalali}</span>
                  </div>
                </div>
              </Card>
            </Link>
          ))}
        </div>
      )}

      {!isLoading && !data?.data?.length && (
        <div className="text-center py-20">
          <Filter className="h-12 w-12 text-muted mx-auto mb-4" />
          <p className="text-muted">ملکی یافت نشد</p>
        </div>
      )}
    </div>
  )
}

function Building2Placeholder() {
  return (
    <svg className="h-16 w-16 text-primary/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
    </svg>
  )
}
