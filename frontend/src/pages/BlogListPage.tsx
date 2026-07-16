import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Calendar, Clock, Tag } from 'lucide-react'
import api from '@/lib/api'
import { SeoHead } from '@/components/seo/SeoHead'
import { SeoBreadcrumb, getBreadcrumbJsonLd } from '@/components/seo/SeoBreadcrumb'
import { getOrganizationJsonLd, getSiteUrl, getWebSiteJsonLd } from '@/lib/seo'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

interface BlogPostItem {
  slug: string
  title: string
  excerpt?: string
  category_slug?: string
  category_label?: string
  author_name?: string
  reading_time?: number
  published_at_jalali?: string
}

interface CategoryMeta {
  slug: string
  label: string
  count: number
}

export function BlogListPage() {
  const { data: categories } = useQuery({
    queryKey: ['blog-categories'],
    queryFn: async () => (await api.get('/blog/categories')).data.data as CategoryMeta[],
  })

  const { data, isLoading } = useQuery({
    queryKey: ['blog'],
    queryFn: async () => {
      const res = await api.get('/blog', { params: { per_page: 50 } })
      return res.data.data as BlogPostItem[]
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
        keywords="وبلاگ املاک, نرم افزار املاک, CRM املاک, مدیریت دفتر املاک, فایلینگ املاک"
        path="/blog"
        jsonLd={[
          getOrganizationJsonLd(),
          getWebSiteJsonLd(),
          {
            '@context': 'https://schema.org',
            '@type': 'Blog',
            name: 'وبلاگ پوشه',
            description: 'مرجع تخصصی نرم افزار و مدیریت املاک',
            url: `${getSiteUrl()}/blog`,
            inLanguage: 'fa-IR',
          },
          getBreadcrumbJsonLd(breadcrumbs, getSiteUrl()),
        ]}
      />

      <div className="min-h-screen bg-background">
        <header className="border-b border-card-border glass sticky top-0 z-50">
          <div className="container mx-auto max-w-4xl flex h-16 items-center justify-between px-4">
            <Link to="/" className="font-bold gradient-text">پوشه</Link>
            <div className="flex gap-2">
              <Link to="/register"><Button size="sm">شروع ۴۸ ساعت رایگان</Button></Link>
            </div>
          </div>
        </header>

        <main className="container mx-auto max-w-4xl px-4 py-12">
          <SeoBreadcrumb items={breadcrumbs} />
          <h1 className="text-3xl md:text-4xl font-bold mb-3">وبلاگ پوشه</h1>
          <p className="text-muted mb-8 leading-relaxed">
            بزرگ‌ترین مرجع فارسی نرم‌افزار املاک، CRM، فایلینگ و مدیریت دفتر — برای مشاوران و مدیران آژانس‌های املاک ایران
          </p>

          {categories && categories.length > 0 && (
            <div className="flex flex-wrap gap-2 mb-10">
              {categories.filter((c) => c.count > 0).map((cat) => (
                <Link key={cat.slug} to={`/blog/category/${cat.slug}`}>
                  <Badge variant="outline" className="gap-1 py-1.5 px-3 hover:border-primary cursor-pointer">
                    <Tag className="h-3 w-3" />
                    {cat.label}
                    <span className="text-muted">({cat.count})</span>
                  </Badge>
                </Link>
              ))}
            </div>
          )}

          {isLoading ? (
            <div className="flex justify-center py-20">
              <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
            </div>
          ) : (
            <div className="space-y-5">
              {data?.map((post) => (
                <Link key={post.slug} to={`/blog/${post.slug}`}>
                  <Card className="p-6 glass-hover">
                    {post.category_label && (
                      <Badge variant="outline" className="mb-2 text-xs">{post.category_label}</Badge>
                    )}
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

          <div className="mt-12 p-8 rounded-2xl bg-primary/10 border border-primary/20 text-center">
            <h2 className="text-lg font-bold mb-2">پوشه — سامانه ابری مدیریت املاک</h2>
            <p className="text-muted text-sm mb-4">فایلینگ، CRM، حسابداری، قرارداد و اپ اندروید/ویندوز</p>
            <Link to="/register"><Button>شروع ۴۸ ساعت رایگان</Button></Link>
          </div>
        </main>
      </div>
    </>
  )
}
