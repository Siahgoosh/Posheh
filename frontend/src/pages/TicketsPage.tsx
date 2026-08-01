import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { LifeBuoy, Send, Filter } from 'lucide-react'
import api from '@/lib/api'
import { formatJalaliDate } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface TicketReply {
  id: number
  message: string
  is_staff: boolean
  created_at?: string
  user?: { name: string }
}

interface Ticket {
  id: number
  ticket_number?: string
  subject: string
  message: string
  status: string
  status_label?: string
  priority: string
  priority_label?: string
  category?: string
  created_at?: string
  replies?: TicketReply[]
}

const STATUS_COLORS: Record<string, string> = {
  open: 'text-warning',
  in_progress: 'text-primary',
  closed: 'text-muted',
}

export function TicketsPage() {
  const queryClient = useQueryClient()
  const [subject, setSubject] = useState('')
  const [message, setMessage] = useState('')
  const [priority, setPriority] = useState('medium')
  const [category, setCategory] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [replyText, setReplyText] = useState<Record<number, string>>({})
  const [expandedId, setExpandedId] = useState<number | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['tickets', statusFilter],
    queryFn: async () => {
      const res = await api.get('/tickets', { params: statusFilter ? { status: statusFilter } : {} })
      const payload = res.data.data ?? res.data
      return (Array.isArray(payload) ? payload : payload.data ?? []) as Ticket[]
    },
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/tickets', { subject, message, priority, category: category || undefined }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tickets'] })
      setSubject('')
      setMessage('')
      setCategory('')
    },
  })

  const replyMutation = useMutation({
    mutationFn: ({ id, text }: { id: number; text: string }) => api.post(`/tickets/${id}/reply`, { message: text }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['tickets'] }),
  })

  const tickets = Array.isArray(data) ? data : []

  return (
    <div className="space-y-6 max-w-3xl mx-auto animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <LifeBuoy className="h-6 w-6 text-primary" /> پشتیبانی
        </h1>
        <p className="text-sm text-muted mt-1">تیکت‌های شما با شماره پیگیری ثبت می‌شوند</p>
      </div>

      <Card>
        <CardHeader><CardTitle className="text-base">تیکت جدید</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <Input placeholder="موضوع" value={subject} onChange={(e) => setSubject(e.target.value)} />
          <div className="grid sm:grid-cols-2 gap-2">
            <select className="rounded-xl border border-card-border bg-background/50 p-2.5 text-sm" value={priority} onChange={(e) => setPriority(e.target.value)}>
              <option value="low">اولویت: کم</option>
              <option value="medium">اولویت: متوسط</option>
              <option value="high">اولویت: بالا</option>
            </select>
            <Input placeholder="دسته (فنی، مالی، …)" value={category} onChange={(e) => setCategory(e.target.value)} />
          </div>
          <textarea className="w-full min-h-[80px] rounded-xl border border-card-border bg-background/50 p-3 text-sm" placeholder="شرح مشکل یا درخواست" value={message} onChange={(e) => setMessage(e.target.value)} />
          <Button onClick={() => createMutation.mutate()} disabled={!subject || !message || createMutation.isPending}>
            ارسال تیکت
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="text-base">تیکت‌های من</CardTitle>
          <div className="flex items-center gap-2 text-sm">
            <Filter className="h-4 w-4 text-muted" />
            <select className="rounded-lg border border-card-border bg-background/50 p-1.5 text-xs" value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
              <option value="">همه</option>
              <option value="open">باز</option>
              <option value="in_progress">در حال بررسی</option>
              <option value="closed">بسته</option>
            </select>
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          {isLoading ? <p className="text-sm text-muted">بارگذاری…</p> : tickets.length === 0 ? (
            <p className="text-sm text-muted">تیکتی ندارید.</p>
          ) : tickets.map((t) => (
            <div key={t.id} className="rounded-xl border border-card-border overflow-hidden">
              <button
                type="button"
                className="w-full p-4 text-right hover:bg-white/5 transition-colors"
                onClick={() => setExpandedId(expandedId === t.id ? null : t.id)}
              >
                <div className="flex flex-wrap justify-between gap-2 items-start">
                  <div>
                    {t.ticket_number && <p className="text-[10px] text-muted font-mono" dir="ltr">{t.ticket_number}</p>}
                    <p className="font-medium">{t.subject}</p>
                  </div>
                  <div className="flex gap-2">
                    <Badge variant="outline" className={STATUS_COLORS[t.status]}>{t.status_label || t.status}</Badge>
                    <Badge variant="outline">{t.priority_label || t.priority}</Badge>
                  </div>
                </div>
                {t.created_at && <p className="text-[10px] text-muted mt-1">{formatJalaliDate(t.created_at)}</p>}
              </button>
              {expandedId === t.id && (
                <div className="px-4 pb-4 space-y-2 border-t border-card-border pt-3">
                  <p className="text-sm">{t.message}</p>
                  {t.replies?.map((r) => (
                    <div key={r.id} className={`text-sm p-2 rounded-lg ${r.is_staff ? 'bg-primary/10 border border-primary/20' : 'bg-white/5'}`}>
                      <p className="text-[10px] text-muted mb-1">
                        {r.user?.name || (r.is_staff ? 'پشتیبانی پوشه' : 'شما')}
                        {r.created_at && ` · ${formatJalaliDate(r.created_at)}`}
                      </p>
                      {r.message}
                    </div>
                  ))}
                  {t.status !== 'closed' ? (
                    <div className="flex gap-2 pt-2">
                      <Input placeholder="پاسخ شما…" value={replyText[t.id] || ''} onChange={(e) => setReplyText((s) => ({ ...s, [t.id]: e.target.value }))} />
                      <Button size="sm" variant="outline" onClick={() => replyMutation.mutate({ id: t.id, text: replyText[t.id] || '' })} disabled={!replyText[t.id]}>
                        <Send className="h-4 w-4" />
                      </Button>
                    </div>
                  ) : (
                    <p className="text-xs text-muted">این تیکت بسته شده است.</p>
                  )}
                </div>
              )}
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
