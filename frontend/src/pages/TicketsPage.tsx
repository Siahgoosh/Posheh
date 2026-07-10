import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { LifeBuoy, Send } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface Ticket {
  id: number
  subject: string
  message: string
  status: string
  priority: string
  replies?: { id: number; message: string; is_staff: boolean; user?: { name: string } }[]
}

export function TicketsPage() {
  const queryClient = useQueryClient()
  const [subject, setSubject] = useState('')
  const [message, setMessage] = useState('')
  const [replyText, setReplyText] = useState<Record<number, string>>({})

  const { data, isLoading } = useQuery({
    queryKey: ['tickets'],
    queryFn: async () => {
      const res = await api.get('/tickets')
      return (res.data.data ?? res.data) as Ticket[]
    },
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/tickets', { subject, message }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tickets'] })
      setSubject('')
      setMessage('')
    },
  })

  const replyMutation = useMutation({
    mutationFn: ({ id, text }: { id: number; text: string }) => api.post(`/tickets/${id}/reply`, { message: text }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['tickets'] }),
  })

  const tickets = Array.isArray(data) ? data : []

  return (
    <div className="space-y-6 max-w-3xl mx-auto animate-fade-in">
      <h1 className="text-2xl font-bold flex items-center gap-2"><LifeBuoy className="h-6 w-6 text-primary" /> پشتیبانی</h1>

      <Card>
        <CardHeader><CardTitle>تیکت جدید</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <Input placeholder="موضوع" value={subject} onChange={(e) => setSubject(e.target.value)} />
          <textarea className="w-full min-h-[80px] rounded-xl border border-card-border bg-background/50 p-3 text-sm" placeholder="پیام شما" value={message} onChange={(e) => setMessage(e.target.value)} />
          <Button onClick={() => createMutation.mutate()} disabled={!subject || !message || createMutation.isPending}>ارسال تیکت</Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>تیکت‌های من</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          {isLoading ? <p className="text-sm text-muted">بارگذاری…</p> : tickets.length === 0 ? <p className="text-sm text-muted">تیکتی ندارید.</p> : tickets.map((t) => (
            <div key={t.id} className="rounded-xl border border-card-border p-4 space-y-2">
              <div className="flex justify-between gap-2">
                <p className="font-medium">{t.subject}</p>
                <span className="text-xs text-muted">{t.status}</span>
              </div>
              <p className="text-sm text-muted">{t.message}</p>
              {t.replies?.map((r) => (
                <div key={r.id} className={`text-sm p-2 rounded-lg ${r.is_staff ? 'bg-primary/10' : 'bg-white/5'}`}>
                  <span className="text-xs text-muted">{r.user?.name || (r.is_staff ? 'پشتیبانی' : 'شما')}: </span>
                  {r.message}
                </div>
              ))}
              <div className="flex gap-2">
                <Input placeholder="پاسخ…" value={replyText[t.id] || ''} onChange={(e) => setReplyText((s) => ({ ...s, [t.id]: e.target.value }))} />
                <Button size="sm" variant="outline" onClick={() => replyMutation.mutate({ id: t.id, text: replyText[t.id] || '' })}><Send className="h-4 w-4" /></Button>
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
