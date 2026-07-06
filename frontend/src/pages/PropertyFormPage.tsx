import { useState, useRef } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, Save, Upload, Trash2 } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { PROPERTY_OPTIONS } from '@/lib/propertyOptions'

const TABS = [
  { id: 'basic', label: 'اطلاعات اصلی' },
  { id: 'price', label: 'قیمت' },
  { id: 'specs', label: 'مشخصات' },
  { id: 'location', label: 'موقعیت' },
  { id: 'owner', label: 'مالک' },
  { id: 'notes', label: 'توضیحات' },
  { id: 'media', label: 'تصاویر' },
] as const

type TabId = (typeof TABS)[number]['id']

const defaultForm = {
  code: '', title: '', type: 'sale', building_type: 'apartment', deed_type: 'single_deed',
  direction: '', permission: 'office', status: 'active', price: '', price_per_meter: '',
  deposit: '', rent: '', is_negotiable: true, commission_percent: '', source: 'owner_direct',
  area: '', land_area: '', rooms: '', building_age: '', renovation_status: '',
  floor: '', total_floors: '', units_per_floor: '', has_parking: false, has_elevator: false,
  has_storage: false, heating_type: '', cooling_type: '', province: 'تهران', city: '',
  district: '', neighborhood: '', address: '', latitude: '', longitude: '',
  owner_name: '', owner_mobile: '', owner_contact_id: '', description: '', internal_notes: '',
  expires_at: '', amenities: [] as string[],
}

