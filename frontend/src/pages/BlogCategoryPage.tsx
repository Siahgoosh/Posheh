import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Calendar, Clock } from 'lucide-react'
import api from '@/lib/api'
import { SeoHead } from '@/components/seo/SeoHead'
import { SeoBreadcrumb, getBreadcrumbJsonLd } from '@/components/seo/SeoBreadcrumb'
import { getSiteUrl } from '@/lib/seo'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'

interface BlogPostItem {
  slug: string
  title: string
  excerpt?: string
  category_label?: string
  reading_time?: number
  published_at_jalali?: string
}

interface CategoryMeta {
  slug: string
  label: string
  count: number
}

export function BlogCategoryPage() {
  const { category } = useParams<{ category: string }>()

  const { data: categories } = useQuery({
    queryKey: ['blog-categories'],
    queryFn: async () => (await api.get('/blog/categories')).data.data as CategoryMeta[],
  })

  const catMeta = categories?.find((c) => c.slug === category)
  const label = catMeta?.label ?? category ?? 'دسته‌بندی'

  const { data, isLoading } = useQuery({
    queryKey: ['blog', 'category', category],
    queryFn: async () => {
      const res = await api.get('/blog', { params: { category, per_page: 50 } })
      return res.data.data as BlogPostItem[]
    },
    enabled: !!category,
  })

  const breadcrumbs = [
    { label: 'خانه', href: '/' },
    { label: 'وبلاگ', href: '/blog' },
    { label },
  ]

  return (
    <>
      <SeoHead
        title={`${label} | وبلاگ پوشه`}
        description={`مقالات تخصصی ${label} برای مشاوران و مدیران دفاتر املاک.`}
        path={`/blog/category/${category}`}
        jsonLd={getBreadcrumbJsonLd(breadcrumbs, getSiteUrl())}
      />

      <div className="min-h-screen bg-background">
        <header className="border-b border-card-border glass sticky top-0 z-50">
          <div className="container mx-auto max-w-4xl flex h-16 items-center justify-between px-4">
            <Link to="/" className="font-bold gradient-text">پوشه</Link>
            <Link to="/blog"><Button variant="ghost" size="sm">وبلاگ</Button></Link>
          </div>
        </header>

        <main className="container mx-auto max-w-4xl px-4 py-12">
          <SeoBreadcrumb items={breadcrumbs} />
          <h1 className="text-3xl font-bold mb-2">{label}</h1>
          <p className="text-muted mb-8">{catMeta?.count ?? data?.length ?? 0} مقاله</p>

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
                    {post.excerpt && <p className="text-muted text-sm mb-3">{post.excerpt}</p>}
                    <div className="flex gap-4 text-xs text-muted">
                      <span className="flex items-center gap-1"><Calendar className="h-3 w-3" />{post.published_at_jalali}</span>
                      <span className="flex items-center gap-1"><Clock className="h-3 w-3" />{post.reading_time} دقیقه</span>
                    </div>
                  </Card>
                </Link>
              ))}
              {!data?.length && <p className="text-center text-muted py-12">مقاله‌ای در این دسته منتشر نشده.</p>}
            </div>
          )}
        </main>
      </div>
    </>
  )
}
