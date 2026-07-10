import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useMutation } from '@tanstack/react-query'
import { ArrowRight } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

export function PropertyFormPage() {
  const navigate = useNavigate()
  const [form, setForm] = useState({
    code: '',
    type: 'sale',
    permission: 'office',
    owner_name: '',
    owner_mobile: '',
    price: '',
    deposit: '',
    rent: '',
    area: '',
    rooms: '',
    latitude: '',
    longitude: '',
    expires_at: '',
    city: '',
    district: '',
    address: '',
    description: '',
    has_parking: false,
    has_elevator: false,
    has_storage: false,
  })
  const [error, setError] = useState('')

  const mutation = useMutation({
    mutationFn: (data: Record<string, unknown>) => api.post('/properties', data),
    onSuccess: (res) => navigate(`/properties/${res.data.data.id}`),
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

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    mutation.mutate({
      ...form,
      price: form.price ? parseInt(form.price) : null,
      deposit: form.deposit ? parseInt(form.deposit) : null,
      rent: form.rent ? parseInt(form.rent) : null,
      area: form.area ? parseFloat(form.area) : null,
      rooms: form.rooms ? parseInt(form.rooms) : null,
      latitude: form.latitude ? parseFloat(form.latitude) : null,
      longitude: form.longitude ? parseFloat(form.longitude) : null,
      expires_at: form.expires_at || null,
    })
  }

  const update = (key: string, value: string | boolean) =>
    setForm((f) => ({ ...f, [key]: value }))

  return (
    <div className="max-w-2xl mx-auto space-y-6 animate-fade-in">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => navigate(-1)}>
          <ArrowRight className="h-5 w-5" />
        </Button>
        <h1 className="text-2xl font-bold">ثبت ملک جدید</h1>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <Card>
          <CardHeader><CardTitle>اطلاعات اصلی</CardTitle></CardHeader>
          <CardContent className="grid sm:grid-cols-2 gap-4">
            <div>
              <label className="text-sm text-muted mb-1 block">کد ملک *</label>
              <Input value={form.code} onChange={(e) => update('code', e.target.value)} required />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">نوع معامله *</label>
              <select
                value={form.type}
                onChange={(e) => update('type', e.target.value)}
                className="flex h-11 w-full rounded-xl border border-card-border bg-white/5 px-4 text-sm"
              >
                <option value="sale">فروش</option>
                <option value="rent">اجاره</option>
                <option value="mortgage">رهن</option>
                <option value="pre_sale">پیش‌فروش</option>
                <option value="land">زمین</option>
                <option value="garden">باغ</option>
                <option value="commercial">تجاری</option>
                <option value="warehouse">انبار</option>
                <option value="partnership">مشارکت</option>
              </select>
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">سطح دسترسی</label>
              <select
                value={form.permission}
                onChange={(e) => update('permission', e.target.value)}
                className="flex h-11 w-full rounded-xl border border-card-border bg-white/5 px-4 text-sm"
              >
                <option value="office">دفتری</option>
                <option value="team">تیمی</option>
                <option value="private">خصوصی</option>
                <option value="manager_only">فقط مدیر</option>
              </select>
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">قیمت (تومان)</label>
              <Input type="number" value={form.price} onChange={(e) => update('price', e.target.value)} dir="ltr" />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">رهن (تومان)</label>
              <Input type="number" value={form.deposit} onChange={(e) => update('deposit', e.target.value)} dir="ltr" />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">اجاره (تومان)</label>
              <Input type="number" value={form.rent} onChange={(e) => update('rent', e.target.value)} dir="ltr" />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">انقضای آگهی</label>
              <Input type="date" value={form.expires_at} onChange={(e) => update('expires_at', e.target.value)} dir="ltr" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>مشخصات</CardTitle></CardHeader>
          <CardContent className="grid sm:grid-cols-2 gap-4">
            <div>
              <label className="text-sm text-muted mb-1 block">متراژ</label>
              <Input type="number" value={form.area} onChange={(e) => update('area', e.target.value)} dir="ltr" />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">تعداد خواب</label>
              <Input type="number" value={form.rooms} onChange={(e) => update('rooms', e.target.value)} dir="ltr" />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">شهر</label>
              <Input value={form.city} onChange={(e) => update('city', e.target.value)} />
            </div>
            <div>
              <label className="text-sm text-muted mb-1 block">محله</label>
              <Input value={form.district} onChange={(e) => update('district', e.target.value)} />
            </div>
            <div className="sm:col-span-2">
              <label className="text-sm text-muted mb-1 block">آدرس</label>
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
              <div className="sm:col-span-2 rounded-xl overflow-hidden border border-card-border h-48">
                <iframe
                  title="map"
                  className="w-full h-full"
                  src={`https://www.openstreetmap.org/export/embed.html?bbox=${parseFloat(form.longitude) - 0.01}%2C${parseFloat(form.latitude) - 0.01}%2C${parseFloat(form.longitude) + 0.01}%2C${parseFloat(form.latitude) + 0.01}&layer=mapnik&marker=${form.latitude}%2C${form.longitude}`}
                />
              </div>
            )}
            <div className="sm:col-span-2">
              <label className="text-sm text-muted mb-1 block">توضیحات</label>
              <textarea
                value={form.description}
                onChange={(e) => update('description', e.target.value)}
                rows={4}
                className="flex w-full rounded-xl border border-card-border bg-white/5 px-4 py-3 text-sm resize-none"
              />
            </div>
            <div className="sm:col-span-2 flex gap-6">
              {(['has_parking', 'has_elevator', 'has_storage'] as const).map((key) => (
                <label key={key} className="flex items-center gap-2 text-sm cursor-pointer">
                  <input
                    type="checkbox"
                    checked={form[key]}
                    onChange={(e) => update(key, e.target.checked)}
                    className="rounded border-card-border"
                  />
                  {{ has_parking: 'پارکینگ', has_elevator: 'آسانسور', has_storage: 'انباری' }[key]}
                </label>
              ))}
            </div>
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
              <Input value={form.owner_mobile} onChange={(e) => update('owner_mobile', e.target.value)} dir="ltr" />
            </div>
          </CardContent>
        </Card>

        {error && <p className="text-danger text-sm">{error}</p>}

        <Button type="submit" size="lg" className="w-full" disabled={mutation.isPending}>
          {mutation.isPending ? 'در حال ثبت...' : 'ثبت ملک'}
        </Button>
      </form>
    </div>
  )
}
