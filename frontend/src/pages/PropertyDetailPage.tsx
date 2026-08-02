import { useState } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, Edit, Heart, MapPin, Phone, User, Eye, EyeOff, Building2, QrCode, Send, Copy, Star } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { categoryLabel } from '@/constants/property'
import { EXTRA_FEATURES } from '@/constants/property'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { PropertyShareModal } from '@/components/property/PropertyShareModal'

interface PropertyMedia {
  id: number
  url: string
  is_cover: boolean
}

interface PropertyDetail {
  id: number
  code: string
  type_label: string
  property_category?: string
  property_category_label?: string
  permission_label: string
  status_label: string
  price?: number
  deposit?: number
  rent?: number
  area?: number
  rooms?: number
  building_age?: number
  floor?: number
  total_floors?: number
  province?: string
  city?: string
  district?: string
  neighborhood?: string
  address?: string
  latitude?: number
  longitude?: number
  description?: string
  owner_name?: string
  owner_mobile?: string
  has_parking?: boolean
  has_elevator?: boolean
  has_storage?: boolean
  features?: string[]
  created_at_jalali?: string
  expires_at_jalali?: string
  qr_url?: string
  quality_score?: number
  creator?: { name: string }
  media?: PropertyMedia[]
  cover_image?: PropertyMedia
}

function featureLabel(value: string) {
  return EXTRA_FEATURES.find((f) => f.value === value)?.label ?? value
}

function maskMobile(mobile: string) {
  if (mobile.length < 8) return '***'
  return mobile.slice(0, 4) + '***' + mobile.slice(-3)
}

