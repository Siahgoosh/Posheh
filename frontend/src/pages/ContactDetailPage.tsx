import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useParams, Link } from 'react-router-dom'
import { ArrowRight, Phone, MessageSquare, Plus } from 'lucide-react'
import { useState } from 'react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { formatPrice } from '@/lib/utils'

const typeLabels: Record<string, string> = {
  buyer: 'خریدار', seller: 'فروشنده', lead: 'سرنخ', owner: 'مالک',
}

export function ContactDetailPage() {
  const { id } = useParams()
  const queryClient = useQueryClient()
  const [note, setNote] = useState('')
  const [dealTitle, setDealTitle] = useState('')
  const [showDealForm, setShowDealForm] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['contact', id],
    queryFn: async () => (await api.get(`/crm/contacts/${id}`)).data,
  })

  const addActivity = useMutation({
    mutationFn: (body: { type: string; subject: string; body?: string }) =>
      api.post(`/crm/contacts/${id}/activities`, body),
    onSuccess: () => {
      setNote('')
      queryClient.invalidateQueries({ queryKey: ['contact', id] })
    },
  })

  const createDeal = useMutation({
    mutationFn: () => api.post('/crm/deals', { title: dealTitle, contact_id: parseInt(id!) }),
    onSuccess: () => {
      setDealTitle('')
      setShowDealForm(false)
      queryClient.invalidateQueries({ queryKey: ['contact', id] })
      queryClient.invalidateQueries({ queryKey: ['crm-pipeline'] })
    },
  })

  if (isLoading || !data) {
    return <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
  }

  const contact = data.data

  return (
    <div className="max-w-3xl mx-auto space-y-6 animate-fade-in">
      <div className="flex items-center gap-3">
        <Link to="/contacts"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
        <div>
          <h1 className="text-2xl font-bold">{contact.name}</h1>
          <p className="text-muted dir-ltr text-right">{contact.mobile || '—'}</p>
        </div>
      </div>

      <div className="flex gap-2 flex-wrap">
        <Badge>{typeLabels[contact.type] || contact.type}</Badge>
        <Badge variant="outline">{contact.status}</Badge>
        {contact.budget_max && <Badge variant="success">بودجه تا {formatPrice(contact.budget_max)}</Badge>}
        {contact.email && <Badge variant="outline" dir="ltr">{contact.email}</Badge>}
      </div>

      <div className="flex gap-2 flex-wrap">
        <Button size="sm" variant="outline" onClick={() => addActivity.mutate({ type: 'call', subject: 'تماس تلفنی' })}>
          <Phone className="h-4 w-4" />تماس
        </Button>
        <Button size="sm" variant="outline" onClick={() => addActivity.mutate({ type: 'sms', subject: 'پیامک' })}>
          <MessageSquare className="h-4 w-4" />پیامک
        </Button>
        <Button size="sm" variant="secondary" onClick={() => setShowDealForm(!showDealForm)}>
          <Plus className="h-4 w-4" />معامله جدید
        </Button>
      </div>

      {showDealForm && (
        <Card className="glass">
          <CardContent className="p-4 flex gap-2">
            <Input placeholder="عنوان معامله" value={dealTitle} onChange={(e) => setDealTitle(e.target.value)} />
            <Button onClick={() => createDeal.mutate()} disabled={!dealTitle || createDeal.isPending}>ایجاد</Button>
          </CardContent>
        </Card>
      )}

      <Card className="glass">
        <CardContent className="p-4 space-y-3">
          <Input placeholder="یادداشت جدید..." value={note} onChange={(e) => setNote(e.target.value)} />
          <Button size="sm" onClick={() => addActivity.mutate({ type: 'note', subject: 'یادداشت', body: note })} disabled={!note}>
            ثبت فعالیت
          </Button>
        </CardContent>
      </Card>

      <div>
        <h2 className="font-semibold mb-3">تایم‌لاین</h2>
        <div className="space-y-2">
          {data.activities?.map((a: { id: number; type: string; subject: string; body?: string; created_at: string; user?: { name: string } }) => (
            <Card key={a.id} className="glass">
              <CardContent className="p-3">
                <div className="flex justify-between text-sm">
                  <span className="font-medium">{a.subject}</span>
                  <span className="text-muted">{new Date(a.created_at).toLocaleDateString('fa-IR')}</span>
                </div>
                {a.body && <p className="text-sm text-muted mt-1">{a.body}</p>}
                <p className="text-xs text-muted mt-1">{a.user?.name} · {a.type}</p>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>

      {data.deals?.length > 0 && (
        <div>
          <h2 className="font-semibold mb-3">معاملات</h2>
          {data.deals.map((d: { id: number; title: string; value: number; stage?: { name: string } }) => (
            <Card key={d.id} className="glass mb-2">
              <CardContent className="p-3 flex justify-between">
                <span>{d.title}</span>
                <span>{formatPrice(d.value)} · {d.stage?.name}</span>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
