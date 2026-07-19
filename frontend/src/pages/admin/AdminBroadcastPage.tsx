import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { ArrowRight, Bell, Send } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

const PLATFORMS = [
  { id: 'web', label: 'وب' },
  { id: 'android', label: 'اندروید' },
  { id: 'ios', label: 'iOS' },
  { id: 'windows', label: 'ویندوز' },
]

export function AdminBroadcastPage() {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({
    title: '',
    body: '',
    link_url: '',
    image_url: '',
    action_label: 'مشاهده',
    priority: 'normal',
    target_platforms: ['web', 'android', 'windows'] as string[],
  })

  const { data: history } = useQuery({
    queryKey: ['admin-broadcasts'],
    queryFn: async () => (await api.get('/admin/broadcasts')).data,
  })

  const send = useMutation({
    mutationFn: async () => api.post('/admin/broadcasts', form),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-broadcasts'] })
      setForm((f) => ({ ...f, title: '', body: '' }))
    },
  })

  const togglePlatform = (id: string) => {
    setForm((f) => ({
      ...f,
      target_platforms: f.target_platforms.includes(id)
        ? f.target_platforms.filter((p) => p !== id)
        : [...f.target_platforms, id],
    }))
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Link to="/admin"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2"><Bell className="h-6 w-6 text-primary" />ارسال اعلان</h1>
          <p className="text-sm text-muted">وب، اندروید و ویندوز — داخل اپ و پنل</p>
        </div>
      </div>

      <Card className="glass">
        <CardHeader><CardTitle>اعلان جدید</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <Input placeholder="عنوان" value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} />
          <textarea
            className="w-full min-h-28 rounded-xl border border-card-border bg-background px-3 py-2 text-sm"
            placeholder="متن اعلان"
            value={form.body}
            onChange={(e) => setForm((f) => ({ ...f, body: e.target.value }))}
          />
          <Input placeholder="لینک (اختیاری)" dir="ltr" value={form.link_url} onChange={(e) => setForm((f) => ({ ...f, link_url: e.target.value }))} />
          <Input placeholder="آدرس تصویر (اختیاری)" dir="ltr" value={form.image_url} onChange={(e) => setForm((f) => ({ ...f, image_url: e.target.value }))} />
          <Input placeholder="متن دکمه" value={form.action_label} onChange={(e) => setForm((f) => ({ ...f, action_label: e.target.value }))} />
          <div className="flex flex-wrap gap-2">
            {PLATFORMS.map((p) => (
              <Button key={p.id} type="button" size="sm" variant={form.target_platforms.includes(p.id) ? 'default' : 'outline'} onClick={() => togglePlatform(p.id)}>
                {p.label}
              </Button>
            ))}
          </div>
          <Button onClick={() => send.mutate()} disabled={send.isPending || !form.title || !form.body}>
            <Send className="h-4 w-4" />
            ارسال به کاربران
          </Button>
        </CardContent>
      </Card>

      <Card className="glass">
        <CardHeader><CardTitle>تاریخچه</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {history?.data?.map((item: { id: number; title: string; body: string; sent_at: string }) => (
            <div key={item.id} className="p-3 rounded-xl border border-card-border">
              <p className="font-medium">{item.title}</p>
              <p className="text-sm text-muted mt-1">{item.body}</p>
            </div>
          )) ?? <p className="text-muted text-sm">هنوز اعلانی ارسال نشده.</p>}
        </CardContent>
      </Card>
    </div>
  )
}
