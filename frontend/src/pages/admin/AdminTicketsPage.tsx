import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

export function AdminTicketsPage() {
  const [replyId, setReplyId] = useState<number | null>(null)
  const [message, setMessage] = useState('')
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-tickets'],
    queryFn: async () => {
      const res = await api.get('/admin/tickets')
      return (res.data.data ?? res.data) as { id: number; subject: string; status: string; office?: { name: string }; user?: { name: string } }[]
    },
  })

  const replyMutation = useMutation({
    mutationFn: ({ id, message: msg }: { id: number; message: string }) =>
      api.post(`/admin/tickets/${id}/reply`, { message: msg }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-tickets'] })
      setReplyId(null)
      setMessage('')
    },
  })

  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      api.put(`/admin/tickets/${id}/status`, { status }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-tickets'] }),
  })

  const tickets = Array.isArray(data) ? data : []

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="تیکت‌های پشتیبانی" />

      <Card>
        <CardHeader><CardTitle>همه تیکت‌ها</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          {isLoading ? <p className="text-sm text-muted">بارگذاری…</p> : tickets.map((t) => (
            <div key={t.id} className="rounded-xl border border-card-border p-4 space-y-2 text-sm">
              <div className="flex flex-wrap justify-between gap-2">
                <span className="font-medium">{t.subject}</span>
                <Badge variant="outline">{t.status}</Badge>
              </div>
              <p className="text-muted">{t.office?.name} · {t.user?.name}</p>
              <div className="flex flex-wrap gap-2">
                <Button size="sm" variant="outline" onClick={() => setReplyId(t.id)}>پاسخ</Button>
                <Button size="sm" variant="outline" onClick={() => statusMutation.mutate({ id: t.id, status: 'closed' })}>بستن / حل شد</Button>
              </div>
              {replyId === t.id && (
                <div className="flex gap-2 pt-2">
                  <textarea
                    className="flex-1 rounded-xl border border-card-border bg-background p-2 text-sm min-h-[60px]"
                    value={message}
                    onChange={(e) => setMessage(e.target.value)}
                    placeholder="پاسخ پشتیبانی…"
                  />
                  <Button size="sm" onClick={() => replyMutation.mutate({ id: t.id, message })}>ارسال</Button>
                </div>
              )}
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
