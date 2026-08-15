import { useEffect, useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  CalendarDays, Plus, List, Copy, Trash2, Pencil, Bell, Check, X,
  ChevronRight, ChevronLeft, Rocket, Clock, Search,
} from 'lucide-react'
import api from '@/lib/api'
import { cn, toEnglishDigits, toPersianDigits } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { usePlanFeature } from '@/components/SubscriptionGuard'

type ContentItem = {
  id: number
  title: string
  content_type: string
  content_type_label: string
  platforms: string[]
  platform_labels: string[]
  goal?: string
  goal_label?: string
  hook?: string
  body?: string
  cta?: string
  caption?: string
  caption_length?: number
  hashtags: string[]
  visual_idea?: string
  overlay_text?: string
  location?: string
  notes?: string
  status: string
  status_label: string
  scheduled_date?: string
  scheduled_jalali?: string
  scheduled_time?: string
  scheduled_label?: string
  countdown?: string
  reminder_enabled: boolean
  reminder_offset_minutes: number
  reminder_custom_minutes?: number | null
  media: { id: number; url: string; media_type: string; original_name?: string }[]
}

type DayCol = {
  date: string
  jalali_label: string
  weekday: string
  is_today: boolean
  is_friday: boolean
  items: ContentItem[]
}

const emptyForm = () => ({
  title: '',
  topic: '',
  content_type: 'post',
  platforms: ['instagram'] as string[],
  goal: '',
  hook: '',
  body: '',
  cta: '',
  caption: '',
  hashtags: [] as string[],
  hashtagInput: '',
  visual_idea: '',
  overlay_text: '',
  location: '',
  notes: '',
  status: 'scheduled',
  jalali_date: '',
  time: '18:30',
  reminder_enabled: true,
  reminder_offset_minutes: 0,
  reminder_custom_minutes: '' as string | number,
})

