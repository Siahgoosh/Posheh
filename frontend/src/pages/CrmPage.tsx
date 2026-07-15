import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Kanban, Plus, GripVertical, Phone, User, Star, Clock, MessageSquare } from 'lucide-react'
import { useState } from 'react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
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

interface Deal {
  id: number
  title: string
  stage: string
  contact_name?: string
  contact_mobile?: string
  value?: number
  offer_amount?: number
  lead_score?: number
  priority?: string
  follow_up_at?: string
  is_overdue?: boolean
  assignee?: { name: string }
}

interface Activity {
  id: number
  type: string
  body: string
  created_at: string
  user?: { name: string }
}

export function CrmPage() {
  const hasCrm = usePlanFeature('crm')
  const queryClient = useQueryClient()
  const [title, setTitle] = useState('')
  const [dragOver, setDragOver] = useState<string | null>(null)
  const [selectedId, setSelectedId] = useState<number | null>(null)
  const [note, setNote] = useState('')

  const { data: deals } = useQuery({
    queryKey: ['crm-deals'],
    queryFn: async () => (await api.get('/crm/deals')).data.data as Deal[],
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

  const createMutation = useMutation({
    mutationFn: () => api.post('/crm/deals', { title, stage: 'lead' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['crm-deals'] })
      queryClient.invalidateQueries({ queryKey: ['crm-pipeline'] })
      setTitle('')
    },
  })

  const moveMutation = useMutation({
    mutationFn: ({ id, stage }: { id: number; stage: string }) => api.put(`/crm/deals/${id}`, { stage }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['crm-deals'] })
      queryClient.invalidateQueries({ queryKey: ['crm-pipeline'] })
      queryClient.invalidateQueries({ queryKey: ['commissions'] })
    },
  })

  const noteMutation = useMutation({
    mutationFn: () => api.post(`/crm/deals/${selectedId}/activities`, { type: 'note', body: note }),
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

  if (!hasCrm) {
    return <div className="p-8 text-center text-muted">CRM در پلن شما فعال نیست.</div>
  }

  const totalOpen = deals?.filter((d) => !['closed_won', 'closed_lost'].includes(d.stage)).length ?? 0
  const selected = deals?.find((d) => d.id === selectedId)

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <Kanban className="h-6 w-6 text-primary" /> قیف فروش CRM
          </h1>
          <p className="text-sm text-muted mt-1">{totalOpen} معامله باز — الگوی Salesforce / Follow Up Boss</p>
        </div>
      </div>

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
                onClick={() => setSelectedId(d.id)}
                className={`text-xs px-3 py-1.5 rounded-full border ${d.is_overdue ? 'border-danger text-danger' : 'border-card-border'}`}
              >
                {d.title}
              </button>
            ))}
          </CardContent>
        </Card>
      )}

      <div className="flex gap-2">
        <Input placeholder="معامله جدید..." value={title} onChange={(e) => setTitle(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && title && createMutation.mutate()} />
        <Button onClick={() => createMutation.mutate()} disabled={!title}><Plus className="h-4 w-4" /></Button>
      </div>

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
                      onClick={() => setSelectedId(d.id)}
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
                <p className="font-semibold">{selected.title}</p>
                {selected.lead_score != null && (
                  <p className="text-xs text-muted">امتیاز سرنخ: <span className="text-primary font-bold">{selected.lead_score}</span>/100</p>
                )}
                <div className="flex gap-2">
                  <Input placeholder="یادداشت..." value={note} onChange={(e) => setNote(e.target.value)} className="text-xs" />
                  <Button size="sm" disabled={!note} onClick={() => noteMutation.mutate()}>+</Button>
                </div>
                <div className="space-y-2 max-h-64 overflow-y-auto">
                  {activities?.map((a) => (
                    <div key={a.id} className="text-xs border-b border-card-border pb-2">
                      <p>{a.body}</p>
                      <p className="text-[10px] text-muted mt-1">{a.user?.name}</p>
                    </div>
                  ))}
                  {!activities?.length && <p className="text-xs text-muted">فعالیتی ثبت نشده</p>}
                </div>
              </>
            )}
          </CardContent>
        </Card>
      </div>

      <p className="text-xs text-muted text-center">معامله موفق → کمیسیون خودکار · امتیاز سرنخ بر اساس اطلاعات تماس و ارزش معامله</p>
    </div>
  )
}
