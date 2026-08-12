import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Pencil, Trash2, ArrowRight, Eye, Download } from 'lucide-react'
import api from '@/lib/api'
import { adminPath } from '@/lib/adminPaths'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface BlogPostRow {
  id: number
  title: string
  slug: string
  is_published: boolean
  review_status?: string
  views: number
  updated_at?: string
  rebuild_locked?: boolean
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

export function AdminBlogListPage() {
  const queryClient = useQueryClient()

  const { data: dash } = useQuery({
    queryKey: ['admin-blog-dashboard'],
    queryFn: async () => (await api.get('/admin/blog/dashboard')).data.data as Dashboard,
  })

  const { data, isLoading } = useQuery({
    queryKey: ['admin-blog'],
    queryFn: async () => {
      const res = await api.get('/admin/blog')
      return res.data.data as BlogPostRow[]
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/blog/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-blog'] })
      queryClient.invalidateQueries({ queryKey: ['admin-blog-dashboard'] })
    },
  })

  const bootstrapMutation = useMutation({
    mutationFn: () => api.post('/admin/blog/bootstrap'),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-blog-dashboard'] }),
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="icon" onClick={() => window.history.back()}>
            <ArrowRight className="h-5 w-5" />
          </Button>
          <div>
            <h1 className="text-2xl font-bold">مدیریت وبلاگ</h1>
            <p className="text-sm text-muted">CMS حرفه‌ای — Draft / Review / Publish</p>
          </div>
        </div>
        <div className="flex gap-2">
          <Link to={adminPath('seo-growth')}>
            <Button variant="outline">SEO Growth</Button>
          </Link>
          <Button variant="outline" onClick={() => bootstrapMutation.mutate()}>Bootstrap دسته‌ها</Button>
          <a href="/api/v1/admin/blog/export.csv" target="_blank" rel="noreferrer">
            <Button variant="outline"><Download className="h-4 w-4" /> خروجی CSV</Button>
          </a>
          <Link to={adminPath('blog/new')}>
            <Button>
              <Plus className="h-4 w-4" />
              مقاله جدید
            </Button>
          </Link>
        </div>
      </div>

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
            ['Redirect فعال', dash.redirects],
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

      {dash?.gsc && (
        <Card>
          <CardHeader><CardTitle className="text-base">Search Console</CardTitle></CardHeader>
          <CardContent className="text-sm text-muted">
            وضعیت: <strong>{dash.gsc.status}</strong> — {dash.gsc.message}
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle>مقالات</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <p className="text-muted text-sm">در حال بارگذاری…</p>
          ) : !data?.length ? (
            <p className="text-muted text-sm">هنوز مقاله‌ای ثبت نشده.</p>
          ) : (
            <div className="space-y-2">
              {data.map((post) => (
                <div
                  key={post.id}
                  className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-card-border px-4 py-3"
                >
                  <div>
                    <p className="font-medium">{post.title}</p>
                    <p className="text-xs text-muted">
                      /blog/{post.slug} · {post.review_status || (post.is_published ? 'published' : 'draft')}
                      {post.rebuild_locked ? ' · locked' : ''} · {post.views} بازدید
                    </p>
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
                        if (confirm('حذف این مقاله؟')) deleteMutation.mutate(post.id)
                      }}
                    >
                      <Trash2 className="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
