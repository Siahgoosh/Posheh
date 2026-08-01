import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Filter, MessageSquare, Lock } from 'lucide-react'
import api from '@/lib/api'
import { formatJalaliDate } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

interface TicketReply {
  id: number
  message: string
  is_staff: boolean
  is_internal?: boolean
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
  office?: { name: string }
  user?: { name: string; email?: string; mobile?: string }
  assignee?: { name: string }
  replies?: TicketReply[]
}

const STATUS_OPTIONS = [
  { value: 'open', label: 'باز' },
  { value: 'in_progress', label: 'در حال بررسی' },
  { value: 'closed', label: 'بسته' },
]

export function AdminTicketsPage() {
  const [selectedId, setSelectedId] = useState<number | null>(null)
  const [message, setMessage] = useState('')
  const [isInternal, setIsInternal] = useState(false)
  const [statusFilter, setStatusFilter] = useState('')
  const [priorityFilter, setPriorityFilter] = useState('')
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-tickets', statusFilter, priorityFilter],
    queryFn: async () => {
      const res = await api.get('/admin/tickets', {
        params: {
          ...(statusFilter ? { status: statusFilter } : {}),
          ...(priorityFilter ? { priority: priorityFilter } : {}),
        },
      })
      const payload = res.data.data ?? res.data
      return (Array.isArray(payload) ? payload : payload.data ?? []) as Ticket[]
    },
  })

  const { data: selectedTicket } = useQuery({
    queryKey: ['admin-ticket', selectedId],
    queryFn: async () => (await api.get(`/admin/tickets/${selectedId}`)).data.data as Ticket,
    enabled: !!selectedId,
  })

  const replyMutation = useMutation({
    mutationFn: ({ id, msg, internal }: { id: number; msg: string; internal: boolean }) =>
      api.post(`/admin/tickets/${id}/reply`, { message: msg, is_internal: internal }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-tickets'] })
      queryClient.invalidateQueries({ queryKey: ['admin-ticket', selectedId] })
      setMessage('')
    },
  })

  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      api.put(`/admin/tickets/${id}/status`, { status }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-tickets'] })
      queryClient.invalidateQueries({ queryKey: ['admin-ticket', selectedId] })
    },
  })

  const tickets = Array.isArray(data) ? data : []
  const ticket = selectedTicket || tickets.find((t) => t.id === selectedId)

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="تیکت‌های پشتیبانی" description="مدیریت حرفه‌ای تیکت‌های دفاتر" />

      <div className="flex flex-wrap gap-2 items-center">
        <Filter className="h-4 w-4 text-muted" />
        <select className="rounded-lg border border-card-border bg-background/50 p-2 text-sm" value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
          <option value="">همه وضعیت‌ها</option>
          {STATUS_OPTIONS.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
        </select>
        <select className="rounded-lg border border-card-border bg-background/50 p-2 text-sm" value={priorityFilter} onChange={(e) => setPriorityFilter(e.target.value)}>
          <option value="">همه اولویت‌ها</option>
          <option value="low">کم</option>
          <option value="medium">متوسط</option>
          <option value="high">بالا</option>
        </select>
      </div>

      <div className="grid lg:grid-cols-5 gap-4">
        <Card className="lg:col-span-2">
          <CardHeader><CardTitle className="text-base">لیست تیکت‌ها ({tickets.length})</CardTitle></CardHeader>
          <CardContent className="space-y-2 max-h-[70vh] overflow-y-auto">
            {isLoading ? <p className="text-sm text-muted">بارگذاری…</p> : tickets.length === 0 ? (
              <p className="text-sm text-muted">تیکتی نیست.</p>
            ) : tickets.map((t) => (
              <button
                key={t.id}
                type="button"
                onClick={() => setSelectedId(t.id)}
                className={`w-full text-right rounded-xl border p-3 text-sm transition-colors ${selectedId === t.id ? 'border-primary bg-primary/5' : 'border-card-border hover:bg-white/5'}`}
              >
                {t.ticket_number && <p className="text-[10px] text-muted font-mono" dir="ltr">{t.ticket_number}</p>}
                <p className="font-medium truncate">{t.subject}</p>
                <p className="text-xs text-muted">{t.office?.name} · {t.user?.name}</p>
                <div className="flex gap-1 mt-1">
                  <Badge variant="outline" className="text-[10px]">{t.status_label || t.status}</Badge>
                  <Badge variant="outline" className="text-[10px]">{t.priority_label || t.priority}</Badge>
                </div>
              </button>
            ))}
          </CardContent>
        </Card>

        <Card className="lg:col-span-3">
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <MessageSquare className="h-4 w-4" /> جزئیات تیکت
            </CardTitle>
          </CardHeader>
          <CardContent>
            {!ticket ? (
              <p className="text-sm text-muted">یک تیکت را انتخاب کنید</p>
            ) : (
              <div className="space-y-4">
                <div className="flex flex-wrap justify-between gap-2">
                  <div>
                    {ticket.ticket_number && <p className="text-xs text-muted font-mono" dir="ltr">{ticket.ticket_number}</p>}
                    <h2 className="font-bold">{ticket.subject}</h2>
                    <p className="text-sm text-muted">{ticket.office?.name} · {ticket.user?.name}</p>
                    {ticket.user?.mobile && <p className="text-xs text-muted" dir="ltr">{ticket.user.mobile}</p>}
                  </div>
                  <select
                    className="rounded-lg border border-card-border bg-background/50 p-2 text-sm h-fit"
                    value={ticket.status}
                    onChange={(e) => statusMutation.mutate({ id: ticket.id, status: e.target.value })}
                  >
                    {STATUS_OPTIONS.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                  </select>
                </div>

                <div className="space-y-2 max-h-[40vh] overflow-y-auto">
                  <div className="p-3 rounded-xl bg-white/5 text-sm">
                    <p className="text-[10px] text-muted mb-1">پیام اولیه · {ticket.created_at && formatJalaliDate(ticket.created_at)}</p>
                    {ticket.message}
                  </div>
                  {ticket.replies?.map((r) => (
                    <div
                      key={r.id}
                      className={`p-3 rounded-xl text-sm ${r.is_internal ? 'bg-warning/10 border border-warning/30' : r.is_staff ? 'bg-primary/10' : 'bg-white/5'}`}
                    >
                      <p className="text-[10px] text-muted mb-1 flex items-center gap-1">
                        {r.is_internal && <Lock className="h-3 w-3" />}
                        {r.user?.name || (r.is_staff ? 'پشتیبانی' : 'مشتری')}
                        {r.created_at && ` · ${formatJalaliDate(r.created_at)}`}
                        {r.is_internal && ' · یادداشت داخلی'}
                      </p>
                      {r.message}
                    </div>
                  ))}
                </div>

                {ticket.status !== 'closed' && (
                  <div className="space-y-2 border-t border-card-border pt-3">
                    <textarea
                      className="w-full rounded-xl border border-card-border bg-background p-3 text-sm min-h-[80px]"
                      value={message}
                      onChange={(e) => setMessage(e.target.value)}
                      placeholder="پاسخ به مشتری…"
                    />
                    <label className="flex items-center gap-2 text-xs text-muted">
                      <input type="checkbox" checked={isInternal} onChange={(e) => setIsInternal(e.target.checked)} />
                      یادداشت داخلی (مشتری نمی‌بیند)
                    </label>
                    <Button onClick={() => replyMutation.mutate({ id: ticket.id, msg: message, internal: isInternal })} disabled={!message}>
                      ارسال پاسخ
                    </Button>
                  </div>
                )}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
