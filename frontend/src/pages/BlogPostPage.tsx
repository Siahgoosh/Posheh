import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { useMemo } from 'react'
import { ArrowRight, Calendar, Clock } from 'lucide-react'
import api from '@/lib/api'
import { SeoHead } from '@/components/seo/SeoHead'
import { SeoBreadcrumb, getBreadcrumbJsonLd, getFaqJsonLd } from '@/components/seo/SeoBreadcrumb'
import { getArticleJsonLd, getSiteUrl } from '@/lib/seo'
import { PRIMARY_KEYWORDS_STRING } from '@/constants/seo'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { SiteFooter } from '@/components/layout/SiteFooter'

interface FaqItem {
  question: string
  answer: string
}

interface BlogPostDetail {
  slug: string
  title: string
  excerpt?: string
  content: string
  meta_title?: string
  meta_description?: string
  keywords?: string
  category_slug?: string
  category_label?: string
  author_name?: string
  reading_time?: number
  cover_image?: string
  published_at?: string
  published_at_jalali?: string
  updated_at?: string
  faq?: FaqItem[]
  cta_text?: string
  cta_url?: string
  related?: { slug: string; title: string; excerpt?: string }[]
}

function extractToc(html: string): { id: string; text: string }[] {
  const matches = [...html.matchAll(/<h2[^>]*>(.*?)<\/h2>/gi)]
  return matches.map((m, i) => ({
    id: `section-${i + 1}`,
    text: m[1].replace(/<[^>]+>/g, '').trim(),
  }))
}

function injectHeadingIds(html: string): string {
  let i = 0
  return html.replace(/<h2([^>]*)>/gi, () => {
    i += 1
    return `<h2 id="section-${i}"$1>`
  })
}

export function BlogPostPage() {
  const { slug } = useParams<{ slug: string }>()

  const { data: post, isLoading, error } = useQuery({
    queryKey: ['blog', slug],
    queryFn: async () => {
      const res = await api.get(`/blog/${slug}`)
      return res.data.data as BlogPostDetail
    },
    enabled: !!slug,
  })

  const toc = useMemo(() => (post ? extractToc(post.content) : []), [post])
  const contentWithIds = useMemo(() => (post ? injectHeadingIds(post.content) : ''), [post])

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  if (error || !post) {
    return (
      <>
        <SeoHead title="مقاله یافت نشد" noindex path={`/blog/${slug}`} />
        <div className="min-h-screen flex flex-col items-center justify-center gap-4">
          <p className="text-muted">مقاله یافت نشد</p>
          <Link to="/blog"><Button variant="outline">بازگشت به وبلاگ</Button></Link>
        </div>
      </>
    )
  }

  const breadcrumbs = [
    { label: 'خانه', href: '/' },
    { label: 'وبلاگ', href: '/blog' },
    ...(post.category_slug && post.category_label
      ? [{ label: post.category_label, href: `/blog/category/${post.category_slug}` }]
      : []),
    { label: post.title },
  ]

  const articleJsonLd = getArticleJsonLd({
    title: post.title,
    description: post.meta_description || post.excerpt,
    slug: post.slug,
    image: post.cover_image,
    authorName: post.author_name,
    publishedAt: post.published_at,
    modifiedAt: post.updated_at,
    keywords: post.keywords || PRIMARY_KEYWORDS_STRING,
    section: post.category_label,
  })

  const faqLd = getFaqJsonLd(post.faq ?? [])
  const jsonLd = [articleJsonLd, getBreadcrumbJsonLd(breadcrumbs, getSiteUrl()), ...(faqLd ? [faqLd] : [])]

  return (
    <>
      <SeoHead
        title={post.meta_title || post.title}
        description={post.meta_description || post.excerpt}
        keywords={post.keywords || PRIMARY_KEYWORDS_STRING}
        path={`/blog/${post.slug}`}
        image={post.cover_image || '/og-default.svg'}
        type="article"
        articlePublishedTime={post.published_at}
        articleModifiedTime={post.updated_at}
        jsonLd={jsonLd}
      />

      <div className="min-h-screen bg-background flex flex-col">
        <header className="border-b border-card-border glass sticky top-0 z-50">
          <div className="container mx-auto max-w-3xl flex h-16 items-center gap-3 px-4">
            <Link to="/blog"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
            <Link to="/" className="font-bold gradient-text">پوشه</Link>
          </div>
        </header>

        <article className="container mx-auto max-w-3xl px-4 py-10 flex-1">
          <SeoBreadcrumb items={breadcrumbs} />

          {post.category_label && (
            <Link to={`/blog/category/${post.category_slug}`}>
              <Badge variant="outline" className="mb-3">{post.category_label}</Badge>
            </Link>
          )}

          <h1 className="text-3xl md:text-4xl font-bold leading-tight mb-4">{post.title}</h1>
          <div className="flex flex-wrap items-center gap-4 text-sm text-muted mb-8">
            <span className="flex items-center gap-1"><Calendar className="h-4 w-4" />{post.published_at_jalali}</span>
            <span className="flex items-center gap-1"><Clock className="h-4 w-4" />{post.reading_time} دقیقه</span>
            <span>{post.author_name}</span>
          </div>

          {toc.length > 1 && (
            <nav className="mb-8 p-5 rounded-2xl border border-card-border bg-white/5">
              <p className="font-semibold mb-3 text-sm">فهرست مطالب</p>
              <ol className="space-y-2 text-sm text-muted list-decimal list-inside">
                {toc.map((item) => (
                  <li key={item.id}>
                    <a href={`#${item.id}`} className="hover:text-primary">{item.text}</a>
                  </li>
                ))}
              </ol>
            </nav>
          )}

          <div
            className="prose prose-invert max-w-none prose-headings:text-foreground prose-p:text-muted prose-p:leading-relaxed prose-a:text-primary prose-strong:text-foreground prose-li:text-muted"
            dangerouslySetInnerHTML={{ __html: contentWithIds }}
          />

          {post.faq && post.faq.length > 0 && (
            <section className="mt-12">
              <h2 className="text-xl font-bold mb-4">سوالات متداول</h2>
              <div className="space-y-4">
                {post.faq.map((f, i) => (
                  <div key={i} className="p-4 rounded-xl border border-card-border">
                    <h3 className="font-semibold mb-2">{f.question}</h3>
                    <p className="text-sm text-muted leading-relaxed">{f.answer}</p>
                  </div>
                ))}
              </div>
            </section>
          )}

          {post.related && post.related.length > 0 && (
            <section className="mt-12">
              <h2 className="text-lg font-bold mb-4">مقالات مرتبط</h2>
              <div className="space-y-3">
                {post.related.map((r) => (
                  <Link key={r.slug} to={`/blog/${r.slug}`} className="block p-4 rounded-xl border border-card-border glass-hover">
                    <p className="font-medium">{r.title}</p>
                    {r.excerpt && <p className="text-xs text-muted mt-1">{r.excerpt}</p>}
                  </Link>
                ))}
              </div>
            </section>
          )}

          <div className="mt-12 p-6 rounded-2xl bg-primary/10 border border-primary/20 text-center">
            <p className="font-medium mb-3">{post.cta_text || 'آماده مدیریت حرفه‌ای املاک هستید؟'}</p>
            <Link to={post.cta_url || '/register'}>
              <Button>شروع ۴۸ ساعت رایگان</Button>
            </Link>
          </div>
        </article>

        <SiteFooter />
      </div>
    </>
  )
}
