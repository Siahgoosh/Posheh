import { useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Building2, MapPin } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

export function PropertyPublicPage() {
  const { token } = useParams<{ token: string }>()

  const { data, isLoading, error } = useQuery({
    queryKey: ['public-property', token],
    queryFn: async () => (await api.get(`/p/qr/${token}`)).data,
    enabled: !!token,
  })

  const property = data?.data
  const office = data?.office

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  if (error || !property) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background text-muted">
        ملک یافت نشد
      </div>
    )
  }

  const cover = property.cover_image?.url || property.media?.[0]?.url

  return (
    <div className="min-h-screen bg-background text-foreground">
      <header className="border-b border-card-border glass p-4 text-center">
        <p className="text-sm text-muted">{office?.name || 'پوشه'}</p>
        <h1 className="text-xl font-bold mt-1">{property.code}</h1>
      </header>
      <div className="max-w-lg mx-auto p-4 space-y-4">
        {cover ? (
          <img src={cover} alt={property.code} className="w-full aspect-video object-cover rounded-2xl" />
        ) : (
          <div className="aspect-video rounded-2xl bg-primary/10 flex items-center justify-center">
            <Building2 className="h-16 w-16 text-primary/40" />
          </div>
        )}
        <Card>
          <CardContent className="p-5 space-y-3">
            <div className="flex flex-wrap gap-2">
              <Badge>{property.type_label}</Badge>
              {property.property_category_label && <Badge variant="outline">{property.property_category_label}</Badge>}
            </div>
            {property.price != null && <p className="text-2xl font-bold text-primary">{formatPrice(property.price)}</p>}
            {(property.deposit || property.rent) && (
              <p className="text-muted">رهن {property.deposit ? formatPrice(property.deposit) : '—'} · اجاره {property.rent ? formatPrice(property.rent) : '—'}</p>
            )}
            <div className="text-sm space-y-1 text-muted">
              {property.area && <p>متراژ: {property.area} متر</p>}
              {property.rooms != null && <p>خواب: {property.rooms}</p>}
              {property.city && <p className="flex items-center gap-1"><MapPin className="h-3 w-3" />{property.city}{property.district ? `، ${property.district}` : ''}</p>}
            </div>
            {property.description && <p className="text-sm leading-relaxed">{property.description}</p>}
          </CardContent>
        </Card>
        <p className="text-center text-xs text-muted pb-8">قدرت گرفته از پوشه — posheapp.ir</p>
      </div>
    </div>
  )
}
