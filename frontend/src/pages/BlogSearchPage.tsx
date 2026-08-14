import { Link, useSearchParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { SeoHead } from '@/components/seo/SeoHead'
import { Button } from '@/components/ui/button'
import { SiteFooter } from '@/components/layout/SiteFooter'

export function BlogSearchPage() {
  const [params] = useSearchParams()
  const q = params.get('q') || ''

  const { data, isLoading } = useQuery({
    queryKey: ['blog-search', q],
    queryFn: async () => (await api.get('/blog/search', { params: { q } })).data as {
      data: { slug: string; title: string; excerpt?: string }[]
      meta: { total: number; query: string }
    },
    enabled: q.length >= 2,
  })

  return (
    <>
      <SeoHead title={`جستجو: ${q || 'وبلاگ'}`} description="نتایج جستجوی وبلاگ پوشه" path={`/blog/search?q=${encodeURIComponent(q)}`} noindex />
      <div className="min-h-screen bg-background flex flex-col">
        <header className="border-b border-card-border glass sticky top-0 z-50">
          <div className="container mx-auto max-w-3xl flex h-16 items-center justify-between px-4">
            <Link to="/blog" className="font-bold gradient-text">وبلاگ پوشه</Link>
          </div>
        </header>
        <main className="container mx-auto max-w-3xl px-4 py-10 flex-1">
          <h1 className="text-2xl font-bold mb-2">جستجو</h1>
          <p className="text-muted text-sm mb-6">{data?.meta.total ?? 0} نتیجه برای «{q}»</p>
          {isLoading && <p className="text-muted">در حال جستجو…</p>}
          {!isLoading && !data?.data?.length && (
            <div className="space-y-3">
              <p>نتیجه‌ای پیدا نشد.</p>
              <Link to="/blog"><Button variant="outline">بازگشت به وبلاگ</Button></Link>
            </div>
          )}
          <div className="space-y-4">
            {data?.data?.map((item) => (
              <Link key={item.slug} to={`/blog/${item.slug}`} className="block p-4 rounded-xl border border-card-border glass-hover">
                <p className="font-medium">{item.title}</p>
                {item.excerpt && <p className="text-sm text-muted mt-1">{item.excerpt}</p>}
              </Link>
            ))}
          </div>
        </main>
        <SiteFooter />
      </div>
    </>
  )
}
