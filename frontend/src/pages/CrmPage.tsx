import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Kanban, Plus } from 'lucide-react'
import { useState } from 'react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { usePlanFeature } from '@/components/SubscriptionGuard'

const STAGES = [
  { key: 'lead', label: 'سرنخ' },
  { key: 'contact', label: 'تماس' },
  { key: 'visit', label: 'بازدید' },
  { key: 'negotiation', label: 'مذاکره' },
  { key: 'closed_won', label: 'موفق' },
  { key: 'closed_lost', label: 'ناموفق' },
]

interface Deal {
  id: number
  title: string
  stage: string
  contact_name?: string
  value?: number
}

export function CrmPage() {
  const hasCrm = usePlanFeature('crm')
  const queryClient = useQueryClient()
  const [title, setTitle] = useState('')

  const { data: deals } = useQuery({
    queryKey: ['crm-deals'],
    queryFn: async () => (await api.get('/crm/deals')).data.data as Deal[],
    enabled: hasCrm,
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/crm/deals', { title, stage: 'lead' }),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['crm-deals'] }); setTitle('') },
  })

  const moveMutation = useMutation({
    mutationFn: ({ id, stage }: { id: number; stage: string }) => api.put(`/crm/deals/${id}`, { stage }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['crm-deals'] }),
  })

  if (!hasCrm) {
    return <div className="p-8 text-center text-muted">CRM در همه پلن‌ها فعال است — اگر نمی‌بینید اشتراک را بررسی کنید.</div>
  }

  return (
    <div className="space-y-6 animate-fade-in">
      <h1 className="text-2xl font-bold flex items-center gap-2"><Kanban className="h-6 w-6 text-primary" /> قیف فروش CRM</h1>

      <div className="flex gap-2">
        <Input placeholder="معامله جدید" value={title} onChange={(e) => setTitle(e.target.value)} />
        <Button onClick={() => createMutation.mutate()} disabled={!title}><Plus className="h-4 w-4" /></Button>
      </div>

      <div className="grid md:grid-cols-3 lg:grid-cols-6 gap-3 overflow-x-auto">
        {STAGES.map((stage) => (
          <Card key={stage.key} className="min-w-[160px]">
            <CardHeader className="py-3"><CardTitle className="text-sm">{stage.label}</CardTitle></CardHeader>
            <CardContent className="space-y-2 p-3 pt-0">
              {deals?.filter((d) => d.stage === stage.key).map((d) => (
                <div key={d.id} className="rounded-lg bg-white/5 p-2 text-xs space-y-1">
                  <p className="font-medium">{d.title}</p>
                  {d.value ? <p className="text-muted">{formatPrice(d.value)}</p> : null}
                  <select className="w-full text-xs rounded border border-card-border bg-background/50 p-1" value={d.stage} onChange={(e) => moveMutation.mutate({ id: d.id, stage: e.target.value })}>
                    {STAGES.map((s) => <option key={s.key} value={s.key}>{s.label}</option>)}
                  </select>
                </div>
              ))}
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  )
}
