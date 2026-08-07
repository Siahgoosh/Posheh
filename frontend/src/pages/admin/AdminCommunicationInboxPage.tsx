import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { MessageCircle, Users, Send } from 'lucide-react'

interface ConversationItem {
  uuid: string
  status: string
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
  subject?: string
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
    is_internal?: boolean
    created_at: string
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

export function AdminCommunicationInboxPage() {
  const [selectedUuid, setSelectedUuid] = useState<string | null>(null)
  const [reply, setReply] = useState('')
  const queryClient = useQueryClient()

  const { data: stats } = useQuery({
    queryKey: ['comm-dashboard'],
    queryFn: async () => (await api.get('/admin/communication/dashboard')).data.data,
  })

  const { data: inbox } = useQuery({
    queryKey: ['comm-inbox'],
    queryFn: async () => (await api.get('/admin/communication/inbox')).data.data as ConversationItem[],
    refetchInterval: 8000,
  })

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
    mutationFn: () => api.post(`/admin/communication/conversations/${selectedUuid}/reply`, { body: reply }),
    onSuccess: () => {
      setReply('')
      queryClient.invalidateQueries({ queryKey: ['comm-conversation', selectedUuid] })
      queryClient.invalidateQueries({ queryKey: ['comm-inbox'] })
    },
  })

  return (
    <div className="space-y-4 animate-fade-in h-[calc(100dvh-6rem)] flex flex-col">
      <AdminPageHeader
        title="مرکز ارتباطات"
        description="اینباکس یکپارچه — چت وبسایت، سرنخ و بازدیدکنندگان آنلاین"
      />

      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 shrink-0">
        <Card><CardContent className="pt-4"><p className="text-xs text-muted">چت فعال</p><p className="text-xl font-bold">{stats?.active_chats ?? 0}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-xs text-muted">سرنخ امروز</p><p className="text-xl font-bold">{stats?.new_leads_today ?? 0}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-xs text-muted">آنلاین</p><p className="text-xl font-bold text-primary">{stats?.online_visitors ?? 0}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-xs text-muted">پیام خوانده‌نشده</p><p className="text-xl font-bold">{stats?.unread_messages ?? 0}</p></CardContent></Card>
      </div>

      <div className="flex-1 min-h-0 grid lg:grid-cols-[280px_1fr_300px] gap-3 border border-card-border rounded-2xl overflow-hidden bg-card/30">
        {/* Conversations list */}
        <div className="border-r border-card-border flex flex-col min-h-0">
          <div className="p-3 border-b border-card-border font-medium text-sm flex items-center gap-2">
            <MessageCircle className="h-4 w-4" /> گفتگوها
          </div>
          <div className="flex-1 overflow-y-auto">
            {inbox?.map((c) => (
              <button
                key={c.uuid}
                type="button"
                onClick={() => setSelectedUuid(c.uuid)}
                className={`w-full text-left p-3 border-b border-card-border/50 hover:bg-primary/5 transition-colors ${
                  selectedUuid === c.uuid ? 'bg-primary/10' : ''
                }`}
              >
                <div className="flex justify-between gap-2">
                  <span className="font-medium text-sm truncate">
                    {c.visitor?.name || c.lead?.office_name || c.subject || 'مهمان'}
                  </span>
                  {c.unread_operator > 0 && (
                    <Badge variant="default" className="shrink-0 text-[10px]">{c.unread_operator}</Badge>
                  )}
                </div>
                <p className="text-xs text-muted truncate mt-0.5">{c.last_message?.body}</p>
                {c.lead?.lead_score != null && (
                  <Badge variant="outline" className="mt-1 text-[10px]">امتیاز {c.lead.lead_score}</Badge>
                )}
              </button>
            ))}
          </div>
        </div>

        {/* Messages */}
        <div className="flex flex-col min-h-0 min-w-0">
          {detail ? (
            <>
              <div className="p-3 border-b border-card-border shrink-0">
                <p className="font-semibold">{detail.subject || detail.lead?.office_name || 'گفتگو'}</p>
                <p className="text-xs text-muted">{detail.lead?.mobile as string}</p>
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
                    <p className="text-[10px] text-muted mt-1">{m.created_at?.slice(11, 16)}</p>
                  </div>
                ))}
              </div>
              <div className="p-3 border-t border-card-border flex gap-2 shrink-0">
                <Input
                  placeholder="پاسخ..."
                  value={reply}
                  onChange={(e) => setReply(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && reply.trim() && replyMutation.mutate()}
                />
                <Button size="icon" onClick={() => replyMutation.mutate()} disabled={!reply.trim()}>
                  <Send className="h-4 w-4" />
                </Button>
              </div>
            </>
          ) : (
            <div className="flex-1 flex items-center justify-center text-muted text-sm">یک گفتگو انتخاب کنید</div>
          )}
        </div>

        {/* Sidebar: lead + live visitors */}
        <div className="border-l border-card-border flex flex-col min-h-0 overflow-y-auto">
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