export function ContentPlannerPage() {
  const hasFeature = usePlanFeature('content_planner')
  const qc = useQueryClient()
  const [view, setView] = useState<'week' | 'next_week' | 'month' | 'list'>('week')
  const [anchor, setAnchor] = useState<string | undefined>()
  const [showForm, setShowForm] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [detail, setDetail] = useState<ContentItem | null>(null)
  const [form, setForm] = useState(emptyForm)
  const [deleteId, setDeleteId] = useState<number | null>(null)
  const [quickOpen, setQuickOpen] = useState(false)
  const [quickRows, setQuickRows] = useState([{ title: '', scheduled_local: '', time: '18:00', content_type: 'post' }])
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const [formError, setFormError] = useState('')

  const { data: meta } = useQuery({
    queryKey: ['cp-meta'],
    queryFn: async () => (await api.get('/content-planner/meta')).data.data,
  })

  const { data: dash } = useQuery({
    queryKey: ['cp-dashboard'],
    queryFn: async () => (await api.get('/content-planner/dashboard')).data.data,
  })

  const calendarMode = view === 'list' ? 'week' : view
  const { data: calendar, isLoading: calLoading } = useQuery({
    queryKey: ['cp-calendar', calendarMode, anchor],
    queryFn: async () => (await api.get('/content-planner/calendar', { params: { mode: calendarMode, anchor } })).data.data,
    enabled: view !== 'list',
  })

  const { data: listData, isLoading: listLoading } = useQuery({
    queryKey: ['cp-list', search, statusFilter],
    queryFn: async () => (await api.get('/content-planner', { params: { q: search || undefined, status: statusFilter } })).data,
    enabled: view === 'list',
  })

  const invalidate = () => {
    qc.invalidateQueries({ queryKey: ['cp-dashboard'] })
    qc.invalidateQueries({ queryKey: ['cp-calendar'] })
    qc.invalidateQueries({ queryKey: ['cp-list'] })
  }

  const [saving, setSaving] = useState(false)

  const saveWithStatus = (status: string) => {
    setFormError('')
    setSaving(true)
    const payload: Record<string, unknown> = {
      ...form,
      status,
      hashtags: form.hashtags,
      reminder_custom_minutes: form.reminder_custom_minutes === '' ? null : Number(form.reminder_custom_minutes),
      jalali_date: toEnglishDigits(form.jalali_date),
      time: toEnglishDigits(form.time),
    }
    delete (payload as { hashtagInput?: string }).hashtagInput
    const req = editingId
      ? api.put(`/content-planner/${editingId}`, payload)
      : api.post('/content-planner', payload)
    req.then(() => {
      setShowForm(false)
      setEditingId(null)
      setForm(emptyForm())
      invalidate()
    }).catch((err: unknown) => {
      const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      const first = e.response?.data?.errors
        ? Object.values(e.response.data.errors)[0]?.[0]
        : e.response?.data?.message
      setFormError(first || 'ذخیره محتوا انجام نشد. لطفاً دوباره تلاش کنید.')
    }).finally(() => setSaving(false))
  }

  const actionMutation = useMutation({
    mutationFn: async ({ id, action }: { id: number; action: string }) => {
      if (action === 'delete') return api.delete(`/content-planner/${id}`)
      return api.post(`/content-planner/${id}/${action}`)
    },
    onSuccess: () => {
      setDeleteId(null)
      setDetail(null)
      invalidate()
    },
  })

  const quickMutation = useMutation({
    mutationFn: async () => {
      const items = quickRows
        .filter((r) => r.title.trim())
        .map((r) => ({
          title: r.title,
          content_type: r.content_type,
          platforms: ['instagram'],
          scheduled_local: r.scheduled_local.includes('T')
            ? r.scheduled_local.replace('T', ' ')
            : `${r.scheduled_local} ${toEnglishDigits(r.time)}`,
          time: toEnglishDigits(r.time),
          reminder_enabled: true,
          reminder_offset_minutes: 0,
        }))
      return (await api.post('/content-planner/quick-add', { items })).data
    },
    onSuccess: () => {
      setQuickOpen(false)
      setQuickRows([{ title: '', scheduled_local: '', time: '18:00', content_type: 'post' }])
      invalidate()
    },
  })

  const moveMutation = useMutation({
    mutationFn: async ({ id, date }: { id: number; date: string }) =>
      api.post(`/content-planner/${id}/move-day`, { date }),
    onSuccess: invalidate,
  })

  useEffect(() => {
    if (meta?.is_friday && view === 'week') {
      // soft highlight only
    }
  }, [meta, view])

  const openCreate = (preset?: Partial<ReturnType<typeof emptyForm>>) => {
    setEditingId(null)
    setForm({ ...emptyForm(), ...preset })
    setFormError('')
    setShowForm(true)
  }

  const openEdit = (item: ContentItem) => {
    setEditingId(item.id)
    setForm({
      ...emptyForm(),
      title: item.title,
      topic: '',
      content_type: item.content_type,
      platforms: item.platforms,
      goal: item.goal || '',
      hook: item.hook || '',
      body: item.body || '',
      cta: item.cta || '',
      caption: item.caption || '',
      hashtags: item.hashtags || [],
      visual_idea: item.visual_idea || '',
      overlay_text: item.overlay_text || '',
      location: item.location || '',
      notes: item.notes || '',
      status: item.status === 'reminder_sent' ? 'scheduled' : item.status,
      jalali_date: item.scheduled_jalali ? toEnglishDigits(item.scheduled_jalali) : '',
      time: item.scheduled_time || '18:30',
      reminder_enabled: item.reminder_enabled,
      reminder_offset_minutes: item.reminder_offset_minutes,
      reminder_custom_minutes: item.reminder_custom_minutes ?? '',
    })
    setDetail(null)
    setShowForm(true)
  }

  const addHashtag = () => {
    const raw = toEnglishDigits(form.hashtagInput).trim()
    if (!raw) return
    const tag = raw.startsWith('#') ? raw : `#${raw}`
    if (!form.hashtags.includes(tag)) {
      setForm((f) => ({ ...f, hashtags: [...f.hashtags, tag], hashtagInput: '' }))
    } else {
      setForm((f) => ({ ...f, hashtagInput: '' }))
    }
  }

  const shiftWeek = (dir: -1 | 0 | 1) => {
    if (dir === 0) {
      setAnchor(undefined)
      setView('week')
      return
    }
    const base = anchor ? new Date(anchor + 'T12:00:00Z') : new Date()
    base.setUTCDate(base.getUTCDate() + dir * 7)
    setAnchor(base.toISOString().slice(0, 10))
    if (view === 'list') setView('week')
  }

  const days: DayCol[] = calendar?.days ?? []
  const listItems: ContentItem[] = listData?.data ?? []

  const typeIcon = (t: string) => {
    if (t === 'reels' || t === 'video') return '🎬'
    if (t === 'story') return '📱'
    if (t === 'photo') return '🖼️'
    return '📝'
  }

  if (!hasFeature && hasFeature !== false) {
    // still render — feature may be soft-gated
  }

  return (
    <div className="space-y-6 animate-fade-in" dir="rtl">
      <div className="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <CalendarDays className="h-6 w-6 text-primary" />
            تقویم محتوا
          </h1>
          <p className="text-muted text-sm mt-1">محتوای هفته‌ات را از همین‌جا برنامه‌ریزی کن.</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Button onClick={() => openCreate()}><Plus className="h-4 w-4" />افزودن محتوا</Button>
          <Button variant="outline" onClick={() => { setView('week'); setAnchor(undefined) }}>برنامه این هفته</Button>
          <Button variant="outline" onClick={() => setView('next_week')}>برنامه هفته بعد</Button>
          <Button variant={view === 'month' ? 'default' : 'outline'} onClick={() => setView('month')}>تقویم</Button>
          <Button variant={view === 'list' ? 'default' : 'outline'} onClick={() => setView('list')}><List className="h-4 w-4" />لیست محتوا</Button>
          <Button variant="ghost" onClick={() => setQuickOpen(true)}>افزودن سریع</Button>
        </div>
      </div>

      {dash && (
        <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
          {[
            { label: 'این هفته', value: toPersianDigits(String(dash.week_planned)) },
            { label: 'منتشرشده', value: toPersianDigits(String(dash.week_published)) },
            { label: 'در انتظار', value: toPersianDigits(String(dash.week_pending)) },
            {
              label: 'نزدیک‌ترین انتشار',
              value: dash.nearest_publish
                ? (dash.nearest_publish.is_today ? `امروز - ${toPersianDigits(dash.nearest_publish.time)}` : toPersianDigits(`${dash.nearest_publish.jalali_date} ${dash.nearest_publish.time}`))
                : '—',
            },
            { label: 'یادآوری‌های فعال', value: toPersianDigits(String(dash.active_reminders)) },
          ].map((s) => (
            <Card key={s.label} className="p-3">
              <p className="text-xs text-muted">{s.label}</p>
              <p className="text-lg font-bold mt-1">{s.value}</p>
            </Card>
          ))}
        </div>
      )}

      {meta?.is_friday && (
        <Card className="p-4 border-primary/30 bg-primary/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div>
            <p className="font-semibold">وقت برنامه‌ریزی هفته آینده است 🚀</p>
            <p className="text-sm text-muted mt-1">هفته آینده را همین حالا بچین</p>
          </div>
          <Button onClick={() => {
            setView('next_week')
            openCreate({ status: 'scheduled', time: '18:30' })
          }}>
            <Rocket className="h-4 w-4" />شروع برنامه‌ریزی
          </Button>
        </Card>
      )}

      {view !== 'list' && (
        <div className="flex items-center justify-center gap-3">
          <Button variant="ghost" size="icon" onClick={() => shiftWeek(1)} aria-label="هفته قبل"><ChevronRight className="h-5 w-5" /></Button>
          <Button variant="outline" size="sm" onClick={() => shiftWeek(0)}>امروز</Button>
          <span className="text-sm font-medium min-w-[160px] text-center">
            {calendar ? toPersianDigits(`${calendar.start_jalali} تا ${calendar.end_jalali}`) : '…'}
          </span>
          <Button variant="ghost" size="icon" onClick={() => shiftWeek(-1)} aria-label="هفته بعد"><ChevronLeft className="h-5 w-5" /></Button>
        </div>
      )}

      {view !== 'list' && view === 'next_week' && dash?.next_week_count === 0 && !calLoading && (
        <Card className="p-8 text-center space-y-3">
          <p className="font-medium">هفته آینده هنوز برنامه‌ای نداری.</p>
          <Button onClick={() => openCreate({ status: 'scheduled' })}>شروع برنامه‌ریزی</Button>
        </Card>
      )}

      {view !== 'list' && calLoading && <p className="text-muted text-sm">در حال بارگذاری تقویم…</p>}

      {view !== 'list' && !calLoading && days.length > 0 && (
        <div className={cn(
          'grid gap-2',
          view === 'month' ? 'grid-cols-2 md:grid-cols-4 lg:grid-cols-7' : 'grid-cols-1 md:grid-cols-2 xl:grid-cols-7'
        )}>
          {days.map((day) => (
            <div
              key={day.date}
              className={cn(
                'rounded-xl border border-card-border bg-card/40 p-3 min-h-[140px] flex flex-col',
                day.is_today && 'ring-1 ring-primary/40',
                day.is_friday && 'bg-primary/5'
              )}
              onDragOver={(e) => e.preventDefault()}
              onDrop={(e) => {
                const id = Number(e.dataTransfer.getData('text/content-id'))
                if (id) moveMutation.mutate({ id, date: day.date })
              }}
            >
              <div className="flex items-center justify-between mb-2">
                <div>
                  <p className="text-xs text-muted">{day.weekday}</p>
                  <p className="text-sm font-semibold">{toPersianDigits(day.jalali_label.replace(day.weekday, '').trim() || day.jalali_label)}</p>
                </div>
                <button
                  type="button"
                  className="text-primary text-lg leading-none px-1"
                  title="افزودن سریع"
                  onClick={() => openCreate({
                    status: 'scheduled',
                    jalali_date: '',
                    // backend accepts scheduled_local from gregorian day
                    time: '18:00',
                  })}
                >
                  +
                </button>
              </div>
              {day.is_friday && (
                <button
                  type="button"
                  className="text-[11px] text-primary mb-2 text-right"
                  onClick={() => setView('next_week')}
                >
                  برنامه‌ریزی هفته آینده
                </button>
              )}
              <div className="space-y-1.5 flex-1">
                {day.items.map((item) => (
                  <button
                    key={item.id}
                    type="button"
                    draggable
                    onDragStart={(e) => e.dataTransfer.setData('text/content-id', String(item.id))}
                    onClick={() => setDetail(item)}
                    className="w-full text-right rounded-lg bg-background/60 hover:bg-background px-2 py-1.5 text-xs border border-transparent hover:border-card-border"
                  >
                    <span className="text-muted">{item.scheduled_time ? toPersianDigits(item.scheduled_time) : '—'}</span>
                    <span className="mx-1">{typeIcon(item.content_type)}</span>
                    <span className="font-medium">{item.title}</span>
                  </button>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}

      {view === 'list' && (
        <div className="space-y-3">
          <div className="flex flex-wrap gap-2">
            <div className="relative flex-1 min-w-[180px]">
              <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted" />
              <Input className="pr-9" placeholder="جستجو در عنوان، کپشن، هشتگ…" value={search} onChange={(e) => setSearch(e.target.value)} />
            </div>
            <select
              className="h-10 rounded-xl border border-card-border bg-background px-3 text-sm"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
            >
              <option value="all">همه</option>
              {meta && Object.entries(meta.statuses as Record<string, string>).map(([k, v]) => (
                <option key={k} value={k}>{v}</option>
              ))}
            </select>
          </div>
          {listLoading && <p className="text-muted text-sm">در حال بارگذاری…</p>}
          {!listLoading && listItems.length === 0 && (
            <Card className="p-10 text-center space-y-3">
              <p className="font-medium">هنوز محتوایی برنامه‌ریزی نکرده‌ای.</p>
              <p className="text-sm text-muted">هفته‌ات را از همین‌جا بچین.</p>
              <Button onClick={() => openCreate()}><Plus className="h-4 w-4" />اولین محتوا را بساز</Button>
            </Card>
          )}
          {listItems.map((item) => (
            <Card key={item.id} className="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer" onClick={() => setDetail(item)}>
              <div>
                <p className="font-semibold">{item.title}</p>
                <p className="text-xs text-muted mt-1">
                  {item.scheduled_label ? toPersianDigits(item.scheduled_label) : 'بدون زمان'} · {item.status_label} · {item.platform_labels?.join('، ')}
                </p>
                {item.countdown && <p className="text-xs text-primary mt-1 flex items-center gap-1"><Clock className="h-3 w-3" />{toPersianDigits(item.countdown)}</p>}
              </div>
              <Badge>{item.content_type_label}</Badge>
            </Card>
          ))}
        </div>
      )}

      {view !== 'list' && !calLoading && days.every((d) => d.items.length === 0) && dash?.week_planned === 0 && view === 'week' && (
        <Card className="p-10 text-center space-y-3">
          <p className="font-medium">هنوز محتوایی برنامه‌ریزی نکرده‌ای.</p>
          <p className="text-sm text-muted">هفته‌ات را از همین‌جا بچین.</p>
          <Button onClick={() => openCreate()}><Plus className="h-4 w-4" />اولین محتوا را بساز</Button>
        </Card>
      )}

      {showForm && (
        <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 p-0 sm:p-4">
          <div className="w-full max-w-2xl max-h-[92vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-background border border-card-border p-5 space-y-4">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-bold">{editingId ? 'ویرایش محتوا' : 'افزودن محتوا'}</h2>
              <Button variant="ghost" size="icon" onClick={() => setShowForm(false)}><X className="h-4 w-4" /></Button>
            </div>
            {formError && <p className="text-sm text-danger">{formError}</p>}

            <section className="space-y-3">
              <h3 className="text-sm font-semibold text-muted">اطلاعات اصلی</h3>
              <Input placeholder="عنوان محتوا *" value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} />
              <Input placeholder="موضوع (اختیاری)" value={form.topic} onChange={(e) => setForm((f) => ({ ...f, topic: e.target.value }))} />
              <div className="grid sm:grid-cols-2 gap-3">
                <label className="text-sm space-y-1">
                  <span>نوع محتوا</span>
                  <select className="w-full h-10 rounded-xl border border-card-border bg-background px-3" value={form.content_type} onChange={(e) => setForm((f) => ({ ...f, content_type: e.target.value }))}>
                    {meta && Object.entries(meta.content_types as Record<string, string>).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                  </select>
                </label>
                <label className="text-sm space-y-1">
                  <span>هدف محتوا</span>
                  <select className="w-full h-10 rounded-xl border border-card-border bg-background px-3" value={form.goal} onChange={(e) => setForm((f) => ({ ...f, goal: e.target.value }))}>
                    <option value="">انتخاب کنید</option>
                    {meta && Object.entries(meta.goals as Record<string, string>).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                  </select>
                </label>
              </div>
              <div>
                <p className="text-sm mb-2">پلتفرم</p>
                <div className="flex flex-wrap gap-2">
                  {meta && Object.entries(meta.platforms as Record<string, string>).map(([k, v]) => {
                    const on = form.platforms.includes(k)
                    return (
                      <button
                        key={k}
                        type="button"
                        className={cn('px-3 py-1.5 rounded-lg text-sm border', on ? 'bg-primary/15 border-primary text-primary' : 'border-card-border')}
                        onClick={() => setForm((f) => ({
                          ...f,
                          platforms: on ? f.platforms.filter((p) => p !== k) : [...f.platforms, k],
                        }))}
                      >
                        {v}
                      </button>
                    )
                  })}
                </div>
              </div>
            </section>

            <section className="space-y-3">
              <h3 className="text-sm font-semibold text-muted">سناریو</h3>
              <textarea className="w-full min-h-[70px] rounded-xl border border-card-border bg-background p-3 text-sm" placeholder="در ۳ ثانیه اول چه چیزی مخاطب را متوقف می‌کند؟" value={form.hook} onChange={(e) => setForm((f) => ({ ...f, hook: e.target.value }))} />
              <textarea className="w-full min-h-[120px] rounded-xl border border-card-border bg-background p-3 text-sm" placeholder="متن یا سناریوی کامل محتوا را بنویس..." value={form.body} onChange={(e) => setForm((f) => ({ ...f, body: e.target.value }))} />
              <textarea className="w-full min-h-[70px] rounded-xl border border-card-border bg-background p-3 text-sm" placeholder="در پایان از مخاطب چه می‌خواهی؟" value={form.cta} onChange={(e) => setForm((f) => ({ ...f, cta: e.target.value }))} />
            </section>

            <section className="space-y-2">
              <div className="flex items-center justify-between">
                <h3 className="text-sm font-semibold text-muted">کپشن</h3>
                <span className="text-xs text-muted">{toPersianDigits(String(form.caption.length))} / {toPersianDigits('2200')}</span>
              </div>
              <textarea className="w-full min-h-[100px] rounded-xl border border-card-border bg-background p-3 text-sm" value={form.caption} onChange={(e) => setForm((f) => ({ ...f, caption: e.target.value }))} />
              <div className="flex gap-2">
                <Button type="button" variant="outline" size="sm" onClick={() => navigator.clipboard.writeText(form.caption)}>کپی</Button>
                <Button type="button" variant="ghost" size="sm" onClick={() => setForm((f) => ({ ...f, caption: '' }))}>پاک کردن</Button>
              </div>
            </section>

            <section className="space-y-2">
              <h3 className="text-sm font-semibold text-muted">هشتگ‌ها</h3>
              <div className="flex gap-2">
                <Input placeholder="#املاک" value={form.hashtagInput} onChange={(e) => setForm((f) => ({ ...f, hashtagInput: e.target.value }))} onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), addHashtag())} />
                <Button type="button" variant="outline" onClick={addHashtag}>افزودن</Button>
              </div>
              <div className="flex flex-wrap gap-2">
                {form.hashtags.map((t) => (
                  <span key={t} className="inline-flex items-center gap-1 rounded-lg bg-primary/10 px-2 py-1 text-xs">
                    {t}
                    <button type="button" onClick={() => setForm((f) => ({ ...f, hashtags: f.hashtags.filter((x) => x !== t) }))}><X className="h-3 w-3" /></button>
                  </span>
                ))}
              </div>
            </section>

            <section className="grid sm:grid-cols-2 gap-3">
              <Input placeholder="ایده تصویر / ویدئو" value={form.visual_idea} onChange={(e) => setForm((f) => ({ ...f, visual_idea: e.target.value }))} />
              <Input placeholder="متن روی تصویر" value={form.overlay_text} onChange={(e) => setForm((f) => ({ ...f, overlay_text: e.target.value }))} />
              <Input placeholder="لوکیشن" value={form.location} onChange={(e) => setForm((f) => ({ ...f, location: e.target.value }))} />
              <Input placeholder="یادداشت داخلی" value={form.notes} onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))} />
            </section>

            <section className="space-y-3">
              <h3 className="text-sm font-semibold text-muted">تاریخ و ساعت انتشار (تهران)</h3>
              <div className="grid sm:grid-cols-2 gap-3">
                <Input dir="ltr" placeholder="۱۴۰۵/۰۵/۲۸" value={toPersianDigits(form.jalali_date)} onChange={(e) => setForm((f) => ({ ...f, jalali_date: toEnglishDigits(e.target.value) }))} />
                <Input dir="ltr" placeholder="۱۸:۳۰" value={toPersianDigits(form.time)} onChange={(e) => setForm((f) => ({ ...f, time: toEnglishDigits(e.target.value) }))} />
              </div>
              <p className="text-[11px] text-muted">زمان همیشه بر اساس Asia/Tehran ذخیره می‌شود و به ساعت مرورگر وابسته نیست.</p>
              <label className="text-sm space-y-1 block">
                <span>وضعیت</span>
                <select className="w-full h-10 rounded-xl border border-card-border bg-background px-3" value={form.status} onChange={(e) => setForm((f) => ({ ...f, status: e.target.value }))}>
                  <option value="draft">پیش‌نویس</option>
                  <option value="scheduled">زمان‌بندی‌شده</option>
                </select>
              </label>
              <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={form.reminder_enabled} onChange={(e) => setForm((f) => ({ ...f, reminder_enabled: e.target.checked }))} />
                ارسال SMS یادآوری
              </label>
              {form.reminder_enabled && (
                <div className="grid sm:grid-cols-2 gap-3">
                  <select className="h-10 rounded-xl border border-card-border bg-background px-3 text-sm" value={form.reminder_offset_minutes} onChange={(e) => setForm((f) => ({ ...f, reminder_offset_minutes: Number(e.target.value) }))}>
                    {meta && Object.entries(meta.reminder_offsets as Record<string, string>).map(([k, v]) => (
                      <option key={k} value={k}>{v}</option>
                    ))}
                  </select>
                  <Input type="number" placeholder="یادآوری سفارشی (دقیقه قبل)" value={form.reminder_custom_minutes} onChange={(e) => setForm((f) => ({ ...f, reminder_custom_minutes: e.target.value }))} />
                </div>
              )}
            </section>

            <div className="flex flex-wrap gap-2 pt-2">
              <Button onClick={() => saveWithStatus(form.status || 'scheduled')} disabled={saving || !form.title}>
                {saving ? 'در حال ذخیره…' : 'ذخیره محتوا'}
              </Button>
              <Button variant="outline" onClick={() => saveWithStatus('draft')} disabled={saving || !form.title}>
                ذخیره به‌عنوان پیش‌نویس
              </Button>
              <Button variant="ghost" onClick={() => setShowForm(false)}>انصراف</Button>
            </div>
          </div>
        </div>
      )}

      {detail && (
        <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 p-0 sm:p-4" onClick={() => setDetail(null)}>
          <div className="w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-background border border-card-border p-5 space-y-3" onClick={(e) => e.stopPropagation()}>
            <div className="flex justify-between items-start gap-2">
              <div>
                <h2 className="text-lg font-bold">{detail.title}</h2>
                <p className="text-xs text-muted mt-1">{detail.status_label} · {detail.content_type_label}</p>
              </div>
              <Button variant="ghost" size="icon" onClick={() => setDetail(null)}><X className="h-4 w-4" /></Button>
            </div>
            {detail.countdown && <p className="text-sm text-primary">{toPersianDigits(detail.countdown)}</p>}
            <p className="text-sm"><span className="text-muted">زمان:</span> {detail.scheduled_label ? toPersianDigits(detail.scheduled_label) : '—'}</p>
            <p className="text-sm"><span className="text-muted">پلتفرم:</span> {detail.platform_labels?.join('، ')}</p>
            {detail.hook && <div><p className="text-xs text-muted">هوک</p><p className="text-sm whitespace-pre-wrap">{detail.hook}</p></div>}
            {detail.body && <div><p className="text-xs text-muted">بدنه</p><p className="text-sm whitespace-pre-wrap">{detail.body}</p></div>}
            {detail.cta && <div><p className="text-xs text-muted">CTA</p><p className="text-sm whitespace-pre-wrap">{detail.cta}</p></div>}
            {detail.caption && <div><p className="text-xs text-muted">کپشن</p><p className="text-sm whitespace-pre-wrap">{detail.caption}</p></div>}
            {detail.hashtags?.length > 0 && <p className="text-sm text-primary">{detail.hashtags.join(' ')}</p>}
            <div className="flex flex-wrap gap-2 pt-2">
              <Button size="sm" onClick={() => openEdit(detail)}><Pencil className="h-3.5 w-3.5" />ویرایش</Button>
              <Button size="sm" variant="outline" onClick={() => actionMutation.mutate({ id: detail.id, action: 'duplicate' })}><Copy className="h-3.5 w-3.5" />کپی محتوا</Button>
              {(detail.status === 'scheduled' || detail.status === 'reminder_sent') && (
                <>
                  <Button size="sm" variant="outline" onClick={() => actionMutation.mutate({ id: detail.id, action: 'mark-published' })}><Check className="h-3.5 w-3.5" />علامت‌گذاری به‌عنوان منتشرشده</Button>
                  <Button size="sm" variant="ghost" onClick={() => actionMutation.mutate({ id: detail.id, action: 'cancel' })}>لغو زمان‌بندی</Button>
                </>
              )}
              <Button size="sm" variant="ghost" className="text-danger" onClick={() => setDeleteId(detail.id)}><Trash2 className="h-3.5 w-3.5" />حذف</Button>
            </div>
          </div>
        </div>
      )}

      {deleteId && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-4">
          <Card className="p-5 max-w-sm w-full space-y-4">
            <p className="font-medium">آیا از حذف این محتوا مطمئن هستید؟</p>
            <div className="flex gap-2">
              <Button className="bg-danger text-white" onClick={() => actionMutation.mutate({ id: deleteId, action: 'delete' })}>حذف محتوا</Button>
              <Button variant="outline" onClick={() => setDeleteId(null)}>انصراف</Button>
            </div>
          </Card>
        </div>
      )}

      {quickOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
          <Card className="p-5 w-full max-w-lg space-y-3 max-h-[85vh] overflow-y-auto">
            <div className="flex justify-between items-center">
              <h2 className="font-bold">افزودن سریع</h2>
              <Button variant="ghost" size="icon" onClick={() => setQuickOpen(false)}><X className="h-4 w-4" /></Button>
            </div>
            <p className="text-xs text-muted">عنوان، تاریخ میلادی تهران (YYYY-MM-DD) و ساعت را وارد کن.</p>
            {quickRows.map((row, idx) => (
              <div key={idx} className="grid grid-cols-2 gap-2">
                <Input className="col-span-2" placeholder="عنوان" value={row.title} onChange={(e) => setQuickRows((rows) => rows.map((r, i) => i === idx ? { ...r, title: e.target.value } : r))} />
                <Input dir="ltr" placeholder="2026-08-15" value={row.scheduled_local} onChange={(e) => setQuickRows((rows) => rows.map((r, i) => i === idx ? { ...r, scheduled_local: e.target.value } : r))} />
                <Input dir="ltr" placeholder="18:00" value={row.time} onChange={(e) => setQuickRows((rows) => rows.map((r, i) => i === idx ? { ...r, time: e.target.value } : r))} />
              </div>
            ))}
            <Button variant="outline" size="sm" onClick={() => setQuickRows((r) => [...r, { title: '', scheduled_local: '', time: '18:00', content_type: 'post' }])}>+ ردیف</Button>
            <Button onClick={() => quickMutation.mutate()} disabled={quickMutation.isPending}>ذخیره همه</Button>
          </Card>
        </div>
      )}
    </div>
  )
}

