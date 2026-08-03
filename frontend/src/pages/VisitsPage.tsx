import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { format as formatJalali } from 'date-fns-jalali/format'
import { Calendar, Plus, ChevronRight, ChevronLeft, Clock, Globe, Phone } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { toPersianDigits } from '@/lib/utils'
import { usePlanFeature } from '@/components/SubscriptionGuard'

interface Visit {
  id: number
  visit_at: string
  status: string
  notes?: string
  property?: { id: number; code: string; city?: string }
  customer?: { id: number; name: string }
}

interface WebsiteVisitRequest {
  id: number
  name: string
  mobile: string
  property_code?: string
  preferred_date?: string
  preferred_time?: string
  message?: string
  status: string
  created_at?: string
}

const statusLabel: Record<string, string> = {
  scheduled: 'برنامه‌ریزی‌شده',
  completed: 'انجام‌شده',
  cancelled: 'لغو',
}

export function VisitsPage() {
  const hasWebsite = usePlanFeature('website_listing')
  const [year, setYear] = useState(1404)
  const [month, setMonth] = useState(4)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ property_id: '', customer_id: '', visit_at: '', notes: '' })
  const queryClient = useQueryClient()

  const { data: visits, isLoading } = useQuery({
    queryKey: ['visits', year, month],
    queryFn: async () => (await api.get(`/visits?year=${year}&month=${month}`)).data.data as Visit[],
  })

  const { data: upcoming } = useQuery({
    queryKey: ['visits-upcoming'],
    queryFn: async () => (await api.get('/visits/upcoming')).data.data as Visit[],
  })

  const { data: websiteRequests, refetch: refetchWebsite } = useQuery({
    queryKey: ['office-visit-requests'],
    queryFn: async () => (await api.get('/office/website/visit-requests')).data.data as WebsiteVisitRequest[],
    enabled: hasWebsite,
    refetchInterval: 60_000,
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/visits', {
      property_id: parseInt(form.property_id),
      customer_id: form.customer_id ? parseInt(form.customer_id) : null,
      visit_at: form.visit_at,
      notes: form.notes || null,
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['visits'] })
      queryClient.invalidateQueries({ queryKey: ['visits-upcoming'] })
      setShowForm(false)
    },
  })

  const grouped = (visits ?? []).reduce<Record<string, Visit[]>>((acc, v) => {
    const key = formatJalali(new Date(v.visit_at), 'yyyy/MM/dd')
    acc[key] = acc[key] || []
    acc[key].push(v)
    return acc
  }, {})

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2"><Calendar className="h-6 w-6 text-primary" />تقویم بازدید</h1>
          <p className="text-muted text-sm mt-1">برنامه‌ریزی بازدید با تقویم شمسی</p>
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

      {showForm && (
        <Card className="p-5 space-y-3">
          <div className="grid sm:grid-cols-2 gap-3">
            <Input placeholder="شناسه ملک *" value={form.property_id} onChange={(e) => setForm((f) => ({ ...f, property_id: e.target.value }))} dir="ltr" />
            <Input placeholder="شناسه مشتری (اختیاری)" value={form.customer_id} onChange={(e) => setForm((f) => ({ ...f, customer_id: e.target.value }))} dir="ltr" />
            <Input type="datetime-local" value={form.visit_at} onChange={(e) => setForm((f) => ({ ...f, visit_at: e.target.value }))} dir="ltr" className="sm:col-span-2" />
            <Input placeholder="یادداشت" value={form.notes} onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))} className="sm:col-span-2" />
          </div>
          <Button onClick={() => createMutation.mutate()} disabled={!form.property_id || !form.visit_at}>ثبت بازدید</Button>
        </Card>
      )}

      {hasWebsite && (
        <Card className="p-5 border-primary/25 bg-primary/5">
          <div className="flex items-center justify-between gap-2 mb-3">
            <h2 className="font-semibold flex items-center gap-2">
              <Globe className="h-4 w-4 text-primary" />
              درخواست‌های بازدید از وبسایت
              {websiteRequests?.length ? (
                <Badge variant="outline" className="border-primary text-primary">
                  {toPersianDigits(String(websiteRequests.length))} جدید
                </Badge>
              ) : null}
            </h2>
            <Button variant="ghost" size="sm" onClick={() => refetchWebsite()}>بروزرسانی</Button>
          </div>
          {!websiteRequests?.length ? (
            <p className="text-sm text-muted">هنوز درخواست بازدیدی از وبسایت ثبت نشده است.</p>
          ) : (
            <div className="space-y-2">
              {websiteRequests.map((r) => (
                <div key={r.id} className="flex flex-wrap items-start justify-between gap-3 p-3 rounded-xl bg-background/60 border border-card-border text-sm">
                  <div>
                    <p className="font-medium">{r.name}</p>
                    <p className="text-xs text-muted mt-0.5">
                      {r.property_code ? `ملک کد ${toPersianDigits(r.property_code)}` : 'درخواست عمومی'}
                      {r.preferred_date ? ` · ${r.preferred_date}` : ''}
                      {r.preferred_time ? ` ساعت ${r.preferred_time}` : ''}
                    </p>
                    {r.message && <p className="text-xs text-muted mt-1">{r.message}</p>}
                  </div>
                  <a href={`tel:${r.mobile}`} className="inline-flex items-center gap-1 text-primary shrink-0" dir="ltr">
                    <Phone className="h-3.5 w-3.5" />
                    {toPersianDigits(r.mobile)}
                  </a>
                </div>
              ))}
            </div>
          )}
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
