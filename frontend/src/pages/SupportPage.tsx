import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { LifeBuoy, Plus } from 'lucide-react'
import { useState } from 'react'
import api from '@/lib/api'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

export function SupportPage() {
  const queryClient = useQueryClient()
  const [subject, setSubject] = useState('')
  const [message, setMessage] = useState('')
  const [showForm, setShowForm] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['tickets'],
    queryFn: async () => (await api.get('/tickets')).data,
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/tickets', { subject, message, priority: 'medium' }),
    onSuccess: () => {
      setSubject('')
      setMessage('')
      setShowForm(false)
      queryClient.invalidateQueries({ queryKey: ['tickets'] })
    },
  })

  return (
    <div className="space-y-6 animate-fade-in max-w-2xl">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <LifeBuoy className="h-6 w-6 text-primary" />
            پشتیبانی
          </h1>
          <p className="text-muted mt-1">ارسال درخواست به تیم پوشه</p>
        </div>
        <Button onClick={() => setShowForm(!showForm)}>
          <Plus className="h-4 w-4" />
          تیکت جدید
        </Button>
      </div>

      {showForm && (
        <Card className="glass">
          <CardContent className="p-4 space-y-3">
            <Input placeholder="موضوع" value={subject} onChange={(e) => setSubject(e.target.value)} />
            <textarea
              className="w-full rounded-xl border border-card-border bg-white/5 p-3 text-sm min-h-28"
              placeholder="توضیحات..."
              value={message}
              onChange={(e) => setMessage(e.target.value)}
            />
            <Button onClick={() => createMutation.mutate()} disabled={!subject || !message || createMutation.isPending}>
              ارسال تیکت
            </Button>
          </CardContent>
        </Card>
      )}

      {isLoading ? (
        <div className="flex justify-center py-16"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
      ) : (
        <div className="space-y-2">
          {data?.data?.map((t: { id: number; subject: string; message: string; status: string; created_at: string }) => (
            <Card key={t.id} className="glass">
              <CardContent className="p-4">
                <div className="flex items-center justify-between gap-2 mb-2">
                  <p className="font-medium">{t.subject}</p>
                  <Badge variant={t.status === 'open' ? 'warning' : 'success'}>{t.status}</Badge>
                </div>
                <p className="text-sm text-muted">{t.message}</p>
                <p className="text-xs text-muted mt-2">{new Date(t.created_at).toLocaleDateString('fa-IR')}</p>
              </CardContent>
            </Card>
          ))}
          {!data?.data?.length && <p className="text-muted text-center py-8">تیکتی ثبت نشده</p>}
        </div>
      )}
    </div>
  )
}
