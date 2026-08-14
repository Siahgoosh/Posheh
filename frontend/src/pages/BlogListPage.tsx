import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { useState } from 'react'
import { Calendar, Clock, Search, Tag } from 'lucide-react'
import api from '@/lib/api'
import { SeoHead } from '@/components/seo/SeoHead'
import { SeoBreadcrumb, getBreadcrumbJsonLd } from '@/components/seo/SeoBreadcrumb'
import { getOrganizationJsonLd, getSiteUrl, getWebSiteJsonLd } from '@/lib/seo'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { SiteFooter } from '@/components/layout/SiteFooter'

interface BlogPostItem {
  slug: string
  title: string
  excerpt?: string
  cover_image?: string
  category_slug?: string
  category_label?: string
  author_name?: string
  reading_time?: number
  published_at_jalali?: string
  updated_at_jalali?: string
}

interface CategoryMeta {
  slug: string
  label: string
  count: number
}

export function BlogListPage() {
  const [q, setQ] = useState('')
  const [page, setPage] = useState(1)

  const { data: home } = useQuery({
    queryKey: ['blog-home'],
    queryFn: async () => (await api.get('/blog/home')).data.data as {
      featured?: BlogPostItem
      latest: BlogPostItem[]
      popular: BlogPostItem[]
      editors_picks: BlogPostItem[]
      categories: CategoryMeta[]
    },
  })

  const { data: list, isLoading } = useQuery({
    queryKey: ['blog', page],
    queryFn: async () => {
      const res = await api.get('/blog', { params: { per_page: 9, page } })
      return res.data as { data: BlogPostItem[]; meta: { current_page: number; last_page: number; total: number } }
    },
  })

  const breadcrumbs = [
    { label: 'خانه', href: '/' },
    { label: 'وبلاگ' },
  ]

  return (
    <>
      <SeoHead
        title="وبلاگ املاک و نرم‌افزار مدیریت دفتر"
        description="مرجع فارسی نرم افزار املاک، CRM مشاوران، ثبت ملک، حسابداری دفتر و تحول دیجیتال املاک در ایران."
        keywords="وبلاگ املاک, نرم افزار املاک, CRM املاک"
        path="/blog"
        jsonLd={[
          getOrganizationJsonLd(),
          getWebSiteJsonLd(),
          getBreadcrumbJsonLd(breadcrumbs, getSiteUrl()),
        ]}
      />

      <div className="min-h-screen bg-background flex flex-col">
        <header className="border-b border-card-border glass sticky top-0 z-50">
          <div className="container mx-auto max-w-5xl flex h-16 items-center justify-between px-4">
            <Link to="/" className="font-bold gradient-text text-lg">پوشه</Link>
            <Link to="/register"><Button size="sm">شروع ۴۸ ساعت رایگان</Button></Link>
          </div>
        </header>

        <main className="container mx-auto max-w-5xl px-4 py-10 flex-1">
          <SeoBreadcrumb items={breadcrumbs} />

          <section className="mb-10 rounded-3xl border border-card-border bg-gradient-to-bl from-primary/15 via-transparent to-transparent p-8 md:p-12">
            <p className="text-sm text-muted mb-2">وبلاگ پوشه</p>
            <h1 className="text-3xl md:text-5xl font-bold mb-4 leading-tight">مرجع عملی مدیریت دفتر املاک</h1>
            <p className="text-muted max-w-2xl mb-6 leading-relaxed">
              راهنماهای CRM، فایلینگ، قرارداد و تحول دیجیتال — برای مشاوران و مدیران آژانس در ایران.
            </p>
            <form
              className="flex flex-col sm:flex-row gap-2 max-w-xl"
              onSubmit={(e) => {
                e.preventDefault()
                if (q.trim()) window.location.href = `/blog/search?q=${encodeURIComponent(q.trim())}`
              }}
            >
              <div className="relative flex-1">
                <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted" />
                <input
                  value={q}
                  onChange={(e) => setQ(e.target.value)}
                  placeholder="جستجو در مقالات…"
                  className="w-full rounded-xl border border-card-border bg-background/80 pr-10 pl-4 py-3 text-sm"
                />
              </div>
              <Button type="submit">جستجو</Button>
            </form>
          </section>

          {home?.featured && (
            <section className="mb-12">
              <h2 className="text-xl font-bold mb-4">مقاله ویژه</h2>
              <ArticleCard post={home.featured} featured />
            </section>
          )}

          {home?.categories && home.categories.filter((c) => c.count > 0).length > 0 && (
            <div className="flex flex-wrap gap-2 mb-10">
              {home.categories.filter((c) => c.count > 0).map((cat) => (
                <Link key={cat.slug} to={`/blog/category/${cat.slug}`}>
                  <Badge variant="outline" className="gap-1 py-1.5 px-3 hover:border-primary">
                    <Tag className="h-3 w-3" />
                    {cat.label}
                    <span className="text-muted">({cat.count})</span>
                  </Badge>
                </Link>
              ))}
            </div>
          )}

          <section className="mb-12">
            <h2 className="text-xl font-bold mb-4">آخرین مقالات</h2>
            {isLoading ? (
              <div className="flex justify-center py-16">
                <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
              </div>
            ) : (
              <div className="grid md:grid-cols-2 gap-5">
                {(list?.data || home?.latest || []).map((post) => (
                  <ArticleCard key={post.slug} post={post} />
                ))}
              </div>
            )}
            {list && list.meta.last_page > 1 && (
              <div className="flex justify-center gap-2 mt-8">
                <Button variant="outline" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>قبلی</Button>
                <span className="text-sm text-muted self-center">صفحه {list.meta.current_page} از {list.meta.last_page}</span>
                <Button variant="outline" disabled={page >= list.meta.last_page} onClick={() => setPage((p) => p + 1)}>بعدی</Button>
              </div>
            )}
          </section>

          {home?.popular && home.popular.length > 0 && (
            <section className="mb-12">
              <h2 className="text-xl font-bold mb-4">محبوب</h2>
              <div className="space-y-3">
                {home.popular.map((post) => (
                  <Link key={post.slug} to={`/blog/${post.slug}`} className="block p-4 rounded-xl border border-card-border glass-hover">
                    {post.title}
                  </Link>
                ))}
              </div>
            </section>
          )}

          <div className="mt-8 p-8 rounded-2xl bg-primary/10 border border-primary/20 text-center">
            <h2 className="text-lg font-bold mb-2">پوشه — سامانه ابری مدیریت املاک</h2>
            <p className="text-muted text-sm mb-4">فایلینگ، CRM، حسابداری و قرارداد در یک پنل</p>
            <Link to="/register"><Button>شروع ۴۸ ساعت رایگان</Button></Link>
          </div>
        </main>
        <SiteFooter />
      </div>
    </>
  )
}

