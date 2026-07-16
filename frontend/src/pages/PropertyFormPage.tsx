import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectOption } from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  TRANSACTION_TYPES,
  PROPERTY_CATEGORIES,
  PERMISSION_LEVELS,
  EXTRA_FEATURES,
  IRAN_PROVINCES,
  showsSalePrice,
  showsRentFields,
} from '@/constants/property'
import {
  PropertyMediaUploader,
  PendingImagesPicker,
  uploadPendingImages,
  type PropertyMediaItem,
} from '@/components/property/PropertyMediaUploader'
import { usePlanFeature } from '@/components/SubscriptionGuard'
import { useAuthStore } from '@/stores/auth'
import { Globe } from 'lucide-react'

const defaultForm = {
  code: '',
  type: 'sale',
  property_category: 'apartment',
  permission: 'office',
  owner_name: '',
  owner_mobile: '',
  price: '',
  deposit: '',
  rent: '',
  area: '',
  rooms: '',
  building_age: '',
  floor: '',
  total_floors: '',
  province: '',
  city: '',
  district: '',
  neighborhood: '',
  address: '',
  latitude: '',
  longitude: '',
  description: '',
  expires_at: '',
  has_parking: false,
  has_elevator: false,
  has_storage: false,
  show_on_website: false,
  features: [] as string[],
}

type FormState = typeof defaultForm

interface PropertyData {
  id: number
  code: string
  type: string
  property_category?: string
  permission: string
  owner_name?: string
  owner_mobile?: string
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
  expires_at?: string
  has_parking?: boolean
  has_elevator?: boolean
  has_storage?: boolean
  show_on_website?: boolean
  website_status?: string
  features?: string[]
  media?: PropertyMediaItem[]
}

function toForm(data: PropertyData): FormState {
  return {
    code: data.code || '',
    type: data.type || 'sale',
    property_category: data.property_category || 'apartment',
    permission: data.permission || 'office',
    owner_name: data.owner_name || '',
    owner_mobile: data.owner_mobile || '',
    price: data.price != null ? String(data.price) : '',
    deposit: data.deposit != null ? String(data.deposit) : '',
    rent: data.rent != null ? String(data.rent) : '',
    area: data.area != null ? String(data.area) : '',
    rooms: data.rooms != null ? String(data.rooms) : '',
    building_age: data.building_age != null ? String(data.building_age) : '',
    floor: data.floor != null ? String(data.floor) : '',
    total_floors: data.total_floors != null ? String(data.total_floors) : '',
    province: data.province || '',
    city: data.city || '',
    district: data.district || '',
    neighborhood: data.neighborhood || '',
    address: data.address || '',
    latitude: data.latitude != null ? String(data.latitude) : '',
    longitude: data.longitude != null ? String(data.longitude) : '',
    description: data.description || '',
    expires_at: data.expires_at ? data.expires_at.slice(0, 10) : '',
    has_parking: !!data.has_parking,
    has_elevator: !!data.has_elevator,
    has_storage: !!data.has_storage,
    show_on_website: !!data.show_on_website,
    features: data.features || [],
  }
}

function toPayload(form: FormState) {
  return {
    ...form,
    price: form.price ? parseInt(form.price) : null,
    deposit: form.deposit ? parseInt(form.deposit) : null,
    rent: form.rent ? parseInt(form.rent) : null,
    area: form.area ? parseFloat(form.area) : null,
    rooms: form.rooms ? parseInt(form.rooms) : null,
    building_age: form.building_age ? parseInt(form.building_age) : null,
    floor: form.floor ? parseInt(form.floor) : null,
    total_floors: form.total_floors ? parseInt(form.total_floors) : null,
    latitude: form.latitude ? parseFloat(form.latitude) : null,
    longitude: form.longitude ? parseFloat(form.longitude) : null,
    expires_at: form.expires_at || null,
    property_category: form.property_category || null,
    features: form.features.length ? form.features : null,
  }
}

