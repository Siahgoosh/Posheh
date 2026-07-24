import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

export function AdminAnnouncementsPage() {
  const [title, setTitle] = useState('')
  const [content, setContent] = useState('')
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-announcements'],
    queryFn: async () => {
      const res = await api.get('/admin/announcements')
      return res.data.data as { id: number; title: string; content: string; type?: string }[]
    },
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/admin/announcements', { title, content, type: 'info' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-announcements'] })
      setTitle('')
      setContent('')
    },
  })

  return (
    <div className="space-y-6 animate-fade-in max-w-2xl">
      <AdminPageHeader title="اطلاعیه‌ها" />

      <Card>
        <CardHeader><CardTitle>اطلاعیه جدید</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <Input placeholder="عنوان" value={title} onChange={(e) => setTitle(e.target.value)} />
          <textarea
            className="w-full rounded-xl border border-card-border bg-background p-3 text-sm min-h-[100px]"
            placeholder="متن اطلاعیه"
            value={content}
            onChange={(e) => setContent(e.target.value)}
          />
          <Button onClick={() => createMutation.mutate()} disabled={!title || !content}>انتشار</Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>اطلاعیه‌های فعال</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : data?.map((a) => (
            <div key={a.id} className="border-b border-card-border pb-2">
              <p className="font-medium">{a.title}</p>
              <p className="text-sm text-muted">{a.content}</p>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
