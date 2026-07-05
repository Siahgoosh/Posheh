import { useParams, Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, Star, MapPin, Bed, Maximize, Phone, User, Calendar } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice, formatNumber } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { PropertyCard, type PropertyItem } from '@/components/PropertyCard'

export function PropertyDetailPage() {
  const { id } = useParams<{ id: string }>()
  const queryClient = useQueryClient()

  const { data: property, isLoading } = useQuery({
    queryKey: ['property', id],
    queryFn: async () => (await api.get(`/properties/${id}`)).data.data,
    enabled: !!id,
  })

  const { data: similar } = useQuery({
    queryKey: ['property-similar', id],
    queryFn: async () => (await api.get(`/properties/${id}/similar`)).data.data,
    enabled: !!id,
  })

  const favoriteMutation = useMutation({
    mutationFn: async () => api.post(`/properties/${id}/favorite`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['property', id] }),
  })

  if (isLoading) {
    return (
      <div className="flex justify-center py-20">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  if (!property) {
    return <p className="text-center text-muted py-20">ملک یافت نشد</p>
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-5xl mx-auto">
      <div className="flex items-center justify-between">
        <Link to="/properties" className="flex items-center gap-2 text-muted hover:text-foreground">
          <ArrowRight className="h-4 w-4" />
          بازگشت
        </Link>
        <Button
          variant={property.is_favorite ? 'default' : 'secondary'}
          onClick={() => favoriteMutation.mutate()}
          disabled={favoriteMutation.isPending}
        >
          <Star className={`h-4 w-4 ${property.is_favorite ? 'fill-current' : ''}`} />
          {property.is_favorite ? 'حذف از علاقه‌مندی' : 'افزودن به علاقه‌مندی'}
        </Button>
      </div>

      <div className="h-56 rounded-2xl bg-gradient-to-br from-primary/30 via-accent/20 to-primary/10 flex items-center justify-center">
        <span className="text-6xl font-bold text-primary/30">{property.code}</span>
      </div>

      <div className="flex flex-wrap items-center gap-3">
        <h1 className="text-3xl font-bold">{property.code}</h1>
        <Badge className="text-sm">{property.type_label}</Badge>
        <Badge variant="outline">{property.status_label}</Badge>
        <Badge variant="outline">{property.permission_label}</Badge>
      </div>

      {(property.price || property.rent) && (
        <p className="text-2xl font-bold text-primary">{formatPrice(property.price || property.rent)}</p>
      )}

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {property.area && (
          <Card className="glass !p-4">
            <div className="flex items-center gap-3">
              <Maximize className="h-5 w-5 text-primary" />
              <div>
                <p className="text-xs text-muted">متراژ</p>
                <p className="font-semibold">{formatNumber(property.area)} متر</p>
              </div>
            </div>
          </Card>
        )}
        {property.rooms && (
          <Card className="glass !p-4">
            <div className="flex items-center gap-3">
              <Bed className="h-5 w-5 text-primary" />
              <div>
                <p className="text-xs text-muted">اتاق</p>
                <p className="font-semibold">{formatNumber(property.rooms)} خواب</p>
              </div>
            </div>
          </Card>
        )}
        {property.city && (
          <Card className="glass !p-4">
            <div className="flex items-center gap-3">
              <MapPin className="h-5 w-5 text-primary" />
              <div>
                <p className="text-xs text-muted">موقعیت</p>
                <p className="font-semibold">{property.city}{property.district ? `، ${property.district}` : ''}</p>
              </div>
            </div>
          </Card>
        )}
        {property.expires_at_jalali && (
          <Card className="glass !p-4">
            <div className="flex items-center gap-3">
              <Calendar className="h-5 w-5 text-primary" />
              <div>
                <p className="text-xs text-muted">انقضا</p>
                <p className="font-semibold">{property.expires_at_jalali}</p>
              </div>
            </div>
          </Card>
        )}
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader><CardTitle>اطلاعات مالک</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            {property.owner_name && (
              <p className="flex items-center gap-2"><User className="h-4 w-4 text-muted" />{property.owner_name}</p>
            )}
            {property.owner_mobile && (
              <p className="flex items-center gap-2" dir="ltr"><Phone className="h-4 w-4 text-muted" />{property.owner_mobile}</p>
            )}
            {property.address && (
              <p className="flex items-center gap-2"><MapPin className="h-4 w-4 text-muted" />{property.address}</p>
            )}
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>توضیحات</CardTitle></CardHeader>
          <CardContent>
            <p className="text-muted leading-relaxed">{property.description || 'بدون توضیحات'}</p>
          </CardContent>
        </Card>
      </div>

      {similar?.length > 0 && (
        <div>
          <h2 className="text-xl font-bold mb-4">املاک مشابه</h2>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {similar.map((p: PropertyItem) => <PropertyCard key={p.id} property={p} />)}
          </div>
        </div>
      )}
    </div>
  )
}
