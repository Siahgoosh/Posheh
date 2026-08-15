import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { format as formatJalali } from 'date-fns-jalali/format'
import { getYear as getJalaliYear } from 'date-fns-jalali/getYear'
import { getMonth as getJalaliMonth } from 'date-fns-jalali/getMonth'
import { Calendar, Plus, ChevronRight, ChevronLeft, Clock, Globe, Check } from 'lucide-react'
import api from '@/lib/api'
import { extractApiError } from '@/lib/apiError'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface Visit {
  id: number
  visit_at: string
  status: string
  notes?: string
  property?: { id: number; code: string; city?: string }
  customer?: { id: number; name: string; mobile?: string }
}

interface InboundRequest {
  id: number
  name: string
  mobile?: string
  preferred_date?: string
  preferred_time?: string
  message?: string
  status: string
  property?: { id: number; code: string } | null
  property_id?: number
}

const statusLabel: Record<string, string> = {
  scheduled: 'برنامه‌ریزی‌شده',
  completed: 'انجام‌شده',
  cancelled: 'لغو',
  converted: 'تبدیل‌شده',
  new: 'جدید',
}

const now = new Date()

export function VisitsPage() {
  const [year, setYear] = useState(() => getJalaliYear(now))
  const [month, setMonth] = useState(() => getJalaliMonth(now) + 1)
  const [showForm, setShowForm] = useState(false)
  const [formError, setFormError] = useState('')
  const [form, setForm] = useState({
    property_code: '',
    customer_name: '',
    customer_mobile: '',
    visit_at: '',
    notes: '',
  })
  const queryClient = useQueryClient()

  const { data: visits, isLoading } = useQuery({
    queryKey: ['visits', year, month],
    queryFn: async () => (await api.get(`/visits?year=${year}&month=${month}`)).data.data as Visit[],
  })

  const { data: upcoming } = useQuery({
    queryKey: ['visits-upcoming'],
    queryFn: async () => (await api.get('/visits/upcoming')).data.data as Visit[],
  })

  const { data: inbound } = useQuery({
    queryKey: ['visits-inbound'],
    queryFn: async () => (await api.get('/visits/inbound')).data.data as InboundRequest[],
  })

  const createMutation = useMutation({
    mutationFn: async () => {
      const props = await api.get(`/properties?q=${encodeURIComponent(form.property_code)}&per_page=5`)
      const list = (props.data.data ?? []) as { id: number; code: string }[]
      const property = list.find((p) => p.code.toLowerCase() === form.property_code.trim().toLowerCase()) || list[0]
      if (!property) throw new Error('ملک با این کد پیدا نشد.')
      return api.post('/visits', {
        property_id: property.id,
        customer_name: form.customer_name || null,
        customer_mobile: form.customer_mobile || null,
        visit_at: form.visit_at,
        notes: form.notes || null,
      })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['visits'] })
      queryClient.invalidateQueries({ queryKey: ['visits-upcoming'] })
      setShowForm(false)
      setFormError('')
      setForm({ property_code: '', customer_name: '', customer_mobile: '', visit_at: '', notes: '' })
    },
    onError: (err) => setFormError(extractApiError(err, 'ثبت بازدید ناموفق بود.')),
  })

  const convertMutation = useMutation({
    mutationFn: (id: number) => api.post(`/visits/inbound/${id}/convert`, {}),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['visits'] })
      queryClient.invalidateQueries({ queryKey: ['visits-upcoming'] })
      queryClient.invalidateQueries({ queryKey: ['visits-inbound'] })
    },
  })

  const grouped = (visits ?? []).reduce<Record<string, Visit[]>>((acc, v) => {
    const key = formatJalali(new Date(v.visit_at), 'yyyy/MM/dd')
    acc[key] = acc[key] || []
    acc[key].push(v)
    return acc
  }, {})

  const inboundCount = inbound?.length ?? 0

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2"><Calendar className="h-6 w-6 text-primary" />تقویم بازدید</h1>
          <p className="text-muted text-sm mt-1">برنامه‌ریزی بازدید — مشاور می‌تواند بازدید ثبت کند؛ درخواست‌های وبسایت/تور اینجا دیده می‌شوند.</p>
        </div>
        <Button onClick={() => setShowForm(!showForm)}><Plus className="h-4 w-4" />بازدید جدید</Button>
      </div>

      <div className="flex items-center justify-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => {
          if (month <= 1) { setYear((y) => y - 1); setMonth(12) } else { setMonth((m) => m - 1) }
        }}>
          <ChevronRight className="h-5 w-5" />
        </Button>
        <span className="font-semibold min-w-[120px] text-center">{year}/{String(month).padStart(2, '0')}</span>
        <Button variant="ghost" size="icon" onClick={() => {
          if (month >= 12) { setYear((y) => y + 1); setMonth(1) } else { setMonth((m) => m + 1) }
        }}>
          <ChevronLeft className="h-5 w-5" />
        </Button>
      </div>

      {inboundCount > 0 && (
        <Card className="p-5 border-accent/30 bg-accent/5 space-y-3">
          <h2 className="font-semibold flex items-center gap-2"><Globe className="h-4 w-4 text-accent" />درخواست‌های وبسایت / تور ({inboundCount})</h2>
          <div className="space-y-2">
            {inbound!.map((r) => (
              <div key={r.id} className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 rounded-xl bg-background/60 text-sm">
                <div>
                  <p className="font-medium">{r.name} {r.mobile ? `— ${r.mobile}` : ''}</p>
                  <p className="text-xs text-muted mt-1">
                    ملک: {r.property?.code || (r.property_id ? `#${r.property_id}` : 'نامشخص')}
                    {(r.preferred_date || r.preferred_time) && ` · ترجیح: ${r.preferred_date || ''} ${r.preferred_time || ''}`}
                  </p>
                  {r.message && <p className="text-xs text-muted mt-1 line-clamp-2">{r.message}</p>}
                </div>
                <Button
                  size="sm"
                  disabled={!r.property?.id && !r.property_id || convertMutation.isPending}
                  onClick={() => convertMutation.mutate(r.id)}
                >
                  <Check className="h-3.5 w-3.5" />ثبت در تقویم
                </Button>
              </div>
            ))}
          </div>
        </Card>
      )}

      {showForm && (
        <Card className="p-5 space-y-3">
          <p className="text-sm text-muted">ثبت بازدید توسط مشاور / مدیر دفتر</p>
          {formError && <p className="text-sm text-danger">{formError}</p>}
          <div className="grid sm:grid-cols-2 gap-3">
            <Input placeholder="کد ملک *" value={form.property_code} onChange={(e) => setForm((f) => ({ ...f, property_code: e.target.value }))} />
            <Input type="datetime-local" value={form.visit_at} onChange={(e) => setForm((f) => ({ ...f, visit_at: e.target.value }))} dir="ltr" />
            <Input placeholder="نام مشتری / بازدیدکننده" value={form.customer_name} onChange={(e) => setForm((f) => ({ ...f, customer_name: e.target.value }))} />
            <Input placeholder="موبایل مشتری" value={form.customer_mobile} onChange={(e) => setForm((f) => ({ ...f, customer_mobile: e.target.value }))} dir="ltr" />
            <Input placeholder="یادداشت" value={form.notes} onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))} className="sm:col-span-2" />
          </div>
          <Button onClick={() => createMutation.mutate()} disabled={!form.property_code || !form.visit_at || createMutation.isPending}>
            ثبت بازدید
          </Button>
        </Card>
      )}

      {upcoming && upcoming.length > 0 && (
        <Card className="p-5 border-primary/20 bg-primary/5">
          <h2 className="font-semibold mb-3 flex items-center gap-2"><Clock className="h-4 w-4 text-primary" />بازدیدهای ۷ روز آینده</h2>
          <div className="space-y-2">
            {upcoming.map((v) => (
              <div key={v.id} className="flex justify-between text-sm p-2 rounded-lg bg-background/50">
                <span>{v.property?.code} — {v.customer?.name || 'بدون مشتری'}</span>
                <span className="text-muted">{formatJalali(new Date(v.visit_at), 'MM/dd HH:mm')}</span>
              </div>
            ))}
          </div>
        </Card>
      )}

      {isLoading ? (
        <div className="flex justify-center py-16"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
      ) : Object.keys(grouped).length === 0 ? (
        <p className="text-center text-muted py-12">بازدیدی در این ماه ثبت نشده</p>
      ) : (
        <div className="space-y-4">
          {Object.entries(grouped).sort().map(([date, items]) => (
            <Card key={date} className="p-4">
              <h3 className="font-medium text-primary mb-3">{date}</h3>
              <div className="space-y-2">
                {items.map((v) => (
                  <div key={v.id} className="flex items-center justify-between p-3 rounded-xl bg-background/50 text-sm">
                    <div>
                      <span className="font-medium">{v.property?.code}</span>
                      {v.customer && <span className="text-muted"> · {v.customer.name}</span>}
                      {v.notes && <p className="text-xs text-muted mt-1">{v.notes}</p>}
                    </div>
                    <div className="text-left">
                      <Badge variant="outline">{statusLabel[v.status] || v.status}</Badge>
                      <p className="text-xs text-muted mt-1">{formatJalali(new Date(v.visit_at), 'HH:mm')}</p>
                    </div>
                  </div>
                ))}
              </div>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