function ArticleCard({ post, featured = false }: { post: BlogPostItem; featured?: boolean }) {
  return (
    <Link to={`/blog/${post.slug}`} className={`block rounded-2xl border border-card-border overflow-hidden glass-hover ${featured ? 'md:flex' : ''}`}>
      {post.cover_image && (
        <img
          src={post.cover_image.startsWith('http') ? post.cover_image : post.cover_image}
          alt=""
          width={featured ? 640 : 480}
          height={featured ? 360 : 270}
          className={`object-cover bg-muted/20 ${featured ? 'md:w-1/2 h-56 md:h-auto' : 'w-full h-44'}`}
          loading={featured ? 'eager' : 'lazy'}
        />
      )}
      <div className="p-5 flex-1">
        {post.category_label && <Badge variant="outline" className="mb-2 text-xs">{post.category_label}</Badge>}
        <h3 className={`font-semibold mb-2 ${featured ? 'text-2xl' : 'text-lg'}`}>{post.title}</h3>
        {post.excerpt && <p className="text-muted text-sm leading-relaxed mb-3 line-clamp-3">{post.excerpt}</p>}
        <div className="flex flex-wrap gap-3 text-xs text-muted">
          {post.published_at_jalali && <span className="flex items-center gap-1"><Calendar className="h-3 w-3" />{post.published_at_jalali}</span>}
          {post.reading_time && <span className="flex items-center gap-1"><Clock className="h-3 w-3" />{post.reading_time} دقیقه</span>}
          {post.author_name && <span>{post.author_name}</span>}
        </div>
      </div>
    </Link>
  )
}
