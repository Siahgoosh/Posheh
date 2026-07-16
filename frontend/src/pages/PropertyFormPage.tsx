import { useEffect, useMemo, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, Globe } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { DynamicFilingFields } from '@/components/filing/DynamicFilingFields'
import {
  PropertyMediaUploader,
  PendingImagesPicker,
  uploadPendingImages,
  type PropertyMediaItem,
} from '@/components/property/PropertyMediaUploader'
import { usePlanFeature } from '@/components/SubscriptionGuard'
import { useAuthStore } from '@/stores/auth'
import type { FilingFieldGroups, FilingFormValues, FilingSchema } from '@/lib/filing'

const defaultForm: FilingFormValues = {
  code: '',
  title: '',
  type: 'sale',
  property_category: 'apartment',
  permission: 'office',
  status: 'active',
  owner_name: '',
  owner_mobile: '',
  contact_phone_2: '',
  province: '',
  city: '',
  features: [],
  tags: [],
  show_on_website: false,
}

function toPayload(form: FilingFormValues) {
  const payload: Record<string, unknown> = { ...form }
  ;['price', 'deposit', 'rent'].forEach((k) => {
    if (payload[k]) payload[k] = parseInt(String(payload[k]))
  })
  ;['area', 'latitude', 'longitude'].forEach((k) => {
    if (payload[k]) payload[k] = parseFloat(String(payload[k]))
  })
  ;['rooms', 'building_age', 'floor', 'total_floors'].forEach((k) => {
    if (payload[k]) payload[k] = parseInt(String(payload[k]))
  })
  if (Array.isArray(payload.features) && !payload.features.length) payload.features = null
  if (Array.isArray(payload.tags) && !payload.tags.length) payload.tags = null
  return payload
}

const sectionLabels: Record<string, string> = {
  shared: 'اطلاعات عمومی',
  owner: 'مالک',
  location: 'موقعیت مکانی',
  property: 'مشخصات ملک',
  transaction: 'شرایط معامله',
  amenities: 'امکانات',
  documents: 'سند و مدارک',
  notes: 'توضیحات و برچسب',
}

export function PropertyFormPage() {
  const { id } = useParams<{ id: string }>()
  const isEdit = !!id
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [form, setForm] = useState<FilingFormValues>(defaultForm)
  const [media, setMedia] = useState<PropertyMediaItem[]>([])
  const [pendingImages, setPendingImages] = useState<File[]>([])
  const [error, setError] = useState('')
  const hasWebsite = usePlanFeature('website_listing')
  const { user } = useAuthStore()
  const isManager = user?.role === 'office_manager' || user?.role === 'super_admin'

  const category = String(form.property_category || 'apartment')
  const transaction = String(form.type || 'sale')

  const { data: schema } = useQuery({
    queryKey: ['filing-schema'],
    queryFn: async () => (await api.get('/filing/schema')).data.data as FilingSchema,
  })

  const { data: fieldGroups } = useQuery({
    queryKey: ['filing-fields', category, transaction],
    queryFn: async () => {
      const res = await api.get('/filing/fields', {
        params: { property_category: category, transaction_type: transaction },
      })
      return res.data.data as FilingFieldGroups
    },
    enabled: !!category && !!transaction,
  })

  const { data: property, isLoading } = useQuery({
    queryKey: ['property', id],
    queryFn: async () => (await api.get(`/properties/${id}`)).data.data,
    enabled: isEdit,
  })

  useEffect(() => {
    if (!property) return
    const fd = property.filing_data || {}
    setForm({
      ...defaultForm,
      ...property,
      ...(fd.specs || {}),
      ...(fd.transaction || {}),
      ...(fd.owner || {}),
      ...(fd.location || {}),
      features: property.features || [],
      tags: property.tags || [],
      show_on_website: !!property.show_on_website,
      price: property.price != null ? String(property.price) : '',
      deposit: property.deposit != null ? String(property.deposit) : '',
      rent: property.rent != null ? String(property.rent) : '',
      area: property.area != null ? String(property.area) : '',
    })
    setMedia(property.media || [])
  }, [property])

  const mutation = useMutation({
    mutationFn: async (data: Record<string, unknown>) => {
      if (isEdit) return api.put(`/properties/${id}`, data)
      const res = await api.post('/properties', data)
      const propertyId = res.data.data.id
      if (pendingImages.length) await uploadPendingImages(propertyId, pendingImages)
      return res
    },
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: ['properties'] })
      navigate(`/properties/${isEdit ? id : res.data.data.id}`)
    },
    onError: (err: unknown) => {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      const errors = axiosErr.response?.data?.errors
      setError(errors ? Object.values(errors).flat().join('، ') : axiosErr.response?.data?.message || 'خطا در ثبت ملک')
    },
  })

  const update = (key: string, value: string | boolean | string[]) =>
    setForm((f) => ({ ...f, [key]: value }))

  const sections = useMemo(() => {
    if (!fieldGroups) return []
    return (Object.keys(sectionLabels) as (keyof FilingFieldGroups)[])
      .map((key) => ({ key, label: sectionLabels[key], fields: fieldGroups[key] || [] }))
      .filter((s) => s.fields.length > 0)
  }, [fieldGroups])

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
          <h1 className="text-2xl font-bold">{isEdit ? 'ویرایش فایل' : 'ثبت فایل جدید'}</h1>
          <p className="text-sm text-muted mt-1">فرم پویا بر اساس نوع ملک و معامله</p>
        </div>
      </div>

      <form
        onSubmit={(e) => {
          e.preventDefault()
          setError('')
          mutation.mutate(toPayload(form))
        }}
        className="space-y-6"
      >
        {sections.map((section) => (
          <Card key={section.key}>
            <CardHeader><CardTitle>{section.label}</CardTitle></CardHeader>
            <CardContent>
              <DynamicFilingFields fields={section.fields} values={form} onChange={update} />
            </CardContent>
          </Card>
        ))}

        <Card>
          <CardHeader><CardTitle>تصاویر فایل</CardTitle></CardHeader>
          <CardContent>
            {isEdit && id ? (
              <PropertyMediaUploader propertyId={parseInt(id)} media={media} onChange={setMedia} />
            ) : (
              <PendingImagesPicker files={pendingImages} onChange={setPendingImages} />
            )}
          </CardContent>
        </Card>

        {hasWebsite && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Globe className="h-5 w-5 text-primary" /> نمایش در وبسایت
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <label className="flex items-start gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  checked={!!form.show_on_website}
                  onChange={(e) => update('show_on_website', e.target.checked)}
                  className="mt-1 rounded border-card-border accent-primary"
                />
                <span className="text-sm">نمایش در وبسایت اختصاصی دفتر</span>
              </label>
              {form.show_on_website && !isManager && (
                <p className="text-xs text-warning">پس از تأیید مدیر منتشر می‌شود.</p>
              )}
            </CardContent>
          </Card>
        )}

        {error && <p className="text-danger text-sm">{error}</p>}

        <Button type="submit" size="lg" className="w-full" disabled={mutation.isPending || !fieldGroups}>
          {mutation.isPending ? 'در حال ذخیره...' : isEdit ? 'ذخیره تغییرات' : 'ثبت فایل'}
        </Button>

        {!schema && <p className="text-center text-muted text-sm">در حال بارگذاری schema...</p>}
      </form>
    </div>
  )
}
