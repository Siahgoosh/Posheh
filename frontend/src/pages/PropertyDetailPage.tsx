import { useParams, Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  ArrowRight, Star, MapPin, Bed, Maximize, Phone, User, Calendar,
  Car, Building2, Layers, Edit, Thermometer, Wind,
} from 'lucide-react'
import api from '@/lib/api'
import { formatPrice, formatNumber } from '@/lib/utils'
import { PROPERTY_OPTIONS } from '@/lib/propertyOptions'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { PropertyCard, type PropertyItem } from '@/components/PropertyCard'

function labelFor(options: { value: string; label: string }[], value?: string) {
  return options.find((o) => o.value === value)?.label ?? value
}

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

  const cover = property.cover_image?.url
  const images = property.media?.filter((m: { type: string }) => m.type === 'image') ?? []

  return (
    <div className="space-y-6 animate-fade-in max-w-5xl mx-auto">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <Link to="/properties" className="flex items-center gap-2 text-muted hover:text-foreground">
          <ArrowRight className="h-4 w-4" />
          بازگشت
        </Link>
        <div className="flex gap-2">
          <Link to={`/properties/${id}/edit`}>
            <Button variant="secondary"><Edit className="h-4 w-4" />ویرایش</Button>
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
      </div>

      {cover || images.length > 0 ? (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
          <div className="sm:col-span-2 lg:col-span-2 h-64 rounded-2xl overflow-hidden bg-muted/20">
            <img src={cover || images[0]?.url} alt={property.title || property.code} className="w-full h-full object-cover" />
          </div>
          <div className="grid grid-cols-2 gap-2">
            {images.slice(0, 4).map((img: { id: number; url: string }) => (
              <div key={img.id} className="h-32 rounded-xl overflow-hidden bg-muted/20">
                <img src={img.url} alt="" className="w-full h-full object-cover" />
              </div>
            ))}
          </div>
        </div>
      ) : (
        <div className="h-56 rounded-2xl bg-gradient-to-br from-primary/30 via-accent/20 to-primary/10 flex items-center justify-center">
          <span className="text-6xl font-bold text-primary/30">{property.code}</span>
        </div>
      )}

      <div className="flex flex-wrap items-center gap-3">
        <h1 className="text-3xl font-bold">{property.title || property.code}</h1>
        <Badge className="text-sm">{property.type_label}</Badge>
        <Badge variant="outline">{property.status_label}</Badge>
        <Badge variant="outline">{property.permission_label}</Badge>
        {property.is_negotiable && <Badge variant="success">قابل مذاکره</Badge>}
      </div>

      <p className="text-muted text-sm">کد فایل: {property.code}</p>

      <div className="flex flex-wrap gap-4 items-baseline">
        {property.price && <p className="text-2xl font-bold text-primary">{formatPrice(property.price)}</p>}
        {property.price_per_meter && (
          <p className="text-muted">هر متر: {formatPrice(property.price_per_meter)}</p>
        )}
        {property.rent && <p className="text-lg">اجاره: {formatPrice(property.rent)}</p>}
        {property.deposit && <p className="text-lg">رهن: {formatPrice(property.deposit)}</p>}
      </div>

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {property.area && (
          <SpecCard icon={Maximize} label="متراژ بنا" value={`${formatNumber(property.area)} متر`} />
        )}
        {property.land_area && (
          <SpecCard icon={Layers} label="متراژ زمین" value={`${formatNumber(property.land_area)} متر`} />
        )}
        {property.rooms && (
          <SpecCard icon={Bed} label="خواب" value={`${formatNumber(property.rooms)} اتاق`} />
        )}
        {property.city && (
          <SpecCard icon={MapPin} label="موقعیت" value={`${property.city}${property.district ? `، ${property.district}` : ''}`} />
        )}
        {property.floor != null && (
          <SpecCard icon={Building2} label="طبقه" value={`${property.floor} از ${property.total_floors ?? '—'}`} />
        )}
        {property.building_age != null && (
          <SpecCard icon={Calendar} label="سن بنا" value={`${property.building_age} سال`} />
        )}
        {property.has_parking && <SpecCard icon={Car} label="پارکینگ" value="دارد" />}
        {property.heating_type && <SpecCard icon={Thermometer} label="گرمایش" value={property.heating_type} />}
        {property.cooling_type && <SpecCard icon={Wind} label="سرمایش" value={property.cooling_type} />}
        {property.expires_at_jalali && (
          <SpecCard icon={Calendar} label="انقضای فایل" value={property.expires_at_jalali} />
        )}
      </div>

      <div className="flex flex-wrap gap-2">
        {property.building_type && (
          <Badge variant="outline">{labelFor(PROPERTY_OPTIONS.buildingTypes, property.building_type)}</Badge>
        )}
        {property.deed_type && (
          <Badge variant="outline">{labelFor(PROPERTY_OPTIONS.deedTypes, property.deed_type)}</Badge>
        )}
        {property.direction && (
          <Badge variant="outline">{labelFor(PROPERTY_OPTIONS.directions, property.direction)}</Badge>
        )}
        {property.renovation_status && (
          <Badge variant="outline">{labelFor(PROPERTY_OPTIONS.renovationStatuses, property.renovation_status)}</Badge>
        )}
        {property.has_elevator && <Badge variant="outline">آسانسور</Badge>}
        {property.has_storage && <Badge variant="outline">انباری</Badge>}
        {property.amenities?.map((a: string) => (
          <Badge key={a} variant="outline">{labelFor(PROPERTY_OPTIONS.amenities, a) ?? a}</Badge>
        ))}
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
            {property.neighborhood && (
              <p className="text-sm text-muted">محله: {property.neighborhood}</p>
            )}
            {property.source && (
              <p className="text-sm text-muted">منبع: {labelFor(PROPERTY_OPTIONS.sources, property.source)}</p>
            )}
            {property.commission_percent && (
              <p className="text-sm text-muted">کمیسیون: {property.commission_percent}%</p>
            )}
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>توضیحات</CardTitle></CardHeader>
          <CardContent>
            <p className="text-muted leading-relaxed whitespace-pre-wrap">{property.description || 'بدون توضیحات'}</p>
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

function SpecCard({ icon: Icon, label, value }: { icon: React.ComponentType<{ className?: string }>; label: string; value: string }) {
  return (
    <Card className="glass !p-4">
      <div className="flex items-center gap-3">
        <Icon className="h-5 w-5 text-primary" />
        <div>
          <p className="text-xs text-muted">{label}</p>
          <p className="font-semibold">{value}</p>
        </div>
      </div>
    </Card>
  )
}