export function ContentPlannerBell() {
  const qc = useQueryClient()
  const { data } = useQuery({
    queryKey: ['panel-notifications'],
    queryFn: async () => (await api.get('/crm/notifications')).data.data as {
      id: number
      title: string
      body?: string
      link?: string
      read_at?: string
      created_at?: string
    }[],
    refetchInterval: 60_000,
  })
  const [open, setOpen] = useState(false)
  const unread = useMemo(() => (data ?? []).filter((n) => !n.read_at).length, [data])

  const markOne = useMutation({
    mutationFn: (id: number) => api.post(`/crm/notifications/${id}/read`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['panel-notifications'] }),
  })

  const markAll = useMutation({
    mutationFn: () => api.post('/crm/notifications/read-all'),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['panel-notifications'] }),
  })

  return (
    <div className="relative">
      <Button variant="ghost" size="icon" onClick={() => setOpen((v) => !v)} aria-label="اعلان‌ها">
        <Bell className="h-4 w-4" />
        {unread > 0 && <span className="absolute -top-0.5 -left-0.5 h-4 min-w-4 rounded-full bg-danger text-[10px] text-white flex items-center justify-center px-1">{toPersianDigits(String(unread))}</span>}
      </Button>
      {open && (
        <div className="absolute left-0 top-10 z-50 w-80 rounded-xl border border-card-border bg-background shadow-xl p-2 max-h-96 overflow-y-auto">
          <div className="flex items-center justify-between px-2 py-1">
            <p className="text-xs text-muted">اعلان‌ها (تا ۴۸ ساعت)</p>
            {unread > 0 && (
              <button type="button" className="text-[11px] text-primary" onClick={() => markAll.mutate()} disabled={markAll.isPending}>
                همه را مشاهده کردم
              </button>
            )}
          </div>
          {(data ?? []).length === 0 && <p className="text-sm p-3 text-muted">اعلانی نیست.</p>}
          {(data ?? []).slice(0, 20).map((n) => (
            <div key={n.id} className={`px-2 py-2 border-b border-card-border/40 last:border-0 ${n.read_at ? 'opacity-60' : ''}`}>
              <p className="text-sm font-medium">{n.title}</p>
              {n.body && <p className="text-xs text-muted whitespace-pre-wrap mt-1 line-clamp-3">{n.body}</p>}
              <div className="flex gap-2 mt-2">
                {!n.read_at && (
                  <button
                    type="button"
                    className="text-[11px] px-2 py-1 rounded-lg bg-primary/15 text-primary"
                    onClick={() => markOne.mutate(n.id)}
                    disabled={markOne.isPending}
                  >
                    مشاهده کردم
                  </button>
                )}
                {n.link && (
                  <a href={n.link} className="text-[11px] px-2 py-1 rounded-lg border border-card-border" onClick={() => setOpen(false)}>
                    باز کردن
                  </a>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
