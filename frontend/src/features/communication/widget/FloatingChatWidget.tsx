import { useEffect, useState } from 'react'
import { useAuthStore } from '@/stores/auth'
import { communicationApi } from '../api/communicationApi'
import { useCommVisitorStore, createSessionKey } from '../store/visitorStore'
import { trackCommEvent } from '../tracking/visitorTracker'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { MessageCircle, X, Send, Loader2 } from 'lucide-react'

interface CommConfig {
  provinces: string[]
  activity_types: Record<string, string>
  request_types: Record<string, string>
}

interface Message {
  id: number
  sender_type: string
  body: string
  created_at: string
}

export function FloatingChatWidget() {
  const { user, isAuthenticated } = useAuthStore()
  const {
    visitorToken, sessionKey, conversationUuid,
    setConversationUuid, setLeadScore,
  } = useCommVisitorStore()

  const [open, setOpen] = useState(false)
  const [step, setStep] = useState<'form' | 'chat'>('form')
  const [config, setConfig] = useState<CommConfig | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [messages, setMessages] = useState<Message[]>([])
  const [draft, setDraft] = useState('')
  const [unread, setUnread] = useState(0)

  const [form, setForm] = useState({
    first_name: user?.name?.split(' ')[0] || '',
    last_name: user?.name?.split(' ').slice(1).join(' ') || '',
    mobile: user?.mobile || '',
    email: user?.email || '',
    province: user?.office?.city || '',
    city: '',
    office_name: user?.office?.name || '',
    role_title: '',
    staff_count: '',
    activity_type: '',
    request_type: '',
    budget: '',
    description: '',
  })

  useEffect(() => {
    communicationApi.config().then((res) => setConfig(res.data.data)).catch(() => {})
  }, [])

  useEffect(() => {
    if (!open || step !== 'chat' || !conversationUuid || !visitorToken) return
    const poll = async () => {
      try {
        const res = await communicationApi.getMessages(conversationUuid, visitorToken)
        const list = res.data.data as Message[]
        setMessages(list)
        const operatorMsgs = list.filter((m) => m.sender_type === 'operator')
        if (!open) setUnread(operatorMsgs.length)
      } catch { /* ignore */ }
    }
    poll()
    const t = setInterval(poll, 4000)
    return () => clearInterval(t)
  }, [open, step, conversationUuid, visitorToken])

  const openWidget = () => {
    setOpen(true)
    setUnread(0)
    trackCommEvent('chat_open')
    if (conversationUuid) setStep('chat')
    else if (isAuthenticated && user?.mobile) setStep('form')
  }

  const submitForm = async () => {
    if (!visitorToken) return
    setLoading(true)
    setError(null)
    try {
      const res = await communicationApi.captureLead({
        visitor_token: visitorToken,
        session_key: sessionKey || createSessionKey(),
        ...form,
        staff_count: form.staff_count ? Number(form.staff_count) : undefined,
        tracking_snapshot: {
          landing_page: sessionStorage.getItem('posheh_comm_landing'),
          current_page: window.location.pathname,
          referrer: document.referrer,
          user_agent: navigator.userAgent,
          screen: `${window.screen.width}x${window.screen.height}`,
          language: navigator.language,
          timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        },
      })
      const uuid = res.data.data.conversation_uuid as string
      setConversationUuid(uuid)
      setLeadScore(res.data.data.lead_score as number)
      trackCommEvent('form_submit')
      setStep('chat')
    } catch (e: unknown) {
      const msg = (e as { response?: { data?: { message?: string } } })?.response?.data?.message
      setError(msg || 'ثبت اطلاعات ناموفق بود.')
    } finally {
      setLoading(false)
    }
  }

  const sendMessage = async () => {
    if (!draft.trim() || !conversationUuid || !visitorToken) return
    const body = draft.trim()
    setDraft('')
    setMessages((prev) => [...prev, {
      id: Date.now(),
      sender_type: 'visitor',
      body,
      created_at: new Date().toISOString(),
    }])
    try {
      await communicationApi.sendMessage(conversationUuid, visitorToken, body)
      trackCommEvent('chat_message')
    } catch {
      setError('ارسال پیام ناموفق بود.')
    }
  }

  return (
    <>
      {!open && (
        <button
          type="button"
          onClick={openWidget}
          className="comm-fab fixed z-[9990] bottom-5 right-5 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg shadow-primary/40 transition-transform hover:scale-105 active:scale-95 safe-bottom"
          aria-label="چت با پوشه"
        >
          <span className="absolute inset-0 rounded-full bg-primary/40 animate-ping opacity-30" />
          <MessageCircle className="h-6 w-6 relative z-10" />
          {unread > 0 && (
            <span className="absolute -top-1 -right-1 z-20 min-w-[20px] h-5 px-1 rounded-full bg-danger text-white text-[10px] font-bold flex items-center justify-center">
              {unread}
            </span>
          )}
        </button>
      )}

      {open && (
        <div
          className="fixed z-[9991] bottom-5 right-5 w-[min(100vw-2rem,380px)] h-[min(85dvh,560px)] flex flex-col rounded-2xl border border-card-border bg-background shadow-2xl overflow-hidden safe-bottom"
          dir="rtl"
        >
          <header className="flex items-center justify-between px-4 py-3 border-b border-card-border bg-card/80 backdrop-blur shrink-0">
            <div>
              <p className="font-bold text-sm">پشتیبانی پوشه</p>
              <p className="text-[10px] text-muted">معمولاً در چند دقیقه پاسخ می‌دهیم</p>
            </div>
            <button type="button" onClick={() => setOpen(false)} className="p-1 text-muted hover:text-foreground">
              <X className="h-5 w-5" />
            </button>
          </header>

          {step === 'form' ? (
            <div className="flex-1 overflow-y-auto p-4 space-y-2 text-sm">
              <p className="text-xs text-muted mb-2">قبل از شروع گفتگو، لطفاً اطلاعات خود را وارد کنید.</p>
              <div className="grid grid-cols-2 gap-2">
                <Input placeholder="نام *" value={form.first_name} onChange={(e) => setForm({ ...form, first_name: e.target.value })} />
                <Input placeholder="نام خانوادگی" value={form.last_name} onChange={(e) => setForm({ ...form, last_name: e.target.value })} />
              </div>
              <Input placeholder="موبایل *" value={form.mobile} onChange={(e) => setForm({ ...form, mobile: e.target.value })} dir="ltr" />
              <Input placeholder="ایمیل" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} dir="ltr" />
              <select
                className="w-full rounded-xl border border-card-border bg-background px-3 py-2 text-sm"
                value={form.province}
                onChange={(e) => setForm({ ...form, province: e.target.value })}
              >
                <option value="">استان</option>
                {config?.provinces?.map((p) => <option key={p} value={p}>{p}</option>)}
              </select>
              <Input placeholder="شهر" value={form.city} onChange={(e) => setForm({ ...form, city: e.target.value })} />
              <Input placeholder="نام دفتر املاک" value={form.office_name} onChange={(e) => setForm({ ...form, office_name: e.target.value })} />
              <Input placeholder="سمت" value={form.role_title} onChange={(e) => setForm({ ...form, role_title: e.target.value })} />
              <Input placeholder="تعداد پرسنل" value={form.staff_count} onChange={(e) => setForm({ ...form, staff_count: e.target.value })} dir="ltr" />
              <select
                className="w-full rounded-xl border border-card-border bg-background px-3 py-2 text-sm"
                value={form.activity_type}
                onChange={(e) => setForm({ ...form, activity_type: e.target.value })}
              >
                <option value="">نوع فعالیت</option>
                {Object.entries(config?.activity_types ?? {}).map(([k, v]) => (
                  <option key={k} value={k}>{v}</option>
                ))}
              </select>
              <select
                className="w-full rounded-xl border border-card-border bg-background px-3 py-2 text-sm"
                value={form.request_type}
                onChange={(e) => setForm({ ...form, request_type: e.target.value })}
              >
                <option value="">نوع درخواست</option>
                {Object.entries(config?.request_types ?? {}).map(([k, v]) => (
                  <option key={k} value={k}>{v}</option>
                ))}
              </select>
              <Input placeholder="بودجه تقریبی" value={form.budget} onChange={(e) => setForm({ ...form, budget: e.target.value })} />
              <textarea
                className="w-full rounded-xl border border-card-border bg-background p-2 text-sm min-h-[72px]"
                placeholder="توضیحات"
                value={form.description}
                onChange={(e) => setForm({ ...form, description: e.target.value })}
              />
              {error && <p className="text-xs text-danger">{error}</p>}
              <Button
                className="w-full"
                disabled={!form.first_name.trim() || !form.mobile.trim() || loading}
                onClick={submitForm}
              >
                {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : 'شروع گفتگو'}
              </Button>
            </div>
          ) : (
            <>
              <div className="flex-1 overflow-y-auto p-3 space-y-2 bg-black/20">
                {messages.map((m) => (
                  <div
                    key={m.id}
                    className={`max-w-[85%] rounded-2xl px-3 py-2 text-sm ${
                      m.sender_type === 'visitor'
                        ? 'ml-auto bg-primary text-primary-foreground rounded-br-sm'
                        : 'mr-auto bg-card border border-card-border rounded-bl-sm'
                    }`}
                  >
                    {m.body}
                  </div>
                ))}
              </div>
              <div className="shrink-0 p-3 border-t border-card-border flex gap-2">
                <Input
                  placeholder="پیام خود را بنویسید..."
                  value={draft}
                  onChange={(e) => setDraft(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && sendMessage()}
                  className="flex-1"
                />
                <Button size="icon" onClick={sendMessage} disabled={!draft.trim()}>
                  <Send className="h-4 w-4" />
                </Button>
              </div>
            </>
          )}
        </div>
      )}
    </>
  )
}
