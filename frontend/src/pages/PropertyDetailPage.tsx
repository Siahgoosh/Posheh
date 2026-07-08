import { useParams, Link, useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, Edit, Heart, MapPin, Phone, User } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface PropertyDetail {
  id: number
  code: string
  type_label: string
  permission_label: string
  status_label: string
  price?: number
  deposit?: number
  rent?: number
  area?: number
  rooms?: number
  city?: string
  district?: string
  neighborhood?: string
  address?: string
  description?: string
  owner_name?: string
  owner_mobile?: string
  has_parking?: boolean
  has_elevator?: boolean
  has_storage?: boolean
  created_at_jalali?: string
  expires_at_jalali?: string
  creator?: { name: string }
}

export function PropertyDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const { data: property, isLoading, error } = useQuery<PropertyDetail>({
    queryKey: ['property', id],
    queryFn: async () => {
      const res = await api.get(`/properties/${id}`)
      return res.data.data
    },
    enabled: !!id,
  })

  const { data: similar } = useQuery({
    queryKey: ['property-similar', id],
    queryFn: async () => {
      const res = await api.get(`/properties/${id}/similar`)
      return res.data.data as PropertyDetail[]
    },
    enabled: !!id,
  })

  const favoriteMutation = useMutation({
    mutationFn: () => api.post(`/properties/${id}/favorite`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['property', id] }),
  })

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  if (error || !property) {
    return (
      <div className="text-center py-20">
        <p className="text-muted mb-4">ملک یافت نشد</p>
        <Button variant="outline" onClick={() => navigate('/properties')}>بازگشت</Button>
      </div>
    )
  }

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="icon" onClick={() => navigate(-1)}>
          <ArrowRight className="h-5 w-5" />
        </Button>
        <div className="flex-1">
          <h1 className="text-2xl font-bold">{property.code}</h1>
          <p className="text-muted text-sm">{property.type_label} · {property.permission_label}</p>
        </div>
        <Button variant="outline" size="icon" onClick={() => favoriteMutation.mutate()}>
          <Heart className="h-4 w-4" />
        </Button>
        <Link to={`/properties/${id}/edit`}>
          <Button variant="outline">
            <Edit className="h-4 w-4" />
            ویرایش
          </Button>
        </Link>
      </div>

      <div className="grid lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <div className="aspect-video rounded-t-xl bg-gradient-to-br from-primary/20 to-accent/20 flex items-center justify-center">
              <MapPin className="h-16 w-16 text-primary/40" />
            </div>
            <CardContent className="pt-6 space-y-4">
              <div className="flex flex-wrap gap-2">
                <Badge>{property.status_label}</Badge>
                {property.has_parking && <Badge variant="outline">پارکینگ</Badge>}
                {property.has_elevator && <Badge variant="outline">آسانسور</Badge>}
                {property.has_storage && <Badge variant="outline">انباری</Badge>}
              </div>
              {property.price != null && (
                <p className="text-2xl font-bold text-primary">{formatPrice(property.price)}</p>
              )}
              {(property.deposit || property.rent) && (
                <p className="text-muted">
                  {property.deposit ? `رهن: ${formatPrice(property.deposit)}` : ''}
                  {property.rent ? ` · اجاره: ${formatPrice(property.rent)}` : ''}
                </p>
              )}
              {property.description && (
                <p className="text-sm leading-relaxed">{property.description}</p>
              )}
            </CardContent>
          </Card>
        </div>

        <div className="space-y-4">
          <Card>
            <CardHeader><CardTitle className="text-base">مشخصات</CardTitle></CardHeader>
            <CardContent className="space-y-3 text-sm">
              {property.area && <Row label="متراژ" value={`${property.area} متر`} />}
              {property.rooms != null && <Row label="اتاق" value={String(property.rooms)} />}
              {property.city && <Row label="شهر" value={property.city} />}
              {property.district && <Row label="منطقه" value={property.district} />}
              {property.neighborhood && <Row label="محله" value={property.neighborhood} />}
              {property.address && <Row label="آدرس" value={property.address} />}
              {property.created_at_jalali && <Row label="ثبت" value={property.created_at_jalali} />}
              {property.expires_at_jalali && <Row label="انقضا" value={property.expires_at_jalali} />}
            </CardContent>
          </Card>

          {(property.owner_name || property.owner_mobile) && (
            <Card>
              <CardHeader><CardTitle className="text-base flex items-center gap-2"><User className="h-4 w-4" /> مالک</CardTitle></CardHeader>
              <CardContent className="space-y-2 text-sm">
                {property.owner_name && <p>{property.owner_name}</p>}
                {property.owner_mobile && (
                  <p className="flex items-center gap-2 text-muted" dir="ltr">
                    <Phone className="h-3 w-3" /> {property.owner_mobile}
                  </p>
                )}
              </CardContent>
            </Card>
          )}
        </div>
      </div>

      {similar && similar.length > 0 && (
        <Card>
          <CardHeader><CardTitle>املاک مشابه</CardTitle></CardHeader>
          <CardContent className="grid md:grid-cols-2 gap-3">
            {similar.map((p) => (
              <Link key={p.id} to={`/properties/${p.id}`} className="p-3 rounded-xl glass-hover flex justify-between">
                <span className="font-medium">{p.code}</span>
                <span className="text-sm text-muted">{p.type_label}</span>
              </Link>
            ))}
          </CardContent>
        </Card>
      )}
    </div>
  )
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4">
      <span className="text-muted">{label}</span>
      <span className="text-left">{value}</span>
    </div>
  )
}
