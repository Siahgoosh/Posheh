import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

interface Campaign {
  id: number
  subject: string
  body_html: string
  segment: string
  status: string
  sent_count?: number
  failed_count?: number
  sent_at?: string
}

export function AdminEmailMarketingPage() {
  const [subject, setSubject] = useState('')
  const [body, setBody] = useState('')
  const [segment, setSegment] = useState('all_managers')
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-email-campaigns'],
    queryFn: async () => {
      const res = await api.get('/admin/email-campaigns')
      return res.data as { data: Campaign[]; meta: { segments: Record<string, string> } }
    },
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/admin/email-campaigns', {
      subject,
      body_html: `<div dir="rtl" style="font-family:Tahoma,sans-serif;line-height:1.7">${body}</div>`,
      segment,
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-email-campaigns'] })
      setSubject('')
      setBody('')
    },
  })

  const sendMutation = useMutation({
    mutationFn: (id: number) => api.post(`/admin/email-campaigns/${id}/send`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-email-campaigns'] }),
  })

  const segments = data?.meta?.segments ?? {}

  return (
    <div className="space-y-6 animate-fade-in max-w-3xl">
      <AdminPageHeader
        title="ایمیل مارکتینگ"
        description="ارسال کمپین ایمیل به مدیران دفاتر و کاربران پلتفرم"
      />

      <Card>
        <CardHeader><CardTitle>کمپین جدید</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <Input placeholder="موضوع ایمیل" value={subject} onChange={(e) => setSubject(e.target.value)} />
          <select
            className="w-full rounded-xl border border-card-border bg-background px-3 py-2 text-sm"
            value={segment}
            onChange={(e) => setSegment(e.target.value)}
          >
            {Object.entries(segments).map(([key, label]) => (
              <option key={key} value={key}>{label}</option>
            ))}
          </select>
          <textarea
            className="w-full rounded-xl border border-card-border bg-background p-3 text-sm min-h-[140px]"
            placeholder="متن ایمیل (HTML ساده پشتیبانی می‌شود)"
            value={body}
            onChange={(e) => setBody(e.target.value)}
          />
          <Button
            onClick={() => createMutation.mutate()}
            disabled={!subject.trim() || !body.trim() || createMutation.isPending}
          >
            {createMutation.isPending ? 'در حال ایجاد...' : 'ذخیره کمپین'}
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>کمپین‌ها</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {isLoading && <p className="text-sm text-muted">بارگذاری…</p>}
          {data?.data?.map((c) => (
            <div key={c.id} className="border-b border-card-border pb-3 last:border-0">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <p className="font-medium">{c.subject}</p>
                  <p className="text-xs text-muted mt-0.5">
                    {segments[c.segment] ?? c.segment}
                    {c.sent_at && ` · ارسال ${c.sent_at.slice(0, 10)}`}
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={c.status === 'sent' ? 'default' : 'outline'}>
                    {c.status === 'sent' ? `ارسال شد (${c.sent_count ?? 0})` : 'draft'}
                  </Badge>
                  {c.status !== 'sent' && (
                    <Button
                      size="sm"
                      onClick={() => sendMutation.mutate(c.id)}
                      disabled={sendMutation.isPending}
                    >
                      ارسال الان
                    </Button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
