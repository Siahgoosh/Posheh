import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Pencil, Trash2, ArrowRight, Eye, Download, CalendarDays, Image as ImageIcon } from 'lucide-react'
import api from '@/lib/api'
import { adminPath } from '@/lib/adminPaths'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { REVIEW_STATUS_FA, labelFa } from '@/lib/blogLabelsFa'
import { BootstrapStatusBanner, useAdminBootstrap } from '@/lib/useAdminBootstrap'
import { extractApiError } from '@/lib/apiError'

interface BlogPostRow {
  id: number
  title: string
  slug: string
  is_published: boolean
  review_status?: string
  views: number
  updated_at?: string
  rebuild_locked?: boolean
  cover_image?: string
  category_label?: string
  author_name?: string
  word_count?: number
}

interface Dashboard {
  total: number
  published: number
  draft: number
  scheduled: number
  needs_review: number
  missing_image: number
  missing_meta: number
  redirects: number
  gsc?: { status: string; message: string }
  seo_health?: Record<string, string | number>
}

const STATUS_FILTER = ['draft', 'in_review', 'approved', 'scheduled', 'published', 'archived', 'trash'] as const

export function AdminBlogListPage() {
  const queryClient = useQueryClient()
  const [status, setStatus] = useState('')
  const [q, setQ] = useState('')
  const [selected, setSelected] = useState<number[]>([])

  const { data: dash, isError: dashError, error: dashErr } = useQuery({
    queryKey: ['admin-blog-dashboard'],
    queryFn: async () => (await api.get('/admin/blog/dashboard')).data.data as Dashboard,
  })

  const { data, isLoading, isError: listError, error: listErr } = useQuery({
    queryKey: ['admin-blog', status, q],
    queryFn: async () => {
      const res = await api.get('/admin/blog', {
        params: {
          review_status: status || undefined,
          q: q || undefined,
          per_page: 30,
        },
      })
      return { rows: res.data.data as BlogPostRow[], meta: res.data.meta as { total: number; last_page: number } }
    },
  })

  const bootstrap = useAdminBootstrap('/admin/blog/bootstrap', ['admin-blog-dashboard', 'admin-blog'])

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/blog/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-blog'] })
      queryClient.invalidateQueries({ queryKey: ['admin-blog-dashboard'] })
    },
  })

  const bulkMutation = useMutation({
    mutationFn: async (action: string) => {
      const actionFa =
        action === 'archive' ? 'آرشیو گروهی' : action === 'noindex' ? 'نویندکس گروهی' : action === 'index' ? 'ایندکس گروهی' : action
      const first = await api.post('/admin/blog/bulk', {
        ids: selected,
        action,
        confirm_affected: false,
        confirm_destructive: false,
      }).catch((err) => err.response)
      const affected = first?.data?.affected || first?.data?.count
      if (!confirm(`تعداد تحت تأثیر: ${JSON.stringify(affected)}\nادامه برای «${actionFa}»؟`)) return
      return api.post('/admin/blog/bulk', {
        ids: selected,
        action,
        confirm_affected: true,
        confirm_destructive: true,
      })
    },
    onSuccess: () => {
      setSelected([])
      queryClient.invalidateQueries({ queryKey: ['admin-blog'] })
    },
  })

  const toggle = (id: number) =>
    setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]))

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="icon" onClick={() => window.history.back()}>
            <ArrowRight className="h-5 w-5" />
          </Button>
          <div>
            <h1 className="text-2xl font-bold">مدیریت وبلاگ</h1>
            <p className="text-sm text-muted">پیش‌نویس → بررسی → زمان‌بندی → انتشار</p>
          </div>
        </div>
        <div className="flex flex-wrap gap-2">
          <Link to={adminPath('blog/calendar')}><Button variant="outline"><CalendarDays className="h-4 w-4" /> تقویم</Button></Link>
          <Link to={adminPath('blog/media')}><Button variant="outline"><ImageIcon className="h-4 w-4" /> رسانه</Button></Link>
          <Link to={adminPath('seo-growth')}><Button variant="outline">رشد سئو</Button></Link>
          <Link to={adminPath('seo-technical')}><Button variant="outline">سئوی فنی</Button></Link>
          <Link to={adminPath('seo-local')}><Button variant="outline">سئوی محلی</Button></Link>
          <Link to={adminPath('content-ops')}><Button variant="outline">عملیات محتوا</Button></Link>
          <Link to={adminPath('blog-images')}><Button variant="outline">تصاویر وبلاگ</Button></Link>
          <Link to={adminPath('cro')}><Button variant="outline">تبدیل / سرنخ</Button></Link>
          <Button type="button" variant="outline" onClick={() => bootstrap.run()} disabled={bootstrap.isPending}>
            {bootstrap.isPending ? 'در حال راه‌اندازی…' : 'راه‌اندازی اولیه'}
          </Button>
          <a href="/api/v1/admin/blog/export.csv" target="_blank" rel="noreferrer">
            <Button variant="outline"><Download className="h-4 w-4" /> خروجی CSV</Button>
          </a>
          <Link to={adminPath('blog/new')}>
            <Button><Plus className="h-4 w-4" /> مقاله جدید</Button>
          </Link>
        </div>
      </div>

      <BootstrapStatusBanner msg={bootstrap.msg} />
      {(dashError || listError) && (
        <div className="rounded-xl border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-200">
          {extractApiError(dashErr || listErr)}
        </div>
      )}

      {dash && (
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
          {[
            ['کل', dash.total],
            ['منتشر', dash.published],
            ['پیش‌نویس', dash.draft],
            ['زمان‌بندی', dash.scheduled],
            ['نیاز به بررسی', dash.needs_review],
            ['بدون تصویر', dash.missing_image],
            ['بدون متا', dash.missing_meta],
            ['ریدایرکت فعال', dash.redirects],
          ].map(([label, value]) => (
            <Card key={String(label)}>
              <CardContent className="p-4">
                <p className="text-xs text-muted">{label}</p>
                <p className="text-2xl font-bold">{value}</p>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {dash?.seo_health && (
        <Card>
          <CardHeader><CardTitle className="text-base">سلامت محتوا (داخلی)</CardTitle></CardHeader>
          <CardContent className="text-sm text-muted">
            {String(dash.seo_health.note || 'امتیاز داخلی — نمره گوگل نیست')}
          </CardContent>
        </Card>
      )}

      {dash?.gsc && (
        <Card>
          <CardHeader><CardTitle className="text-base">کنسول جستجوی گوگل</CardTitle></CardHeader>
          <CardContent className="text-sm text-muted space-y-1">
            <p>وضعیت: <strong>{dash.gsc.status}</strong></p>
            <p>{dash.gsc.message}</p>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="flex flex-wrap items-center justify-between gap-3">
            <span>مقالات</span>
            <div className="flex flex-wrap gap-2 font-normal">
              <Input placeholder="جستجو…" value={q} onChange={(e) => setQ(e.target.value)} className="w-40" />
              <select className="rounded-xl border border-card-border bg-background/50 px-3 text-sm" value={status} onChange={(e) => setStatus(e.target.value)}>
                <option value="">همه وضعیت‌ها</option>
                {STATUS_FILTER.map((s) => (
                  <option key={s} value={s}>{REVIEW_STATUS_FA[s] || s}</option>
                ))}
              </select>
              {selected.length > 0 && (
                <>
                  <Button size="sm" variant="outline" onClick={() => bulkMutation.mutate('archive')}>آرشیو گروهی</Button>
                  <Button size="sm" variant="outline" onClick={() => bulkMutation.mutate('noindex')}>نویندکس گروهی</Button>
                  <Button size="sm" variant="outline" onClick={() => bulkMutation.mutate('index')}>ایندکس گروهی</Button>
                </>
              )}
            </div>
          </CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <p className="text-muted text-sm">در حال بارگذاری…</p>
          ) : !data?.rows?.length ? (
            <p className="text-muted text-sm">هنوز مقاله‌ای ثبت نشده.</p>
          ) : (
            <div className="space-y-2">
              {data.rows.map((post) => {
                const statusLabel = labelFa(
                  REVIEW_STATUS_FA,
                  post.review_status || (post.is_published ? 'published' : 'draft'),
                )
                return (
                  <div key={post.id} className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-card-border px-4 py-3">
                    <div className="flex items-center gap-3 min-w-0">
                      <input type="checkbox" checked={selected.includes(post.id)} onChange={() => toggle(post.id)} />
                      {post.cover_image ? (
                        <img src={post.cover_image} alt="" className="h-10 w-14 object-cover rounded-md" />
                      ) : (
                        <div className="h-10 w-14 rounded-md bg-muted/30" />
                      )}
                      <div className="min-w-0">
                        <p className="font-medium truncate">{post.title}</p>
                        <p className="text-xs text-muted">
                          /blog/{post.slug} · {statusLabel}
                          {post.author_name ? ` · ${post.author_name}` : ''}
                          {post.category_label ? ` · ${post.category_label}` : ''}
                          {post.rebuild_locked ? ' · قفل‌شده' : ''} · {post.views} بازدید
                        </p>
                      </div>
                    </div>
                    <div className="flex gap-2">
                      {post.is_published && (
                        <a href={`/blog/${post.slug}`} target="_blank" rel="noreferrer">
                          <Button variant="ghost" size="sm"><Eye className="h-4 w-4" /></Button>
                        </a>
                      )}
                      <Link to={adminPath(`blog/${post.id}/edit`)}>
                        <Button variant="outline" size="sm"><Pencil className="h-4 w-4" /></Button>
                      </Link>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                          if (confirm('انتقال به سطل زباله؟')) deleteMutation.mutate(post.id)
                        }}
                      >
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    </div>
                  </div>
                )
              })}
              <p className="text-xs text-muted">جمع فیلتر: {data.meta?.total ?? data.rows.length}</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
