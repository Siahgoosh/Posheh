import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { format as formatJalali } from 'date-fns-jalali/format'
import { JalaliDateTimeInput } from '@/components/JalaliDateInput'
import { Calendar, Plus, ChevronRight, ChevronLeft, Clock } from 'lucide-react'
import api from '@/lib/api'
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
  customer?: { id: number; name: string }
}

const statusLabel: Record<string, string> = {
  scheduled: 'برنامه‌ریزی‌شده',
  completed: 'انجام‌شده',
  cancelled: 'لغو',
}

export function VisitsPage() {
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
            <JalaliDateTimeInput
              value={form.visit_at}
              onChange={(visit_at) => setForm((f) => ({ ...f, visit_at }))}
              className="sm:col-span-2"
            />
            <Input placeholder="یادداشت" value={form.notes} onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))} className="sm:col-span-2" />
          </div>
          <Button onClick={() => createMutation.mutate()} disabled={!form.property_id || !form.visit_at}>ثبت بازدید</Button>
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