export function PropertyFormPage() {
  const { id } = useParams()
  const isEdit = Boolean(id)
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [tab, setTab] = useState<TabId>('basic')
  const [form, setForm] = useState(defaultForm)
  const [error, setError] = useState('')
  const [uploading, setUploading] = useState(false)

  const { data: propertyData } = useQuery({
    queryKey: ['property-edit', id],
    enabled: isEdit,
    queryFn: async () => {
      const res = await api.get(`/properties/${id}`)
      const p = res.data.data
      setForm({
        ...defaultForm,
        ...Object.fromEntries(Object.entries(p).map(([k, v]) => [k, v ?? ''])),
        price: p.price?.toString() ?? '',
        is_negotiable: p.is_negotiable ?? true,
        amenities: p.amenities ?? [],
      })
      return p
    },
  })

  const mutation = useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      isEdit ? api.put(`/properties/${id}`, data) : api.post('/properties', data),
    onSuccess: (res) => navigate(`/properties/${res.data.data.id}`),
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      const errors = e.response?.data?.errors
      setError(errors ? Object.values(errors).flat().join('، ') : e.response?.data?.message || 'خطا')
    },
  })

  const update = (key: string, value: string | boolean | string[]) =>
    setForm((f) => ({ ...f, [key]: value }))

  const toggleAmenity = (key: string) => {
    setForm((f) => ({
      ...f,
      amenities: f.amenities.includes(key)
        ? f.amenities.filter((a) => a !== key)
        : [...f.amenities, key],
    }))
  }

  const uploadMedia = async (file: File) => {
    if (!id) return
    setUploading(true)
    const fd = new FormData()
    fd.append('file', file)
    fd.append('type', 'image')
    try {
      await api.post(`/properties/${id}/media`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      queryClient.invalidateQueries({ queryKey: ['property-edit', id] })
      queryClient.invalidateQueries({ queryKey: ['property', id] })
    } finally {
      setUploading(false)
    }
  }

  const deleteMedia = async (mediaId: number) => {
    if (!id) return
    await api.delete(`/properties/${id}/media/${mediaId}`)
    queryClient.invalidateQueries({ queryKey: ['property-edit', id] })
    queryClient.invalidateQueries({ queryKey: ['property', id] })
  }

  const media = propertyData?.media ?? []

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    mutation.mutate({
      ...form,
      price: form.price ? parseInt(form.price) : null,
      price_per_meter: form.price_per_meter ? parseInt(form.price_per_meter) : null,
      deposit: form.deposit ? parseInt(form.deposit) : null,
      rent: form.rent ? parseInt(form.rent) : null,
      area: form.area ? parseFloat(form.area) : null,
      land_area: form.land_area ? parseFloat(form.land_area) : null,
      rooms: form.rooms ? parseInt(form.rooms) : null,
      building_age: form.building_age ? parseInt(form.building_age) : null,
      floor: form.floor ? parseInt(form.floor) : null,
      total_floors: form.total_floors ? parseInt(form.total_floors) : null,
      units_per_floor: form.units_per_floor ? parseInt(form.units_per_floor) : null,
      commission_percent: form.commission_percent ? parseFloat(form.commission_percent) : null,
      latitude: form.latitude ? parseFloat(form.latitude) : null,
      longitude: form.longitude ? parseFloat(form.longitude) : null,
      owner_contact_id: form.owner_contact_id ? parseInt(form.owner_contact_id) : null,
      is_negotiable: form.is_negotiable,
    })
  }

  const selectCls = 'flex h-11 w-full rounded-xl border border-card-border bg-white/5 px-4 text-sm'

  return (
    <div className="max-w-4xl mx-auto space-y-6 animate-fade-in pb-12">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => navigate(-1)}><ArrowRight className="h-5 w-5" /></Button>
        <h1 className="text-2xl font-bold">{isEdit ? 'ویرایش ملک' : 'ثبت ملک حرفه‌ای'}</h1>
      </div>

      <div className="flex gap-2 flex-wrap">
        {TABS.map((t) => (
          <button key={t.id} type="button" onClick={() => setTab(t.id)}
            className={`px-4 py-2 rounded-xl text-sm font-medium transition-colors ${tab === t.id ? 'bg-primary/15 text-primary' : 'glass text-muted'}`}>
            {t.label}
          </button>
        ))}
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        {tab === 'basic' && (
          <Card className="glass"><CardHeader><CardTitle>اطلاعات اصلی</CardTitle></CardHeader>
            <CardContent className="grid sm:grid-cols-2 gap-4">
              <Field label="کد ملک *"><Input value={form.code} onChange={(e) => update('code', e.target.value)} required /></Field>
              <Field label="عنوان"><Input value={form.title} onChange={(e) => update('title', e.target.value)} placeholder="آپارتمان ۱۲۰ متری نوساز" /></Field>
              <Field label="نوع معامله *">
                <select className={selectCls} value={form.type} onChange={(e) => update('type', e.target.value)}>
                  {PROPERTY_OPTIONS.types.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                </select>
              </Field>
              <Field label="نوع ساختمان">
                <select className={selectCls} value={form.building_type} onChange={(e) => update('building_type', e.target.value)}>
                  {PROPERTY_OPTIONS.buildingTypes.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                </select>
              </Field>
              <Field label="نوع سند">
                <select className={selectCls} value={form.deed_type} onChange={(e) => update('deed_type', e.target.value)}>
                  {PROPERTY_OPTIONS.deedTypes.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                </select>
              </Field>
              <Field label="جهت">
                <select className={selectCls} value={form.direction} onChange={(e) => update('direction', e.target.value)}>
                  <option value="">—</option>
                  {PROPERTY_OPTIONS.directions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                </select>
              </Field>
              <Field label="سطح دسترسی">
                <select className={selectCls} value={form.permission} onChange={(e) => update('permission', e.target.value)}>
                  {PROPERTY_OPTIONS.permissions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                </select>
              </Field>
              <Field label="منبع فایل">
                <select className={selectCls} value={form.source} onChange={(e) => update('source', e.target.value)}>
                  {PROPERTY_OPTIONS.sources.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                </select>
              </Field>
            </CardContent>
          </Card>
        )}

        {tab === 'price' && (
          <Card className="glass"><CardHeader><CardTitle>قیمت و شرایط</CardTitle></CardHeader>
            <CardContent className="grid sm:grid-cols-2 gap-4">
              <Field label="قیمت کل (تومان)"><Input type="number" dir="ltr" value={form.price} onChange={(e) => update('price', e.target.value)} /></Field>
              <Field label="قیمت هر متر"><Input type="number" dir="ltr" value={form.price_per_meter} onChange={(e) => update('price_per_meter', e.target.value)} /></Field>
              <Field label="رهن (تومان)"><Input type="number" dir="ltr" value={form.deposit} onChange={(e) => update('deposit', e.target.value)} /></Field>
              <Field label="اجاره ماهانه"><Input type="number" dir="ltr" value={form.rent} onChange={(e) => update('rent', e.target.value)} /></Field>
              <Field label="کمیسیون (%)"><Input type="number" dir="ltr" value={form.commission_percent} onChange={(e) => update('commission_percent', e.target.value)} /></Field>
              <label className="flex items-center gap-2 text-sm sm:col-span-2">
                <input type="checkbox" checked={form.is_negotiable} onChange={(e) => update('is_negotiable', e.target.checked)} />
                قیمت قابل مذاکره
              </label>
            </CardContent>
          </Card>
        )}

        {tab === 'specs' && (
          <Card className="glass"><CardHeader><CardTitle>مشخصات فنی</CardTitle></CardHeader>
            <CardContent className="grid sm:grid-cols-2 gap-4">
              <Field label="متراژ بنا"><Input type="number" dir="ltr" value={form.area} onChange={(e) => update('area', e.target.value)} /></Field>
              <Field label="متراژ زمین"><Input type="number" dir="ltr" value={form.land_area} onChange={(e) => update('land_area', e.target.value)} /></Field>
              <Field label="تعداد خواب"><Input type="number" dir="ltr" value={form.rooms} onChange={(e) => update('rooms', e.target.value)} /></Field>
              <Field label="سن بنا"><Input type="number" dir="ltr" value={form.building_age} onChange={(e) => update('building_age', e.target.value)} /></Field>
              <Field label="طبقه"><Input type="number" dir="ltr" value={form.floor} onChange={(e) => update('floor', e.target.value)} /></Field>
              <Field label="کل طبقات"><Input type="number" dir="ltr" value={form.total_floors} onChange={(e) => update('total_floors', e.target.value)} /></Field>
              <Field label="واحد در طبقه"><Input type="number" dir="ltr" value={form.units_per_floor} onChange={(e) => update('units_per_floor', e.target.value)} /></Field>
              <Field label="وضعیت بازسازی">
                <select className={selectCls} value={form.renovation_status} onChange={(e) => update('renovation_status', e.target.value)}>
                  <option value="">—</option>
                  {PROPERTY_OPTIONS.renovationStatuses.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                </select>
              </Field>
              <Field label="گرمایش"><Input value={form.heating_type} onChange={(e) => update('heating_type', e.target.value)} placeholder="پکیج، شوفاژ..." /></Field>
              <Field label="سرمایش"><Input value={form.cooling_type} onChange={(e) => update('cooling_type', e.target.value)} placeholder="اسپلیت، چیلر..." /></Field>
              <div className="sm:col-span-2 flex flex-wrap gap-4">
                {(['has_parking', 'has_elevator', 'has_storage'] as const).map((k) => (
                  <label key={k} className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={form[k]} onChange={(e) => update(k, e.target.checked)} />
                    {{ has_parking: 'پارکینگ', has_elevator: 'آسانسور', has_storage: 'انباری' }[k]}
                  </label>
                ))}
              </div>
              <div className="sm:col-span-2">
                <p className="text-sm text-muted mb-2">امکانات</p>
                <div className="flex flex-wrap gap-2">
                  {PROPERTY_OPTIONS.amenities.map((a) => (
                    <button key={a.value} type="button" onClick={() => toggleAmenity(a.value)}
                      className={`px-3 py-1 rounded-lg text-xs border ${form.amenities.includes(a.value) ? 'bg-primary/20 border-primary text-primary' : 'border-card-border'}`}>
                      {a.label}
                    </button>
                  ))}
                </div>
              </div>
            </CardContent>
          </Card>
        )}

        {tab === 'location' && (
          <Card className="glass"><CardHeader><CardTitle>موقعیت مکانی</CardTitle></CardHeader>
            <CardContent className="grid sm:grid-cols-2 gap-4">
              <Field label="استان"><Input value={form.province} onChange={(e) => update('province', e.target.value)} /></Field>
              <Field label="شهر"><Input value={form.city} onChange={(e) => update('city', e.target.value)} /></Field>
              <Field label="منطقه"><Input value={form.district} onChange={(e) => update('district', e.target.value)} /></Field>
              <Field label="محله"><Input value={form.neighborhood} onChange={(e) => update('neighborhood', e.target.value)} /></Field>
              <Field label="آدرس کامل" className="sm:col-span-2"><Input value={form.address} onChange={(e) => update('address', e.target.value)} /></Field>
              <Field label="عرض جغرافیایی"><Input dir="ltr" value={form.latitude} onChange={(e) => update('latitude', e.target.value)} /></Field>
              <Field label="طول جغرافیایی"><Input dir="ltr" value={form.longitude} onChange={(e) => update('longitude', e.target.value)} /></Field>
            </CardContent>
          </Card>
        )}

        {tab === 'owner' && (
          <Card className="glass"><CardHeader><CardTitle>اطلاعات مالک</CardTitle></CardHeader>
            <CardContent className="grid sm:grid-cols-2 gap-4">
              <Field label="نام مالک"><Input value={form.owner_name} onChange={(e) => update('owner_name', e.target.value)} /></Field>
              <Field label="موبایل مالک"><Input dir="ltr" value={form.owner_mobile} onChange={(e) => update('owner_mobile', e.target.value)} /></Field>
              <Field label="انقضای فایل"><Input type="date" dir="ltr" value={form.expires_at} onChange={(e) => update('expires_at', e.target.value)} /></Field>
            </CardContent>
          </Card>
        )}

        {tab === 'notes' && (
          <Card className="glass"><CardHeader><CardTitle>توضیحات</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              <Field label="توضیحات عمومی (نمایش به مشتری)">
                <textarea className="w-full rounded-xl border border-card-border bg-white/5 p-3 text-sm min-h-28" value={form.description} onChange={(e) => update('description', e.target.value)} />
              </Field>
              <Field label="یادداشت داخلی (فقط تیم)">
                <textarea className="w-full rounded-xl border border-card-border bg-white/5 p-3 text-sm min-h-28" value={form.internal_notes} onChange={(e) => update('internal_notes', e.target.value)} />
              </Field>
            </CardContent>
          </Card>
        )}

        {tab === 'media' && (
          <Card className="glass">
            <CardHeader><CardTitle>تصاویر و فایل‌ها</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              {!isEdit ? (
                <p className="text-sm text-muted">پس از ثبت ملک می‌توانید تصاویر را آپلود کنید.</p>
              ) : (
                <>
                  <input ref={fileInputRef} type="file" accept="image/*" className="hidden"
                    onChange={(e) => e.target.files?.[0] && uploadMedia(e.target.files[0])} />
                  <Button type="button" variant="secondary" disabled={uploading}
                    onClick={() => fileInputRef.current?.click()}>
                    <Upload className="h-4 w-4" />
                    {uploading ? 'در حال آپلود...' : 'افزودن تصویر'}
                  </Button>
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    {media.map((m: { id: number; url: string; original_name: string }) => (
                      <div key={m.id} className="relative group rounded-xl overflow-hidden border border-card-border">
                        <img src={m.url} alt={m.original_name} className="w-full h-32 object-cover" />
                        <button type="button" onClick={() => deleteMedia(m.id)}
                          className="absolute top-2 left-2 p-1.5 rounded-lg bg-danger/80 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </div>
                    ))}
                    {!media.length && <p className="text-sm text-muted col-span-full">تصویری آپلود نشده</p>}
                  </div>
                </>
              )}
            </CardContent>
          </Card>
        )}

        {error && <p className="text-danger text-sm">{error}</p>}

        <Button type="submit" size="lg" className="w-full" disabled={mutation.isPending}>
          <Save className="h-4 w-4" />
          {mutation.isPending ? 'در حال ذخیره...' : isEdit ? 'ذخیره تغییرات' : 'ثبت ملک'}
        </Button>
      </form>
    </div>
  )
}

function Field({ label, children, className = '' }: { label: string; children: React.ReactNode; className?: string }) {
  return (
    <div className={className}>
      <label className="text-sm text-muted mb-1 block">{label}</label>
      {children}
    </div>
  )
}
