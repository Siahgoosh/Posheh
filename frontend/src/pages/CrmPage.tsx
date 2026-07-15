import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Kanban, Plus, GripVertical, Phone, User } from 'lucide-react'
import { useState } from 'react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { usePlanFeature } from '@/components/SubscriptionGuard'

const STAGES = [
  { key: 'lead', label: 'سرنخ', color: 'border-slate-500/30' },
  { key: 'contact', label: 'تماس', color: 'border-blue-500/30' },
  { key: 'visit', label: 'بازدید', color: 'border-cyan-500/30' },
  { key: 'negotiation', label: 'مذاکره', color: 'border-amber-500/30' },
  { key: 'closed_won', label: 'موفق', color: 'border-emerald-500/30' },
  { key: 'closed_lost', label: 'ناموفق', color: 'border-red-500/30' },
]

interface Deal {
  id: number
  title: string
  stage: string
  contact_name?: string
  contact_mobile?: string
  value?: number
  offer_amount?: number
  assignee?: { name: string }
}

export function CrmPage() {
  const hasCrm = usePlanFeature('crm')
  const queryClient = useQueryClient()
  const [title, setTitle] = useState('')
  const [dragOver, setDragOver] = useState<string | null>(null)

  const { data: deals } = useQuery({
    queryKey: ['crm-deals'],
    queryFn: async () => (await api.get('/crm/deals')).data.data as Deal[],
    enabled: hasCrm,
  })

  const { data: pipeline } = useQuery({
    queryKey: ['crm-pipeline'],
    queryFn: async () => (await api.get('/crm/pipeline')).data.data,
    enabled: hasCrm,
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

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <Kanban className="h-6 w-6 text-primary" /> قیف فروش CRM
          </h1>
          <p className="text-sm text-muted mt-1">{totalOpen} معامله باز — کارت‌ها را بکشید و رها کنید</p>
        </div>
      </div>

      <div className="flex gap-2">
        <Input placeholder="معامله جدید..." value={title} onChange={(e) => setTitle(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && title && createMutation.mutate()} />
        <Button onClick={() => createMutation.mutate()} disabled={!title}><Plus className="h-4 w-4" /></Button>
      </div>

      <div className="flex gap-3 overflow-x-auto pb-4 min-h-[480px]">
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
                    className="rounded-lg bg-background/80 border border-card-border p-2.5 text-xs cursor-grab active:cursor-grabbing hover:border-primary/30 transition-colors space-y-1.5"
                  >
                    <div className="flex items-start gap-1">
                      <GripVertical className="h-3.5 w-3.5 text-muted shrink-0 mt-0.5" />
                      <p className="font-medium leading-snug flex-1">{d.title}</p>
                    </div>
                    {d.contact_name && (
                      <p className="flex items-center gap-1 text-muted"><User className="h-3 w-3" />{d.contact_name}</p>
                    )}
                    {d.contact_mobile && (
                      <p className="flex items-center gap-1 text-muted" dir="ltr"><Phone className="h-3 w-3" />{d.contact_mobile}</p>
                    )}
                    {d.value ? <p className="text-primary font-semibold">{formatPrice(d.value)}</p> : null}
                    {d.assignee && <p className="text-[10px] text-muted">{d.assignee.name}</p>}
                  </div>
                ))}
              </div>
            </div>
          )
        })}
      </div>

      {pipeline && (
        <p className="text-xs text-muted text-center">معامله موفق → کمیسیون مشاور به‌صورت خودکار ثبت می‌شود</p>
      )}
    </div>
  )
}
