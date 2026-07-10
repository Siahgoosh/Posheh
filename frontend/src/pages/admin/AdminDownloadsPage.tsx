import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, Plus, Save, Trash2 } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface Release {
  id: number
  platform: string
  version: string
  title: string
  description?: string
  download_url: string
  file_size?: string
  is_published: boolean
}

const emptyRelease = {
  platform: 'android',
  version: '1.0.0',
  title: '',
  description: '',
  download_url: '',
  file_size: '',
  is_published: true,
}

export function AdminDownloadsPage() {
  const queryClient = useQueryClient()
  const [form, setForm] = useState(emptyRelease)
  const [editingId, setEditingId] = useState<number | null>(null)

  const { data: releases, isLoading } = useQuery({
    queryKey: ['admin-releases'],
    queryFn: async () => {
      const res = await api.get('/admin/releases')
      return res.data.data as Release[]
    },
  })

  const saveMutation = useMutation({
    mutationFn: () =>
      editingId
        ? api.put(`/admin/releases/${editingId}`, form)
        : api.post('/admin/releases', form),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-releases'] })
      setForm(emptyRelease)
      setEditingId(null)
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/releases/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-releases'] }),
  })

  const startEdit = (release: Release) => {
    setEditingId(release.id)
    setForm({
      platform: release.platform,
      version: release.version,
      title: release.title,
      description: release.description ?? '',
      download_url: release.download_url,
      file_size: release.file_size ?? '',
      is_published: release.is_published,
    })
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-3xl mx-auto">
      <div className="flex items-center gap-3">
        <Link to="/admin/blog">
          <Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button>
        </Link>
        <div>
          <h1 className="text-2xl font-bold">مدیریت دانلودها</h1>
          <p className="text-sm text-muted">اندروید · ویندوز · PWA</p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{editingId ? 'ویرایش نسخه' : 'نسخه جدید'}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <select
            className="w-full rounded-xl border border-card-border bg-background/50 p-2 text-sm"
            value={form.platform}
            onChange={(e) => setForm((f) => ({ ...f, platform: e.target.value }))}
          >
            <option value="android">اندروید</option>
            <option value="windows">ویندوز</option>
            <option value="pwa">PWA</option>
          </select>
          <Input placeholder="نسخه" value={form.version} onChange={(e) => setForm((f) => ({ ...f, version: e.target.value }))} dir="ltr" />
          <Input placeholder="عنوان" value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} />
          <textarea
            className="w-full min-h-[72px] rounded-xl border border-card-border bg-background/50 p-3 text-sm"
            placeholder="توضیحات"
            value={form.description}
            onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
          />
          <Input placeholder="لینک دانلود" value={form.download_url} onChange={(e) => setForm((f) => ({ ...f, download_url: e.target.value }))} dir="ltr" />
          <Input placeholder="حجم فایل" value={form.file_size} onChange={(e) => setForm((f) => ({ ...f, file_size: e.target.value }))} />
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={form.is_published} onChange={(e) => setForm((f) => ({ ...f, is_published: e.target.checked }))} />
            نمایش در صفحه دانلود
          </label>
          <div className="flex gap-2">
            <Button onClick={() => saveMutation.mutate()} disabled={saveMutation.isPending}>
              <Save className="h-4 w-4" />
              ذخیره
            </Button>
            {editingId && (
              <Button variant="ghost" onClick={() => { setEditingId(null); setForm(emptyRelease) }}>
                انصراف
              </Button>
            )}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>نسخه‌های ثبت‌شده</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {isLoading ? (
            <p className="text-sm text-muted">بارگذاری…</p>
          ) : !releases?.length ? (
            <p className="text-sm text-muted">نسخه‌ای ثبت نشده.</p>
          ) : (
            releases.map((r) => (
              <div key={r.id} className="flex items-center justify-between gap-3 rounded-xl border border-card-border px-3 py-2 text-sm">
                <div>
                  <p className="font-medium">{r.title}</p>
                  <p className="text-xs text-muted">{r.platform} · v{r.version}</p>
                </div>
                <div className="flex gap-1">
                  <Button variant="outline" size="sm" onClick={() => startEdit(r)}>ویرایش</Button>
                  <Button variant="ghost" size="sm" className="text-danger" onClick={() => deleteMutation.mutate(r.id)}>
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            ))
          )}
        </CardContent>
      </Card>
    </div>
  )
}
