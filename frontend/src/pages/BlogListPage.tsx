import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Calendar, Clock } from 'lucide-react'
import api from '@/lib/api'
import { SeoHead } from '@/components/seo/SeoHead'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'

interface BlogPostItem {
  slug: string
  title: string
  excerpt?: string
  author_name?: string
  reading_time?: number
  published_at_jalali?: string
}

export function BlogListPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['blog'],
    queryFn: async () => {
      const res = await api.get('/blog')
      return res.data.data as BlogPostItem[]
    },
  })

  return (
    <>
      <SeoHead
        title="وبلاگ املاک و نرم‌افزار مدیریت دفتر"
        description="مقالات تخصصی درباره نرم افزار املاک، CRM مشاوران، ثبت ملک و مدیریت دفتر املاک در ایران."
        keywords="وبلاگ املاک, نرم افزار املاک, CRM املاک, مدیریت دفتر املاک"
        path="/blog"
        jsonLd={{
          '@context': 'https://schema.org',
          '@type': 'Blog',
          name: 'وبلاگ پوشه',
          description: 'مقالات تخصصی نرم افزار و مدیریت املاک',
          url: `${window.location.origin}/blog`,
          inLanguage: 'fa-IR',
        }}
      />

      <div className="min-h-screen bg-background">
        <header className="border-b border-card-border glass sticky top-0 z-50">
          <div className="container mx-auto max-w-4xl flex h-16 items-center justify-between px-4">
            <Link to="/" className="font-bold gradient-text">پوشه</Link>
            <div className="flex gap-2">
              <Link to="/blog"><Button variant="ghost" size="sm">وبلاگ</Button></Link>
              <Link to="/login"><Button size="sm">ورود</Button></Link>
            </div>
          </div>
        </header>

        <main className="container mx-auto max-w-4xl px-4 py-12">
          <h1 className="text-3xl md:text-4xl font-bold mb-3">وبلاگ پوشه</h1>
          <p className="text-muted mb-10">راهنماها و مقالات تخصصی برای مشاوران و مدیران دفاتر املاک</p>

          {isLoading ? (
            <div className="flex justify-center py-20">
              <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
            </div>
          ) : (
            <div className="space-y-5">
              {data?.map((post) => (
                <Link key={post.slug} to={`/blog/${post.slug}`}>
                  <Card className="p-6 glass-hover">
                    <h2 className="text-xl font-semibold mb-2">{post.title}</h2>
                    {post.excerpt && <p className="text-muted text-sm leading-relaxed mb-4">{post.excerpt}</p>}
                    <div className="flex items-center gap-4 text-xs text-muted">
                      <span className="flex items-center gap-1"><Calendar className="h-3 w-3" />{post.published_at_jalali}</span>
                      <span className="flex items-center gap-1"><Clock className="h-3 w-3" />{post.reading_time} دقیقه مطالعه</span>
                    </div>
                  </Card>
                </Link>
              ))}
              {!data?.length && <p className="text-center text-muted py-12">مقاله‌ای منتشر نشده است.</p>}
            </div>
          )}

          <div className="mt-12 text-center">
            <Link to="/"><Button variant="outline">بازگشت به صفحه اصلی</Button></Link>
          </div>
        </main>
      </div>
    </>
  )
}
