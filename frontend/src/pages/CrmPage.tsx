import { useState } from 'react'
import { useQuery, useMutation } from '@tanstack/react-query'
import { Kanban, Plus, Star } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'

interface Deal {
  id: number
  title: string
  customer_name?: string
  customer_mobile?: string
  value?: number
  lead_score: number
  next_follow_up_at?: string
}

interface Stage {
  id: number
  name: string
  color: string
  deals: Deal[]
}

export function CrmPage() {
  const [newDealTitle, setNewDealTitle] = useState('')

  const { data: stages, refetch } = useQuery({
    queryKey: ['crm-pipeline'],
    queryFn: async () => (await api.get('/crm/pipeline')).data.data as Stage[],
  })

  const createDeal = useMutation({
    mutationFn: async (title: string) => api.post('/crm/deals', { title }),
    onSuccess: () => { setNewDealTitle(''); refetch() },
  })

  const moveDeal = async (dealId: number, stageId: number) => {
    await api.put(`/crm/deals/${dealId}/move`, { stage_id: stageId })
    refetch()
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2"><Kanban className="h-6 w-6 text-primary" />CRM — قیف فروش ملک</h1>
          <p className="text-muted text-sm">مدیریت سرنخ تا قرارداد با امتیازدهی خودکار</p>
        </div>
        <div className="flex gap-2">
          <Input placeholder="عنوان معامله جدید" value={newDealTitle} onChange={(e) => setNewDealTitle(e.target.value)} className="w-48" />
          <Button onClick={() => newDealTitle && createDeal.mutate(newDealTitle)}><Plus className="h-4 w-4" />معامله</Button>
        </div>
      </div>

      <div className="flex gap-4 overflow-x-auto pb-4">
        {stages?.map((stage) => (
          <div key={stage.id} className="min-w-[260px] flex-shrink-0">
            <div className="flex items-center gap-2 mb-3">
              <div className="w-3 h-3 rounded-full" style={{ background: stage.color }} />
              <h2 className="font-semibold text-sm">{stage.name}</h2>
              <Badge variant="outline" className="text-xs">{stage.deals?.length || 0}</Badge>
            </div>
            <div className="space-y-2 min-h-[200px]">
              {stage.deals?.map((deal) => (
                <Card key={deal.id} className="p-3 cursor-pointer glass-hover" draggable
                  onDragStart={(e) => e.dataTransfer.setData('dealId', String(deal.id))}
                  onDragOver={(e) => e.preventDefault()}
                  onDrop={() => moveDeal(deal.id, stage.id)}
                >
                  <p className="font-medium text-sm">{deal.title}</p>
                  {deal.customer_name && <p className="text-xs text-muted">{deal.customer_name}</p>}
                  <div className="flex justify-between mt-2">
                    <span className="text-xs flex items-center gap-1 text-amber-500"><Star className="h-3 w-3" />{deal.lead_score}</span>
                    {deal.value && <span className="text-xs text-primary">{(deal.value / 1e9).toFixed(1)}B</span>}
                  </div>
                </Card>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}
