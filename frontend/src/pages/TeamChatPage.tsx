import { useEffect, useRef, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { MessageCircle, Send } from 'lucide-react'
import api from '@/lib/api'
import { formatJalaliDate } from '@/lib/utils'
import { useAuthStore } from '@/stores/auth'
import { usePlanFeature } from '@/components/SubscriptionGuard'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface ChatMessage {
  id: number
  message: string
  created_at: string
  user?: { id: number; name: string; role?: string }
}

export function TeamChatPage() {
  const hasTeamChat = usePlanFeature('team')
  const { user } = useAuthStore()
  const queryClient = useQueryClient()
  const [text, setText] = useState('')
  const bottomRef = useRef<HTMLDivElement>(null)

  const { data: messages, isLoading } = useQuery({
    queryKey: ['team-chat'],
    queryFn: async () => (await api.get('/office/team-chat')).data.data as ChatMessage[],
    enabled: hasTeamChat,
    refetchInterval: 5000,
  })

  const sendMutation = useMutation({
    mutationFn: () => api.post('/office/team-chat', { message: text }),
    onSuccess: () => {
      setText('')
      queryClient.invalidateQueries({ queryKey: ['team-chat'] })
    },
  })

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages])

  if (!hasTeamChat) {
    return (
      <div className="p-8 text-center text-muted max-w-md mx-auto">
        <MessageCircle className="h-12 w-12 mx-auto mb-3 opacity-40" />
        <p>چت درون‌تیمی در پلن دفتر املاک و حرفه‌ای فعال است.</p>
      </div>
    )
  }

  return (
    <div className="max-w-2xl mx-auto animate-fade-in h-[calc(100vh-8rem)] flex flex-col">
      <div className="mb-4">
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <MessageCircle className="h-6 w-6 text-primary" /> چت تیمی
        </h1>
        <p className="text-sm text-muted">گفتگوی داخلی اعضای دفتر — به‌روزرسانی هر ۵ ثانیه</p>
      </div>

      <Card className="flex-1 flex flex-col min-h-0">
        <CardHeader className="pb-2 shrink-0">
          <CardTitle className="text-sm text-muted">{user?.office?.name}</CardTitle>
        </CardHeader>
        <CardContent className="flex-1 flex flex-col min-h-0 p-4 pt-0">
          <div className="flex-1 overflow-y-auto space-y-3 mb-3">
            {isLoading ? (
              <p className="text-sm text-muted text-center">بارگذاری…</p>
            ) : !messages?.length ? (
              <p className="text-sm text-muted text-center py-8">اولین پیام را بفرستید</p>
            ) : messages.map((m) => {
              const mine = m.user?.id === user?.id
              return (
                <div key={m.id} className={`flex ${mine ? 'justify-start' : 'justify-end'}`}>
                  <div className={`max-w-[80%] rounded-2xl px-3 py-2 text-sm ${mine ? 'bg-primary text-white rounded-br-sm' : 'bg-white/10 rounded-bl-sm'}`}>
                    {!mine && <p className="text-[10px] opacity-70 mb-0.5">{m.user?.name}</p>}
                    <p className="whitespace-pre-wrap break-words">{m.message}</p>
                    <p className={`text-[9px] mt-1 ${mine ? 'text-white/60' : 'text-muted'}`}>
                      {formatJalaliDate(m.created_at)}
                    </p>
                  </div>
                </div>
              )
            })}
            <div ref={bottomRef} />
          </div>
          <div className="flex gap-2 shrink-0">
            <input
              className="flex-1 rounded-xl border border-card-border bg-background/50 px-3 py-2.5 text-sm"
              placeholder="پیام به تیم…"
              value={text}
              onChange={(e) => setText(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && !e.shiftKey && text && sendMutation.mutate()}
            />
            <Button onClick={() => sendMutation.mutate()} disabled={!text.trim() || sendMutation.isPending}>
              <Send className="h-4 w-4" />
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
