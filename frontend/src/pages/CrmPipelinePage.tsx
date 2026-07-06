import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Kanban, Plus } from 'lucide-react'
import { Link } from 'react-router-dom'
import { useState } from 'react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

export function CrmPipelinePage() {
  const queryClient = useQueryClient()
  const [showDealForm, setShowDealForm] = useState(false)
  const [dealTitle, setDealTitle] = useState('')
  const [dealValue, setDealValue] = useState('')
  const [dealContactId, setDealContactId] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['crm-pipeline'],
    queryFn: async () => (await api.get('/crm/pipeline')).data.data,
  })

  const { data: contactsData } = useQuery({
    queryKey: ['contacts'],
    queryFn: async () => (await api.get('/contacts?per_page=100')).data,
    enabled: showDealForm,
  })

  const moveMutation = useMutation({
    mutationFn: ({ id, stageId }: { id: number; stageId: number }) =>
      api.put(`/crm/deals/${id}`, { pipeline_stage_id: stageId }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['crm-pipeline'] }),
  })

  const createDealMutation = useMutation({
    mutationFn: () => api.post('/crm/deals', {
      title: dealTitle,
      value: dealValue ? parseInt(dealValue) : 0,
      contact_id: parseInt(dealContactId),
    }),
    onSuccess: () => {
      setShowDealForm(false)
      setDealTitle('')
      setDealValue('')
      setDealContactId('')
      queryClient.invalidateQueries({ queryKey: ['crm-pipeline'] })
    },
  })

  if (isLoading) {
    return <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
  }

  const stages = data?.pipeline?.stages ?? []
  const dealsByStage = data?.deals_by_stage ?? {}
  const contacts = contactsData?.data ?? []

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <Kanban className="h-6 w-6 text-primary" />
          قیف فروش CRM
        </h1>
        <div className="flex gap-2">
          <Button onClick={() => setShowDealForm(!showDealForm)}>
            <Plus className="h-4 w-4" />
            معامله جدید
          </Button>
          <Link to="/contacts" className="text-sm text-primary self-center">مخاطبین</Link>
        </div>
      </div>

      {showDealForm && (
        <Card className="glass max-w-lg">
          <CardContent className="p-4 space-y-3">
            <Input placeholder="عنوان معامله" value={dealTitle} onChange={(e) => setDealTitle(e.target.value)} />
            <Input placeholder="ارزش (تومان)" dir="ltr" value={dealValue} onChange={(e) => setDealValue(e.target.value)} />
            <select className="flex h-11 w-full rounded-xl border border-card-border bg-white/5 px-4 text-sm"
              value={dealContactId} onChange={(e) => setDealContactId(e.target.value)}>
              <option value="">انتخاب مخاطب</option>
              {contacts.map((c: { id: number; name: string }) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
            <Button onClick={() => createDealMutation.mutate()}
              disabled={!dealTitle || !dealContactId || createDealMutation.isPending}>
              ایجاد معامله
            </Button>
          </CardContent>
        </Card>
      )}

      <div className="flex gap-4 overflow-x-auto pb-4">
        {stages.map((stage: { id: number; name: string; color: string; probability: number }) => (
          <div key={stage.id} className="min-w-[260px] flex-shrink-0">
            <div className="flex items-center justify-between mb-3">
              <h3 className="font-semibold text-sm" style={{ color: stage.color }}>{stage.name}</h3>
              <Badge variant="outline">{stage.probability}%</Badge>
            </div>
            <div className="space-y-2 min-h-[200px]">
              {(dealsByStage[stage.id] ?? []).map((deal: {
                id: number; title: string; value: number;
                contact?: { id: number; name: string; mobile?: string }
              }) => (
                <Card key={deal.id} className="glass-hover cursor-pointer"
                  onClick={() => {
                    const idx = stages.findIndex((s: { id: number }) => s.id === stage.id)
                    const next = stages[idx + 1]
                    if (next) moveMutation.mutate({ id: deal.id, stageId: next.id })
                  }}>
                  <CardContent className="p-3">
                    <p className="font-medium text-sm">{deal.title}</p>
                    {deal.contact && (
                      <Link to={`/contacts/${deal.contact.id}`} className="text-xs text-primary hover:underline"
                        onClick={(e) => e.stopPropagation()}>
                        {deal.contact.name}
                      </Link>
                    )}
                    <p className="text-sm font-bold mt-1">{formatPrice(deal.value)}</p>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}