export function PropertyFormPage() {
  const { id } = useParams<{ id: string }>()
  const isEdit = !!id
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [form, setForm] = useState<FormState>(defaultForm)
  const [media, setMedia] = useState<PropertyMediaItem[]>([])
  const [pendingImages, setPendingImages] = useState<File[]>([])
  const [error, setError] = useState('')
  const hasWebsite = usePlanFeature('website_listing')
  const { user } = useAuthStore()
  const isManager = user?.role === 'office_manager' || user?.role === 'super_admin'

  const { data: property, isLoading } = useQuery<PropertyData>({
    queryKey: ['property', id],
    queryFn: async () => {
      const res = await api.get(`/properties/${id}`)
      return res.data.data
    },
    enabled: isEdit,
  })

  useEffect(() => {
    if (property) {
      setForm(toForm(property))
      setMedia(property.media || [])
    }
  }, [property])

  const mutation = useMutation({
    mutationFn: async (data: Record<string, unknown>) => {
      if (isEdit) {
        return api.put(`/properties/${id}`, data)
      }
      const res = await api.post('/properties', data)
      const propertyId = res.data.data.id
      if (pendingImages.length) {
        await uploadPendingImages(propertyId, pendingImages)
      }
      return res
    },
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: ['properties'] })
      const propertyId = isEdit ? id : res.data.data.id
      navigate(`/properties/${propertyId}`)
    },
    onError: (err: unknown) => {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      const errors = axiosErr.response?.data?.errors
      if (errors) {
        setError(Object.values(errors).flat().join('، '))
      } else {
        setError(axiosErr.response?.data?.message || 'خطا در ثبت ملک')
      }
    },
  })

  const update = (key: keyof FormState, value: string | boolean | string[]) =>
    setForm((f) => ({ ...f, [key]: value }))

  const toggleFeature = (value: string) => {
    setForm((f) => ({
      ...f,
      features: f.features.includes(value)
        ? f.features.filter((x) => x !== value)
        : [...f.features, value],
    }))
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    mutation.mutate(toPayload(form))
  }

  if (isEdit && isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  return (
    <div className="max-w-3xl mx-auto space-y-6 animate-fade-in pb-10">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => navigate(-1)}>
          <ArrowRight className="h-5 w-5" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">{isEdit ? 'ویرایش ملک' : 'ثبت ملک جدید'}</h1>
          <p className="text-sm text-muted mt-1">اطلاعات کامل ملک را وارد کنید</p>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <Card>
          <CardHeader><CardTitle>اطلاعات اصلی</CardTitle></CardHeader>
          <CardContent className="grid sm:grid-cols-2 gap-4">
            <div>
              <label className="text-sm text-muted mb-1 block">کد ملک *</label>
              <Input value={form.code} onChange={(e) => update('code', e.target.value)} required dir="ltr" placeholder="A-1024" />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">نوع معامله *</label>
              <Select value={form.type} onChange={(e) => update('type', e.target.value)} required>
                {TRANSACTION_TYPES.map((t) => (
                  <SelectOption key={t.value} value={t.value}>{t.label}</SelectOption>
                ))}
              </Select>
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">نوع ملک</label>
              <Select value={form.property_category} onChange={(e) => update('property_category', e.target.value)}>
                {PROPERTY_CATEGORIES.map((c) => (
                  <SelectOption key={c.value} value={c.value}>{c.label}</SelectOption>
                ))}
              </Select>
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">سطح دسترسی</label>
              <Select value={form.permission} onChange={(e) => update('permission', e.target.value)}>
                {PERMISSION_LEVELS.map((p) => (
                  <SelectOption key={p.value} value={p.value}>{p.label}</SelectOption>
                ))}
              </Select>
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">انقضای آگهی</label>
              <Input type="date" value={form.expires_at} onChange={(e) => update('expires_at', e.target.value)} dir="ltr" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>قیمت و شرایط مالی</CardTitle></CardHeader>
          <CardContent className="grid sm:grid-cols-2 gap-4">
            {showsSalePrice(form.type) && (
              <div className="sm:col-span-2">
                <label className="text-sm text-muted mb-1 block">قیمت کل (تومان)</label>
                <Input type="number" value={form.price} onChange={(e) => update('price', e.target.value)} dir="ltr" placeholder="مثلاً ۵۰۰۰۰۰۰۰۰۰" />
              </div>
            )}
            {showsRentFields(form.type) && (
              <>
                <div>
                  <label className="text-sm text-muted mb-1 block">رهن / ودیعه (تومان)</label>
                  <Input type="number" value={form.deposit} onChange={(e) => update('deposit', e.target.value)} dir="ltr" />
                </div>
                <div>
                  <label className="text-sm text-muted mb-1 block">اجاره ماهانه (تومان)</label>
                  <Input type="number" value={form.rent} onChange={(e) => update('rent', e.target.value)} dir="ltr" />
                </div>
              </>
            )}
            {form.type === 'partnership' && (
              <div className="sm:col-span-2 text-sm text-muted">
                برای مشارکت، قیمت کل یا سهم مالک را در توضیحات بنویسید.
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>مشخصات فنی</CardTitle></CardHeader>
          <CardContent className="grid sm:grid-cols-2 gap-4">
            <div>
              <label className="text-sm text-muted mb-1 block">متراژ (متر مربع)</label>
              <Input type="number" value={form.area} onChange={(e) => update('area', e.target.value)} dir="ltr" />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">تعداد خواب</label>
              <Input type="number" value={form.rooms} onChange={(e) => update('rooms', e.target.value)} dir="ltr" min={0} />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">طبقه</label>
              <Input type="number" value={form.floor} onChange={(e) => update('floor', e.target.value)} dir="ltr" placeholder="مثلاً ۳" />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">تعداد کل طبقات</label>
              <Input type="number" value={form.total_floors} onChange={(e) => update('total_floors', e.target.value)} dir="ltr" />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">سن بنا (سال)</label>
              <Input type="number" value={form.building_age} onChange={(e) => update('building_age', e.target.value)} dir="ltr" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>موقعیت مکانی</CardTitle></CardHeader>
          <CardContent className="grid sm:grid-cols-2 gap-4">
            <div>
              <label className="text-sm text-muted mb-1 block">استان</label>
              <Select value={form.province} onChange={(e) => update('province', e.target.value)}>
                <SelectOption value="">انتخاب استان</SelectOption>
                {IRAN_PROVINCES.map((p) => (
                  <SelectOption key={p} value={p}>{p}</SelectOption>
                ))}
              </Select>
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">شهر</label>
              <Input value={form.city} onChange={(e) => update('city', e.target.value)} />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">منطقه</label>
              <Input value={form.district} onChange={(e) => update('district', e.target.value)} />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">محله</label>
              <Input value={form.neighborhood} onChange={(e) => update('neighborhood', e.target.value)} />
            </div>
            <div className="sm:col-span-2">
              <label className="text-sm text-muted mb-1 block">آدرس کامل</label>
              <Input value={form.address} onChange={(e) => update('address', e.target.value)} />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">عرض جغرافیایی</label>
              <Input value={form.latitude} onChange={(e) => update('latitude', e.target.value)} dir="ltr" placeholder="35.6892" />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">طول جغرافیایی</label>
              <Input value={form.longitude} onChange={(e) => update('longitude', e.target.value)} dir="ltr" placeholder="51.3890" />
            </div>
            {form.latitude && form.longitude && (
              <div className="sm:col-span-2 rounded-xl overflow-hidden border border-card-border h-52">
                <iframe
                  title="map"
                  className="w-full h-full"
                  src={`https://www.openstreetmap.org/export/embed.html?bbox=${parseFloat(form.longitude) - 0.01}%2C${parseFloat(form.latitude) - 0.01}%2C${parseFloat(form.longitude) + 0.01}%2C${parseFloat(form.latitude) + 0.01}&layer=mapnik&marker=${form.latitude}%2C${form.longitude}`}
                />
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>امکانات</CardTitle></CardHeader>
          <CardContent className="space-y-4">
            <div className="flex flex-wrap gap-4">
              {([
                ['has_parking', 'پارکینگ'],
                ['has_elevator', 'آسانسور'],
                ['has_storage', 'انباری'],
              ] as const).map(([key, label]) => (
                <label key={key} className="flex items-center gap-2 text-sm cursor-pointer">
                  <input
                    type="checkbox"
                    checked={form[key]}
                    onChange={(e) => update(key, e.target.checked)}
                    className="rounded border-card-border accent-primary"
                  />
                  {label}
                </label>
              ))}
            </div>
            <div className="flex flex-wrap gap-3">
              {EXTRA_FEATURES.map((f) => (
                <label key={f.value} className="flex items-center gap-2 text-sm cursor-pointer">
                  <input
                    type="checkbox"
                    checked={form.features.includes(f.value)}
                    onChange={() => toggleFeature(f.value)}
                    className="rounded border-card-border accent-primary"
                  />
                  {f.label}
                </label>
              ))}
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">توضیحات تکمیلی</label>
              <textarea
                value={form.description}
                onChange={(e) => update('description', e.target.value)}
                rows={5}
                className="flex w-full rounded-xl border border-card-border bg-background px-4 py-3 text-sm resize-none focus:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary/20"
                placeholder="شرایط ویژه، توضیحات بازدید، نکات مهم..."
              />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>تصاویر ملک</CardTitle></CardHeader>
          <CardContent>
            {isEdit && id ? (
              <PropertyMediaUploader
                propertyId={parseInt(id)}
                media={media}
                onChange={setMedia}
              />
            ) : (
              <PendingImagesPicker files={pendingImages} onChange={setPendingImages} />
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>اطلاعات مالک</CardTitle></CardHeader>
          <CardContent className="grid sm:grid-cols-2 gap-4">
            <div>
              <label className="text-sm text-muted mb-1 block">نام مالک</label>
              <Input value={form.owner_name} onChange={(e) => update('owner_name', e.target.value)} />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">موبایل مالک</label>
              <Input value={form.owner_mobile} onChange={(e) => update('owner_mobile', e.target.value)} dir="ltr" placeholder="09121234567" />
            </div>
            <p className="sm:col-span-2 text-xs text-muted">شماره مالک فقط برای اعضای دفتر نمایش داده می‌شود.</p>
          </CardContent>
        </Card>

        {hasWebsite && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Globe className="h-5 w-5 text-primary" /> نمایش در وبسایت دفتر
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <label className="flex items-start gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  checked={form.show_on_website}
                  onChange={(e) => update('show_on_website', e.target.checked)}
                  className="mt-1 rounded border-card-border accent-primary"
                />
                <span className="text-sm">
                  این فایل در وبسایت اختصاصی دفتر ({'name.posheapp.ir'}) نمایش داده شود.
                </span>
              </label>
              {form.show_on_website && !isManager && (
                <p className="text-xs text-warning">
                  این فایل پس از تأیید مدیر دفتر در وبسایت منتشر می‌شود.
                </p>
              )}
              {form.show_on_website && isManager && (
                <p className="text-xs text-success">
                  به‌عنوان مدیر، این فایل بلافاصله در وبسایت منتشر می‌شود.
                </p>
              )}
            </CardContent>
          </Card>
        )}

        {error && <p className="text-danger text-sm">{error}</p>}

        <Button type="submit" size="lg" className="w-full" disabled={mutation.isPending}>
          {mutation.isPending ? 'در حال ذخیره...' : isEdit ? 'ذخیره تغییرات' : 'ثبت ملک'}
        </Button>
      </form>
    </div>
  )
}
