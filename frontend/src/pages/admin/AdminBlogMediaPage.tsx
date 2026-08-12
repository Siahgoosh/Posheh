import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, Trash2, Image as ImageIcon } from 'lucide-react'
import api from '@/lib/api'
import { adminPath } from '@/lib/adminPaths'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface MediaItem {
  path: string
  url: string
  size?: number
  usage_count: number
  unused: boolean
}

export function AdminBlogMediaPage() {
  const [q, setQ] = useState('')
  const [onlyUnused, setOnlyUnused] = useState(false)
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-blog-media', q],
    queryFn: async () => {
      const res = await api.get('/admin/blog/media', { params: { q, per_page: 48 } })
      return res.data as { data: MediaItem[]; meta: { total: number; unused: number } }
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (path: string) => api.delete('/admin/blog/media', { data: { path, confirm: true } }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-blog-media'] }),
    onError: (err: unknown) => {
      const axiosErr = err as { response?: { data?: { message?: string; usage?: unknown } } }
      alert(axiosErr.response?.data?.message || 'حذف ناموفق — بررسی استفاده فایل')
    },
  })

  const items = (data?.data ?? []).filter((i) => (onlyUnused ? i.unused : true))

  return (
    <div className="space-y-6 animate-fade-in max-w-6xl mx-auto">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Link to={adminPath('blog')}><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
          <div>
            <h1 className="text-2xl font-bold flex items-center gap-2"><ImageIcon className="h-6 w-6" /> کتابخانه رسانه</h1>
            <p className="text-sm text-muted">
              کل: {data?.meta.total ?? '—'} · بدون استفاده: {data?.meta.unused ?? '—'}
            </p>
          </div>
        </div>
        <div className="flex gap-2 items-center">
          <Input placeholder="جستجو مسیر…" value={q} onChange={(e) => setQ(e.target.value)} dir="ltr" className="w-48" />
          <label className="text-xs flex items-center gap-1">
            <input type="checkbox" checked={onlyUnused} onChange={(e) => setOnlyUnused(e.target.checked)} />
            فقط بدون استفاده
          </label>
        </div>
      </div>

      <Card>
        <CardHeader><CardTitle className="text-base">فایل‌های blog/</CardTitle></CardHeader>
        <CardContent>
          {isLoading ? <p className="text-sm text-muted">بارگذاری…</p> : (
            <div className="grid sm:grid-cols-3 lg:grid-cols-4 gap-3">
              {items.map((item) => (
                <div key={item.path} className="rounded-xl border border-card-border overflow-hidden">
                  <img src={item.url} alt="" className="h-28 w-full object-cover" loading="lazy" />
                  <div className="p-2 space-y-1">
                    <p className="text-[10px] text-muted truncate" dir="ltr">{item.path}</p>
                    <p className="text-xs">استفاده: {item.usage_count}{item.unused ? ' · بدون استفاده' : ''}</p>
                    <Button
                      size="sm"
                      variant="ghost"
                      disabled={deleteMutation.isPending}
                      onClick={() => {
                        if (confirm(item.unused ? 'حذف فایل بدون استفاده؟' : 'این فایل در حال استفاده است — مطمئنید؟')) {
                          deleteMutation.mutate(item.path)
                        }
                      }}
                    >
                      <Trash2 className="h-4 w-4 text-danger" />
                    </Button>
                  </div>
                </div>
              ))}
              {items.length === 0 && <p className="text-sm text-muted">موردی یافت نشد.</p>}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
