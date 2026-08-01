import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  Kanban, Plus, GripVertical, Phone, User, Star, Clock, MessageSquare,
  Trash2, Save, AlertCircle, TrendingUp,
} from 'lucide-react'
import { useState } from 'react'
import api from '@/lib/api'
import { formatJalaliDate, formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { usePlanFeature } from '@/components/SubscriptionGuard'

const STAGES = [
  { key: 'lead', label: 'سرنخ', color: 'border-slate-500/30' },
  { key: 'contact', label: 'تماس', color: 'border-blue-500/30' },
  { key: 'visit', label: 'بازدید', color: 'border-cyan-500/30' },
  { key: 'negotiation', label: 'مذاکره', color: 'border-amber-500/30' },
  { key: 'closed_won', label: 'موفق', color: 'border-emerald-500/30' },
  { key: 'closed_lost', label: 'ناموفق', color: 'border-red-500/30' },
]

const PRIORITY_LABELS: Record<string, string> = {
  low: 'کم', medium: 'متوسط', high: 'بالا', urgent: 'فوری',
}

const ACTIVITY_TYPES = [
  { value: 'note', label: 'یادداشت' },
  { value: 'call', label: 'تماس' },
  { value: 'visit', label: 'بازدید' },
  { value: 'email', label: 'ایمیل' },
  { value: 'meeting', label: 'جلسه' },
]

interface Deal {
  id: number
  title: string
  stage: string
  stage_label?: string
  contact_name?: string
  contact_mobile?: string
  value?: number
  offer_amount?: number
  lead_score?: number
  priority?: string
  source?: string
  notes?: string
  follow_up_at?: string
  is_overdue?: boolean
  assignee?: { id: number; name: string }
}

interface Activity {
  id: number
  type: string
  body: string
  created_at: string
  user?: { name: string }
}

interface PipelineStage {
  stage: string
  label: string
  count: number
  total_value: number
}

const emptyForm = {
  title: '',
  contact_name: '',
  contact_mobile: '',
  value: '',
  priority: 'medium',
  source: '',
  follow_up_at: '',
  notes: '',
}

export function CrmPage() {
  const hasCrm = usePlanFeature('crm')
  const queryClient = useQueryClient()
  const [dragOver, setDragOver] = useState<string | null>(null)
  const [selectedId, setSelectedId] = useState<number | null>(null)
  const [note, setNote] = useState('')
  const [activityType, setActivityType] = useState('note')
  const [showCreate, setShowCreate] = useState(false)
  const [createForm, setCreateForm] = useState(emptyForm)
  const [editForm, setEditForm] = useState(emptyForm)

  const { data: deals, isLoading, isError, error } = useQuery({
    queryKey: ['crm-deals'],
    queryFn: async () => (await api.get('/crm/deals')).data.data as Deal[],
    enabled: hasCrm,
  })

  const { data: pipeline } = useQuery({
    queryKey: ['crm-pipeline'],
    queryFn: async () => {
      const res = await api.get('/crm/pipeline')
      return res.data.data as Record<string, PipelineStage>
    },
    enabled: hasCrm,
  })

  const { data: followUps } = useQuery({
    queryKey: ['crm-follow-ups'],
    queryFn: async () => (await api.get('/crm/follow-ups')).data.data as Deal[],
    enabled: hasCrm,
  })

  const { data: activities } = useQuery({
    queryKey: ['crm-activities', selectedId],
    queryFn: async () => (await api.get(`/crm/deals/${selectedId}/activities`)).data.data as Activity[],
    enabled: hasCrm && !!selectedId,
  })

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['crm-deals'] })
    queryClient.invalidateQueries({ queryKey: ['crm-pipeline'] })
    queryClient.invalidateQueries({ queryKey: ['crm-follow-ups'] })
    queryClient.invalidateQueries({ queryKey: ['commissions'] })
  }

  const createMutation = useMutation({
    mutationFn: () => api.post('/crm/deals', {
      title: createForm.title,
      contact_name: createForm.contact_name || undefined,
      contact_mobile: createForm.contact_mobile || undefined,
      value: createForm.value ? Number(createForm.value) : undefined,
      priority: createForm.priority,
      source: createForm.source || undefined,
      follow_up_at: createForm.follow_up_at || undefined,
      notes: createForm.notes || undefined,
      stage: 'lead',
    }),
    onSuccess: () => {
      invalidate()
      setCreateForm(emptyForm)
      setShowCreate(false)
    },
  })

  const updateMutation = useMutation({
    mutationFn: (payload: Record<string, unknown>) => api.put(`/crm/deals/${selectedId}`, payload),
    onSuccess: () => {
      invalidate()
      queryClient.invalidateQueries({ queryKey: ['crm-activities', selectedId] })
    },
  })

  const deleteMutation = useMutation({
    mutationFn: () => api.delete(`/crm/deals/${selectedId}`),
    onSuccess: () => {
      setSelectedId(null)
      invalidate()
    },
  })

  const moveMutation = useMutation({
    mutationFn: ({ id, stage }: { id: number; stage: string }) => api.put(`/crm/deals/${id}`, { stage }),
    onSuccess: invalidate,
  })

  const noteMutation = useMutation({
    mutationFn: () => api.post(`/crm/deals/${selectedId}/activities`, { type: activityType, body: note }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['crm-activities', selectedId] })
      setNote('')
    },
  })

  const handleDrop = (stage: string, e: React.DragEvent) => {
    e.preventDefault()
    setDragOver(null)
    const dealId = Number(e.dataTransfer.getData('dealId'))
    if (!dealId) return
    const deal = deals?.find((d) => d.id === dealId)
    if (deal && deal.stage !== stage) {
      moveMutation.mutate({ id: dealId, stage })
    }
  }

  const selectDeal = (deal: Deal) => {
    setSelectedId(deal.id)
    setEditForm({
      title: deal.title,
      contact_name: deal.contact_name || '',
      contact_mobile: deal.contact_mobile || '',
      value: deal.value ? String(deal.value) : '',
      priority: deal.priority || 'medium',
      source: deal.source || '',
      follow_up_at: deal.follow_up_at ? deal.follow_up_at.slice(0, 16) : '',
      notes: deal.notes || '',
    })
  }

  if (!hasCrm) {
    return <div className="p-8 text-center text-muted">CRM در پلن شما فعال نیست.</div>
  }

  if (isLoading) {
    return <div className="p-8 text-center text-muted">در حال بارگذاری CRM…</div>
  }

  if (isError) {
    const msg = (error as { response?: { data?: { message?: string } } })?.response?.data?.message
    return (
      <div className="p-8 text-center">
        <AlertCircle className="h-10 w-10 text-danger mx-auto mb-2" />
        <p className="text-danger">{msg || 'خطا در بارگذاری CRM'}</p>
      </div>
    )
  }

  const totalOpen = deals?.filter((d) => !['closed_won', 'closed_lost'].includes(d.stage)).length ?? 0
  const totalValue = deals?.reduce((s, d) => s + (d.value ?? 0), 0) ?? 0
  const selected = deals?.find((d) => d.id === selectedId)

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <Kanban className="h-6 w-6 text-primary" /> قیف فروش CRM
          </h1>
          <p className="text-sm text-muted mt-1">
            {totalOpen} معامله باز · ارزش کل {formatPrice(totalValue)}
          </p>
        </div>
        <Button onClick={() => setShowCreate((v) => !v)}>
          <Plus className="h-4 w-4" /> معامله جدید
        </Button>
      </div>

      {pipeline && (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
          {STAGES.map((s) => {
            const p = pipeline[s.key]
            return (
              <Card key={s.key} className="p-3">
                <p className="text-[10px] text-muted">{s.label}</p>
                <p className="text-lg font-bold">{p?.count ?? 0}</p>
                {(p?.total_value ?? 0) > 0 && (
                  <p className="text-[10px] text-primary flex items-center gap-1">
                    <TrendingUp className="h-3 w-3" />{formatPrice(p.total_value)}
                  </p>
                )}
              </Card>
            )
          })}
        </div>
      )}

      {followUps && followUps.length > 0 && (
        <Card className="border-warning/30">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm flex items-center gap-2">
              <Clock className="h-4 w-4 text-warning" /> پیگیری‌های این هفته ({followUps.length})
            </CardTitle>
          </CardHeader>
          <CardContent className="flex flex-wrap gap-2">
            {followUps.map((d) => (
              <button
                key={d.id}
                type="button"
                onClick={() => selectDeal(d)}
                className={`text-xs px-3 py-1.5 rounded-full border ${d.is_overdue ? 'border-danger text-danger' : 'border-card-border'}`}
              >
                {d.title}
                {d.follow_up_at && <span className="mr-1 opacity-70">· {formatJalaliDate(d.follow_up_at)}</span>}
              </button>
            ))}
          </CardContent>
        </Card>
      )}

      {showCreate && (
        <Card>
          <CardHeader><CardTitle className="text-base">ثبت معامله جدید</CardTitle></CardHeader>
          <CardContent className="grid sm:grid-cols-2 gap-3">
            <Input placeholder="عنوان معامله *" value={createForm.title} onChange={(e) => setCreateForm({ ...createForm, title: e.target.value })} />
            <Input placeholder="نام مشتری" value={createForm.contact_name} onChange={(e) => setCreateForm({ ...createForm, contact_name: e.target.value })} />
            <Input placeholder="موبایل مشتری" value={createForm.contact_mobile} onChange={(e) => setCreateForm({ ...createForm, contact_mobile: e.target.value })} dir="ltr" />
            <Input placeholder="ارزش معامله (تومان)" value={createForm.value} onChange={(e) => setCreateForm({ ...createForm, value: e.target.value })} dir="ltr" type="number" />
            <select className="rounded-xl border border-card-border bg-background/50 p-2.5 text-sm" value={createForm.priority} onChange={(e) => setCreateForm({ ...createForm, priority: e.target.value })}>
              {Object.entries(PRIORITY_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
            </select>
            <Input placeholder="منبع (دیوار، معرفی، …)" value={createForm.source} onChange={(e) => setCreateForm({ ...createForm, source: e.target.value })} />
            <Input type="datetime-local" value={createForm.follow_up_at} onChange={(e) => setCreateForm({ ...createForm, follow_up_at: e.target.value })} />
            <textarea className="sm:col-span-2 w-full min-h-[60px] rounded-xl border border-card-border bg-background/50 p-3 text-sm" placeholder="یادداشت" value={createForm.notes} onChange={(e) => setCreateForm({ ...createForm, notes: e.target.value })} />
            <div className="sm:col-span-2 flex gap-2">
              <Button onClick={() => createMutation.mutate()} disabled={!createForm.title || createMutation.isPending}>ثبت معامله</Button>
              <Button variant="outline" onClick={() => setShowCreate(false)}>انصراف</Button>
            </div>
          </CardContent>
        </Card>
      )}

      <div className="grid xl:grid-cols-4 gap-4">
        <div className="xl:col-span-3 flex gap-3 overflow-x-auto pb-4 min-h-[480px]">
          {STAGES.map((stage) => {
            const stageDeals = deals?.filter((d) => d.stage === stage.key) ?? []
            const stageValue = stageDeals.reduce((s, d) => s + (d.value ?? 0), 0)

            return (
              <div
                key={stage.key}
                className={`flex-shrink-0 w-56 rounded-xl border-2 ${stage.color} ${dragOver === stage.key ? 'bg-primary/10 border-primary' : 'bg-card/50'} transition-colors`}
                onDragOver={(e) => { e.preventDefault(); setDragOver(stage.key) }}
                onDragLeave={() => setDragOver(null)}
                onDrop={(e) => handleDrop(stage.key, e)}
              >
                <div className="p-3 border-b border-card-border">
                  <div className="flex items-center justify-between">
                    <span className="font-semibold text-sm">{stage.label}</span>
                    <Badge variant="outline" className="text-[10px]">{stageDeals.length}</Badge>
                  </div>
                  {stageValue > 0 && <p className="text-[10px] text-muted mt-1">{formatPrice(stageValue)}</p>}
                </div>
                <div className="p-2 space-y-2 min-h-[120px] max-h-[420px] overflow-y-auto">
                  {stageDeals.map((d) => (
                    <div
                      key={d.id}
                      draggable
                      onDragStart={(e) => e.dataTransfer.setData('dealId', String(d.id))}
                      onClick={() => selectDeal(d)}
                      className={`rounded-lg bg-background/80 border p-2.5 text-xs cursor-grab active:cursor-grabbing hover:border-primary/30 transition-colors space-y-1.5 ${selectedId === d.id ? 'border-primary ring-1 ring-primary/30' : 'border-card-border'}`}
                    >
                      <div className="flex items-start gap-1">
                        <GripVertical className="h-3.5 w-3.5 text-muted shrink-0 mt-0.5" />
                        <p className="font-medium leading-snug flex-1">{d.title}</p>
                        {d.lead_score != null && (
                          <span className="flex items-center gap-0.5 text-warning text-[10px]">
                            <Star className="h-3 w-3" />{d.lead_score}
                          </span>
                        )}
                      </div>
                      {d.priority && d.priority !== 'medium' && (
                        <Badge variant="outline" className="text-[9px]">{PRIORITY_LABELS[d.priority]}</Badge>
                      )}
                      {d.contact_name && (
                        <p className="flex items-center gap-1 text-muted"><User className="h-3 w-3" />{d.contact_name}</p>
                      )}
                      {d.contact_mobile && (
                        <p className="flex items-center gap-1 text-muted" dir="ltr"><Phone className="h-3 w-3" />{d.contact_mobile}</p>
                      )}
                      {d.value ? <p className="text-primary font-semibold">{formatPrice(d.value)}</p> : null}
                      {d.is_overdue && <p className="text-danger text-[10px]">پیگیری عقب‌افتاده</p>}
                    </div>
                  ))}
                </div>
              </div>
            )
          })}
        </div>

        <Card className="xl:col-span-1 h-fit sticky top-4">
          <CardHeader>
            <CardTitle className="text-sm flex items-center gap-2">
              <MessageSquare className="h-4 w-4" /> جزئیات معامله
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3 text-sm">
            {!selected ? (
              <p className="text-muted text-xs">روی یک کارت کلیک کنید</p>
            ) : (
              <>
                <Input value={editForm.title} onChange={(e) => setEditForm({ ...editForm, title: e.target.value })} />
                <Input placeholder="نام مشتری" value={editForm.contact_name} onChange={(e) => setEditForm({ ...editForm, contact_name: e.target.value })} />
                <Input placeholder="موبایل" value={editForm.contact_mobile} onChange={(e) => setEditForm({ ...editForm, contact_mobile: e.target.value })} dir="ltr" />
                <Input placeholder="ارزش (تومان)" value={editForm.value} onChange={(e) => setEditForm({ ...editForm, value: e.target.value })} dir="ltr" type="number" />
                <select className="w-full rounded-xl border border-card-border bg-background/50 p-2 text-sm" value={editForm.priority} onChange={(e) => setEditForm({ ...editForm, priority: e.target.value })}>
                  {Object.entries(PRIORITY_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                </select>
                <Input type="datetime-local" value={editForm.follow_up_at} onChange={(e) => setEditForm({ ...editForm, follow_up_at: e.target.value })} />
                <textarea className="w-full min-h-[50px] rounded-xl border border-card-border bg-background/50 p-2 text-xs" placeholder="یادداشت" value={editForm.notes} onChange={(e) => setEditForm({ ...editForm, notes: e.target.value })} />
                {selected.lead_score != null && (
                  <p className="text-xs text-muted">امتیاز سرنخ: <span className="text-primary font-bold">{selected.lead_score}</span>/100</p>
                )}
                <div className="flex gap-2">
                  <Button size="sm" onClick={() => updateMutation.mutate({
                    title: editForm.title,
                    contact_name: editForm.contact_name || null,
                    contact_mobile: editForm.contact_mobile || null,
                    value: editForm.value ? Number(editForm.value) : null,
                    priority: editForm.priority,
                    follow_up_at: editForm.follow_up_at || null,
                    notes: editForm.notes || null,
                  })} disabled={updateMutation.isPending}>
                    <Save className="h-3 w-3" /> ذخیره
                  </Button>
                  <Button size="sm" variant="outline" className="text-danger" onClick={() => deleteMutation.mutate()} disabled={deleteMutation.isPending}>
                    <Trash2 className="h-3 w-3" />
                  </Button>
                </div>
                <div className="border-t border-card-border pt-3 space-y-2">
                  <div className="flex gap-2">
                    <select className="rounded-lg border border-card-border bg-background/50 p-1.5 text-xs" value={activityType} onChange={(e) => setActivityType(e.target.value)}>
                      {ACTIVITY_TYPES.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
                    </select>
                    <Input placeholder="فعالیت..." value={note} onChange={(e) => setNote(e.target.value)} className="text-xs flex-1" />
                    <Button size="sm" disabled={!note} onClick={() => noteMutation.mutate()}>+</Button>
                  </div>
                  <div className="space-y-2 max-h-48 overflow-y-auto">
                    {activities?.map((a) => (
                      <div key={a.id} className="text-xs border-b border-card-border pb-2">
                        <p className="text-[10px] text-muted">{ACTIVITY_TYPES.find((t) => t.value === a.type)?.label || a.type} · {a.user?.name} · {formatJalaliDate(a.created_at)}</p>
                        <p>{a.body}</p>
                      </div>
                    ))}
                    {!activities?.length && <p className="text-xs text-muted">فعالیتی ثبت نشده</p>}
                  </div>
                </div>
              </>
            )}
          </CardContent>
        </Card>
      </div>

      <p className="text-xs text-muted text-center">معامله موفق → کمیسیون خودکار · امتیاز سرنخ بر اساس اطلاعات تماس و ارزش</p>
    </div>
  )
}
