import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Pencil, Trash2, ArrowRight, Eye } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface BlogPostRow {
  id: number
  title: string
  slug: string
  is_published: boolean
  views: number
  updated_at?: string
}

export function AdminBlogListPage() {
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-blog'],
    queryFn: async () => {
      const res = await api.get('/admin/blog')
      return res.data.data as BlogPostRow[]
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/blog/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-blog'] }),
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
            <p className="text-sm text-muted">فقط مدیر کل — انتشار مقالات سئو</p>
          </div>
        </div>
        <Link to="/admin/blog/new">
          <Button>
            <Plus className="h-4 w-4" />
            مقاله جدید
          </Button>
        </Link>
      </div>

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
                      /blog/{post.slug} · {post.is_published ? 'منتشر شده' : 'پیش‌نویس'} · {post.views} بازدید
                    </p>
                  </div>
                  <div className="flex gap-2">
                    {post.is_published && (
                      <a href={`/blog/${post.slug}`} target="_blank" rel="noreferrer">
                        <Button variant="ghost" size="sm"><Eye className="h-4 w-4" /></Button>
                      </a>
                    )}
                    <Link to={`/admin/blog/${post.id}/edit`}>
                      <Button variant="outline" size="sm"><Pencil className="h-4 w-4" /></Button>
                    </Link>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="text-danger"
                      onClick={() => {
                        if (window.confirm('حذف این مقاله؟')) deleteMutation.mutate(post.id)
                      }}
                    >
                      <Trash2 className="h-4 w-4" />
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
