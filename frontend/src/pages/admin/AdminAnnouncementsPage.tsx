import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

export function AdminAnnouncementsPage() {
  const [title, setTitle] = useState('')
  const [content, setContent] = useState('')
  const [editingId, setEditingId] = useState<number | null>(null)
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-announcements'],
    queryFn: async () => {
      const res = await api.get('/admin/announcements')
      return res.data.data as { id: number; title: string; content: string; is_active?: boolean }[]
    },
  })

  const saveMutation = useMutation({
    mutationFn: () => editingId
      ? api.put(`/admin/announcements/${editingId}`, { title, content, is_active: true })
      : api.post('/admin/announcements', { title, content, type: 'info', is_active: true }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-announcements'] })
      setTitle('')
      setContent('')
      setEditingId(null)
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/announcements/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-announcements'] }),
  })

  return (
    <div className="space-y-6 animate-fade-in max-w-2xl">
      <AdminPageHeader title="اطلاعیه‌ها" />

      <Card>
        <CardHeader><CardTitle>{editingId ? 'ویرایش' : 'اطلاعیه جدید'}</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <Input placeholder="عنوان" value={title} onChange={(e) => setTitle(e.target.value)} />
          <textarea
            className="w-full rounded-xl border border-card-border bg-background p-3 text-sm min-h-[100px]"
            placeholder="متن اطلاعیه"
            value={content}
            onChange={(e) => setContent(e.target.value)}
          />
          <div className="flex gap-2">
            <Button onClick={() => saveMutation.mutate()} disabled={!title || !content}>
              {editingId ? 'ذخیره' : 'انتشار'}
            </Button>
            {editingId && <Button variant="ghost" onClick={() => { setEditingId(null); setTitle(''); setContent('') }}>انصراف</Button>}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>همه اطلاعیه‌ها</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : data?.map((a) => (
            <div key={a.id} className="border-b border-card-border pb-2">
              <div className="flex justify-between">
                <p className="font-medium">{a.title}</p>
                <div className="flex gap-1">
                  <Button size="sm" variant="ghost" onClick={() => { setEditingId(a.id); setTitle(a.title); setContent(a.content) }}>ویرایش</Button>
                  <Button size="sm" variant="ghost" className="text-danger" onClick={() => deleteMutation.mutate(a.id)}>حذف</Button>
                </div>
              </div>
              <p className="text-sm text-muted">{a.content}</p>
              <Badge variant="outline" className="mt-1">{a.is_active !== false ? 'فعال' : 'غیرفعال'}</Badge>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
