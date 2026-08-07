import { useRef, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { MessageCircle, Users, Send, Sparkles, Ticket, Paperclip, XCircle } from 'lucide-react'

interface ConversationItem {
  uuid: string
  status: string
  channel?: string
  subject?: string
  unread_operator: number
  last_message_at?: string
  visitor?: { name?: string; mobile?: string; lead_score?: number }
  lead?: { id: number; office_name?: string; request_type?: string; lead_score?: number }
  last_message?: { body: string; sender_type: string }
}

interface ConversationDetail {
  uuid: string
  status: string
  channel?: string
  subject?: string
  assigned_to?: number
  ticket?: {
    uuid: string
    status: string
    priority: string
    email_alias?: string
    subject?: string
  }
  visitor?: Record<string, unknown>
  lead?: Record<string, unknown> & {
    id: number
    lead_score?: number
    office_name?: string
    mobile?: string
    email?: string
    province?: string
    city?: string
    request_type?: string
    budget?: string
    description?: string
    notes?: { body: string; user?: { name: string } }[]
  }
  messages: {
    id: number
    sender_type: string
    body: string
    message_type?: string
    is_internal?: boolean
    created_at: string
    attachments?: { id: number; original_name?: string; message_type?: string }[]
  }[]
}

interface LiveVisitor {
  visitor_id: number
  name?: string
  mobile?: string
  lead_score: number
  current_page?: string
  time_on_site_seconds?: number
  scroll_depth?: number
}

const CHANNEL_LABELS: Record<string, string> = {
  website: 'وب',
  telegram: 'تلگرام',
  whatsapp: 'واتساپ',
  email: 'ایمیل',
  web_app: 'وب',
}

export function AdminCommunicationInboxPage() {
  const [selectedUuid, setSelectedUuid] = useState<string | null>(null)
  const [reply, setReply] = useState('')
  const [suggestions, setSuggestions] = useState<string[]>([])
  const [knowledge, setKnowledge] = useState<{ title: string; slug: string }[]>([])
  const fileRef = useRef<HTMLInputElement>(null)
  const queryClient = useQueryClient()

  const { data: stats, isError: statsError, error: statsErr } = useQuery({
    queryKey: ['comm-dashboard'],
    queryFn: async () => (await api.get('/admin/communication/dashboard')).data.data,
    retry: false,
  })

  const { data: inbox, isError: inboxError, error: inboxErr } = useQuery({
    queryKey: ['comm-inbox'],
    queryFn: async () => (await api.get('/admin/communication/inbox')).data.data as ConversationItem[],
    refetchInterval: statsError ? false : 8000,
    retry: false,
  })

  const commApiError = statsError || inboxError
  const commApiMessage = (() => {
    const err = (statsErr ?? inboxErr) as { response?: { status?: number; data?: { message?: string } } }
    const status = err?.response?.status
    const msg = err?.response?.data?.message
    if (status === 403) return msg || 'دسترسی به مرکز ارتباطات مجاز نیست. با super_admin وارد شوید یا دسترسی comm را بررسی کنید.'
    if (status === 401) return 'نشست ورود منقضی شده — دوباره وارد پنل شوید.'
    if (msg) return msg
    return 'بارگذاری مرکز ارتباطات ناموفق بود. لاگ سرور یا communication:diagnose را بررسی کنید.'
  })()

  const { data: live } = useQuery({
    queryKey: ['comm-live'],
    queryFn: async () => (await api.get('/admin/communication/visitors/live')).data.data as LiveVisitor[],
    refetchInterval: 10000,
  })

  const { data: detail } = useQuery({
    queryKey: ['comm-conversation', selectedUuid],
    queryFn: async () => (await api.get(`/admin/communication/conversations/${selectedUuid}`)).data.data as ConversationDetail,
    enabled: !!selectedUuid,
    refetchInterval: 5000,
  })

  const replyMutation = useMutation({
    mutationFn: async () => {
      const file = fileRef.current?.files?.[0]
      if (file) {
        const form = new FormData()
        form.append('body', reply || file.name)
        form.append('file', file)
        return api.post(`/admin/communication/conversations/${selectedUuid}/reply`, form)
      }
      return api.post(`/admin/communication/conversations/${selectedUuid}/reply`, { body: reply })
    },
    onSuccess: () => {
      setReply('')
      if (fileRef.current) fileRef.current.value = ''
      queryClient.invalidateQueries({ queryKey: ['comm-conversation', selectedUuid] })
      queryClient.invalidateQueries({ queryKey: ['comm-inbox'] })
    },
  })

  const aiMutation = useMutation({
    mutationFn: async () => (await api.get(`/admin/communication/conversations/${selectedUuid}/ai/suggestions`)).data.data,
    onSuccess: (data) => {
      setSuggestions(data.suggestions ?? [])
      setKnowledge(data.knowledge ?? [])
    },
  })

  const ticketMutation = useMutation({
    mutationFn: () => api.post(`/admin/communication/conversations/${selectedUuid}/tickets`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['comm-conversation', selectedUuid] }),
  })

  const closeTicketMutation = useMutation({
    mutationFn: () => api.post(`/admin/communication/conversations/${selectedUuid}/tickets/close`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['comm-conversation', selectedUuid] }),
  })

  const closeConversationMutation = useMutation({
    mutationFn: () => api.put(`/admin/communication/conversations/${selectedUuid}`, { status: 'closed' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['comm-conversation', selectedUuid] })
      queryClient.invalidateQueries({ queryKey: ['comm-inbox'] })
    },
  })

  return (
    <div className="space-y-4 animate-fade-in h-[calc(100dvh-6rem)] flex flex-col">
      <AdminPageHeader
        title="مرکز ارتباطات"
        description="اینباکس یکپارچه — وب، تلگرام، ایمیل و واتساپ"
      />

      {commApiError && (
        <div className="rounded-xl border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive shrink-0">
          {commApiMessage}
        </div>
      )}

      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 shrink-0">
        <Card><CardContent className="pt-4"><p className="text-xs text-muted">چت فعال</p><p className="text-xl font-bold">{stats?.active_chats ?? 0}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-xs text-muted">سرنخ امروز</p><p className="text-xl font-bold">{stats?.new_leads_today ?? 0}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-xs text-muted">آنلاین</p><p className="text-xl font-bold text-primary">{stats?.online_visitors ?? 0}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-xs text-muted">پیام خوانده‌نشده</p><p className="text-xl font-bold">{stats?.unread_messages ?? 0}</p></CardContent></Card>
      </div>

      <div className="flex-1 min-h-0 grid lg:grid-cols-[280px_1fr_300px] gap-3 border border-card-border rounded-2xl overflow-hidden bg-card/30">
        <div className="border-r border-card-border flex flex-col min-h-0">
          <div className="p-3 border-b border-card-border font-medium text-sm flex items-center gap-2">
            <MessageCircle className="h-4 w-4" /> گفتگوها
          </div>
          <div className="flex-1 overflow-y-auto">
            {inbox?.map((c) => (
              <button
                key={c.uuid}
                type="button"
                onClick={() => {
                  setSelectedUuid(c.uuid)
                  setSuggestions([])
                  setKnowledge([])
                }}
                className={`w-full text-left p-3 border-b border-card-border/50 hover:bg-primary/5 transition-colors ${
                  selectedUuid === c.uuid ? 'bg-primary/10' : ''
                }`}
              >
                <div className="flex justify-between gap-2 items-center">
                  <span className="font-medium text-sm truncate">
                    {c.visitor?.name || c.lead?.office_name || c.subject || 'مهمان'}
                  </span>
                  <div className="flex gap-1 shrink-0">
                    {c.channel && (
                      <Badge variant="outline" className="text-[10px]">
                        {CHANNEL_LABELS[c.channel] ?? c.channel}
                      </Badge>
                    )}
                    {c.unread_operator > 0 && (
                      <Badge variant="default" className="text-[10px]">{c.unread_operator}</Badge>
                    )}
                  </div>
                </div>
                <p className="text-xs text-muted truncate mt-0.5">{c.last_message?.body}</p>
                {c.lead?.lead_score != null && (
                  <Badge variant="outline" className="mt-1 text-[10px]">امتیاز {c.lead.lead_score}</Badge>
                )}
              </button>
            ))}
          </div>
        </div>

        <div className="flex flex-col min-h-0 min-w-0">
          {detail ? (
            <>
              <div className="p-3 border-b border-card-border shrink-0 flex flex-wrap gap-2 items-center justify-between">
                <div>
                  <p className="font-semibold">{detail.subject || detail.lead?.office_name || 'گفتگو'}</p>
                  <p className="text-xs text-muted">{detail.lead?.mobile as string}</p>
                </div>
                <div className="flex gap-1 flex-wrap">
                  {detail.channel && (
                    <Badge variant="outline">{CHANNEL_LABELS[detail.channel] ?? detail.channel}</Badge>
                  )}
                  <Badge variant="outline">{detail.status}</Badge>
                  {!detail.ticket ? (
                    <Button size="sm" variant="outline" onClick={() => ticketMutation.mutate()} disabled={ticketMutation.isPending}>
                      <Ticket className="h-3 w-3 mr-1" /> تیکت
                    </Button>
                  ) : (
                    <Button size="sm" variant="outline" onClick={() => closeTicketMutation.mutate()} disabled={closeTicketMutation.isPending}>
                      <XCircle className="h-3 w-3 mr-1" /> بستن تیکت
                    </Button>
                  )}
                  {detail.status !== 'closed' && (
                    <Button size="sm" variant="ghost" onClick={() => closeConversationMutation.mutate()}>
                      بستن گفتگو
                    </Button>
                  )}
                </div>
              </div>
              <div className="flex-1 overflow-y-auto p-4 space-y-2">
                {detail.messages.filter((m) => !m.is_internal).map((m) => (
                  <div
                    key={m.id}
                    className={`max-w-[80%] rounded-2xl px-3 py-2 text-sm ${
                      m.sender_type === 'operator'
                        ? 'ml-auto bg-primary/20 border border-primary/30'
                        : 'mr-auto bg-background border border-card-border'
                    }`}
                  >
                    {m.body}
                    {m.attachments?.map((a) => (
                      <p key={a.id} className="text-xs text-muted mt-1">📎 {a.original_name ?? a.message_type}</p>
                    ))}
                    <p className="text-[10px] text-muted mt-1">{m.created_at?.slice(11, 16)}</p>
                  </div>
                ))}
              </div>
              {suggestions.length > 0 && (
                <div className="px-3 py-2 border-t border-card-border space-y-1 shrink-0">
                  <p className="text-xs text-muted flex items-center gap-1"><Sparkles className="h-3 w-3" /> پیشنهاد AI</p>
                  <div className="flex flex-wrap gap-1">
                    {suggestions.map((s, i) => (
                      <button
                        key={i}
                        type="button"
                        className="text-xs border border-card-border rounded-lg px-2 py-1 hover:bg-primary/10"
                        onClick={() => setReply(s)}
                      >
                        {s.slice(0, 60)}{s.length > 60 ? '…' : ''}
                      </button>
                    ))}
                  </div>
                </div>
              )}
              {knowledge.length > 0 && (
                <div className="px-3 py-2 border-t border-card-border shrink-0">
                  <p className="text-xs text-muted mb-1">مقالات مرتبط</p>
                  {knowledge.map((k) => (
                    <p key={k.slug} className="text-xs">{k.title}</p>
                  ))}
                </div>
              )}
              <div className="p-3 border-t border-card-border flex gap-2 shrink-0">
                <input ref={fileRef} type="file" className="hidden" />
                <Button size="icon" variant="outline" onClick={() => fileRef.current?.click()}>
                  <Paperclip className="h-4 w-4" />
                </Button>
                <Button size="icon" variant="outline" onClick={() => aiMutation.mutate()} disabled={aiMutation.isPending}>
                  <Sparkles className="h-4 w-4" />
                </Button>
                <Input
                  placeholder="پاسخ..."
                  value={reply}
                  onChange={(e) => setReply(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && (reply.trim() || fileRef.current?.files?.[0]) && replyMutation.mutate()}
                />
                <Button size="icon" onClick={() => replyMutation.mutate()} disabled={!reply.trim() && !fileRef.current?.files?.[0]}>
                  <Send className="h-4 w-4" />
                </Button>
              </div>
            </>
          ) : (
            <div className="flex-1 flex items-center justify-center text-muted text-sm">یک گفتگو انتخاب کنید</div>
          )}
        </div>

        <div className="border-l border-card-border flex flex-col min-h-0 overflow-y-auto">
          {detail?.ticket && (
            <div className="p-3 border-b border-card-border space-y-1">
              <p className="font-medium text-sm flex items-center gap-2"><Ticket className="h-4 w-4" /> تیکت</p>
              <p className="text-xs">{detail.ticket.subject}</p>
              <p className="text-xs text-muted">{detail.ticket.email_alias}</p>
              <Badge variant="outline" className="text-[10px]">{detail.ticket.status}</Badge>
            </div>
          )}
          {detail?.lead && (
            <div className="p-3 border-b border-card-border space-y-2">
              <p className="font-medium text-sm">اطلاعات سرنخ</p>
              <div className="text-xs space-y-1 text-muted">
                <p>امتیاز: <span className="text-primary font-bold">{detail.lead.lead_score}</span></p>
                <p>{detail.lead.office_name}</p>
                <p>{detail.lead.request_type} · {detail.lead.budget}</p>
                <p>{detail.lead.province} {detail.lead.city}</p>
                <p className="whitespace-pre-wrap">{detail.lead.description}</p>
              </div>
            </div>
          )}
          <div className="p-3">
            <p className="font-medium text-sm flex items-center gap-2 mb-2">
              <Users className="h-4 w-4" /> بازدیدکنندگان آنلاین
            </p>
            <div className="space-y-2">
              {live?.map((v) => (
                <div key={v.visitor_id} className="text-xs border border-card-border rounded-lg p-2">
                  <p className="font-medium">{v.name || v.mobile || 'مهمان'}</p>
                  <p className="text-muted truncate">{v.current_page}</p>
                  <p className="text-muted">امتیاز {v.lead_score} · {Math.floor((v.time_on_site_seconds ?? 0) / 60)}د</p>
                </div>
              ))}
              {(live?.length ?? 0) === 0 && <p className="text-xs text-muted">فعلاً کسی آنلاین نیست</p>}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