export function PropertyDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [activeImage, setActiveImage] = useState(0)
  const [showMobile, setShowMobile] = useState(false)
  const [showShare, setShowShare] = useState(false)
  const [copied, setCopied] = useState(false)

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

  const loadAdCopy = async () => {
    try {
      const { data } = await api.get(`/properties/${id}/share-message`)
      await navigator.clipboard.writeText(data.data.ad_copy)
      setCopied(true)
      setTimeout(() => setCopied(false), 2500)
    } catch { /* ignore */ }
  }

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

  const images = property.media?.length
    ? property.media
    : property.cover_image
      ? [property.cover_image]
      : []

  const currentImage = images[activeImage]

  return (
    <div className="space-y-6 animate-fade-in max-w-5xl mx-auto">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="icon" onClick={() => navigate(-1)}>
          <ArrowRight className="h-5 w-5" />
        </Button>
        <div className="flex-1 min-w-0">
          <h1 className="text-2xl font-bold truncate">{property.code}</h1>
          <p className="text-muted text-sm">
            {property.type_label}
            {property.property_category_label ? ` · ${property.property_category_label}` : ''}
            {' · '}{property.permission_label}
          </p>
        </div>
        <Button variant="outline" size="icon" onClick={() => setShowShare(true)} title="ارسال در واتساپ، روبیکا، بله و تلگرام">
          <Send className="h-4 w-4" />
        </Button>
        <Button variant="outline" size="icon" onClick={loadAdCopy} title="کپی متن آگهی">
          <Copy className="h-4 w-4" />
        </Button>
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
        <div className="lg:col-span-2 space-y-4">
          <Card className="!p-0 overflow-hidden">
            <div className="aspect-video bg-gradient-to-br from-primary/20 to-accent/20 flex items-center justify-center relative">
              {currentImage ? (
                <img src={currentImage.url} alt={property.code} className="w-full h-full object-cover" />
              ) : (
                <Building2 className="h-20 w-20 text-primary/30" />
              )}
            </div>
            {images.length > 1 && (
              <div className="flex gap-2 p-3 overflow-x-auto">
                {images.map((img, i) => (
                  <button
                    key={img.id}
                    type="button"
                    onClick={() => setActiveImage(i)}
                    className={`shrink-0 w-20 h-14 rounded-lg overflow-hidden border-2 ${i === activeImage ? 'border-primary' : 'border-transparent'}`}
                  >
                    <img src={img.url} alt="" className="w-full h-full object-cover" />
                  </button>
                ))}
              </div>
            )}
            <CardContent className="pt-6 space-y-4">
              <div className="flex flex-wrap gap-2">
                <Badge>{property.status_label}</Badge>
                {property.property_category && (
                  <Badge variant="outline">{property.property_category_label || categoryLabel(property.property_category)}</Badge>
                )}
                {property.has_parking && <Badge variant="outline">پارکینگ</Badge>}
                {property.has_elevator && <Badge variant="outline">آسانسور</Badge>}
                {property.has_storage && <Badge variant="outline">انباری</Badge>}
                {property.features?.map((f) => (
                  <Badge key={f} variant="outline">{featureLabel(f)}</Badge>
                ))}
              </div>
              {property.price != null && (
                <p className="text-2xl font-bold text-primary">{formatPrice(property.price)}</p>
              )}
              {(property.deposit || property.rent) && (
                <p className="text-lg text-muted">
                  {property.deposit ? `رهن: ${formatPrice(property.deposit)}` : ''}
                  {property.rent ? ` · اجاره: ${formatPrice(property.rent)}` : ''}
                </p>
              )}
              {property.description && (
                <p className="text-sm leading-relaxed whitespace-pre-wrap">{property.description}</p>
              )}
            </CardContent>
          </Card>

          {property.latitude && property.longitude && (
            <Card className="overflow-hidden">
              <CardHeader><CardTitle className="text-base flex items-center gap-2"><MapPin className="h-4 w-4" /> موقعیت روی نقشه</CardTitle></CardHeader>
              <div className="h-56">
                <iframe
                  title="map"
                  className="w-full h-full border-0"
                  src={`https://www.openstreetmap.org/export/embed.html?bbox=${property.longitude - 0.01}%2C${property.latitude - 0.01}%2C${property.longitude + 0.01}%2C${property.latitude + 0.01}&layer=mapnik&marker=${property.latitude}%2C${property.longitude}`}
                />
              </div>
            </Card>
          )}
        </div>

        <div className="space-y-4">
          {property.quality_score != null && (
            <Card>
              <CardHeader>
                <CardTitle className="text-base flex items-center gap-2">
                  <Star className="h-4 w-4 text-warning" /> امتیاز کیفیت فایل
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-3">
                  <div className="text-3xl font-bold text-primary">{property.quality_score}</div>
                  <div className="flex-1 h-2 rounded-full bg-card-border overflow-hidden">
                    <div className="h-full bg-gradient-to-l from-primary to-accent rounded-full" style={{ width: `${property.quality_score}%` }} />
                  </div>
                </div>
                <p className="text-xs text-muted mt-2">بر اساس تصاویر، توضیحات، موقعیت و اطلاعات تماس</p>
              </CardContent>
            </Card>
          )}

          <Card>
            <CardHeader><CardTitle className="text-base">مشخصات</CardTitle></CardHeader>
            <CardContent className="space-y-3 text-sm">
              {property.area && <Row label="متراژ" value={`${property.area} متر`} />}
              {property.rooms != null && <Row label="خواب" value={String(property.rooms)} />}
              {property.floor != null && <Row label="طبقه" value={String(property.floor)} />}
              {property.total_floors != null && <Row label="کل طبقات" value={String(property.total_floors)} />}
              {property.building_age != null && <Row label="سن بنا" value={`${property.building_age} سال`} />}
              {property.province && <Row label="استان" value={property.province} />}
              {property.city && <Row label="شهر" value={property.city} />}
              {property.district && <Row label="منطقه" value={property.district} />}
              {property.neighborhood && <Row label="محله" value={property.neighborhood} />}
              {property.address && <Row label="آدرس" value={property.address} />}
              {property.creator?.name && <Row label="ثبت‌کننده" value={property.creator.name} />}
              {property.created_at_jalali && <Row label="تاریخ ثبت" value={property.created_at_jalali} />}
              {property.expires_at_jalali && <Row label="انقضا" value={property.expires_at_jalali} />}
            </CardContent>
          </Card>

          {(property.owner_name || property.owner_mobile) && (
            <Card>
              <CardHeader>
                <CardTitle className="text-base flex items-center gap-2">
                  <User className="h-4 w-4" /> مالک
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-2 text-sm">
                {property.owner_name && <p>{property.owner_name}</p>}
                {property.owner_mobile && (
                  <div className="flex items-center justify-between gap-2">
                    <p className="flex items-center gap-2 text-muted" dir="ltr">
                      <Phone className="h-3 w-3 shrink-0" />
                      {showMobile ? property.owner_mobile : maskMobile(property.owner_mobile)}
                    </p>
                    <button
                      type="button"
                      onClick={() => setShowMobile((v) => !v)}
                      className="text-muted hover:text-foreground p-1"
                      title={showMobile ? 'مخفی کردن' : 'نمایش شماره'}
                    >
                      {showMobile ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
                  </div>
                )}
              </CardContent>
            </Card>
          )}

          {property.qr_url && (
            <Card>
              <CardHeader>
                <CardTitle className="text-base flex items-center gap-2">
                  <QrCode className="h-4 w-4" /> کد QR رهگیری
                </CardTitle>
              </CardHeader>
              <CardContent className="flex flex-col items-center gap-3">
                <img
                  src={`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(property.qr_url)}`}
                  alt="QR"
                  className="rounded-xl border border-card-border bg-white p-2"
                />
                <p className="text-xs text-muted text-center break-all">{property.qr_url}</p>
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

      {copied && (
        <div className="fixed bottom-4 left-1/2 -translate-x-1/2 bg-success/90 text-white px-4 py-2 rounded-full text-sm shadow-lg z-40">
          متن آگهی کپی شد
        </div>
      )}

      {showShare && id && (
        <PropertyShareModal propertyId={Number(id)} onClose={() => setShowShare(false)} />
      )}
    </div>
  )
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4">
      <span className="text-muted shrink-0">{label}</span>
      <span className="text-left">{value}</span>
    </div>
  )
}
