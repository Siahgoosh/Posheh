import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { useMemo } from 'react'
import { ArrowRight, Calendar, Clock } from 'lucide-react'
import api from '@/lib/api'
import { SeoHead } from '@/components/seo/SeoHead'
import { SeoBreadcrumb, getBreadcrumbJsonLd, getFaqJsonLd } from '@/components/seo/SeoBreadcrumb'
import { getSiteUrl } from '@/lib/seo'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { SiteFooter } from '@/components/layout/SiteFooter'
import { LeadCaptureBlock } from '@/components/cro/LeadCaptureBlock'

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
  updated_at_jalali?: string
  faq?: FaqItem[]
  cta_text?: string
  cta_url?: string
  search_intent?: string
  category_slug?: string
  cro?: {
    id?: number | null
    key?: string
    title?: string
    description?: string
    button_text?: string
    url?: string
    type?: string
    funnel_stage?: string
  }
  related?: { slug: string; title: string; excerpt?: string }[]
  previous?: { slug: string; title: string } | null
  next?: { slug: string; title: string } | null
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
        <SeoHead title="مقاله یافت نشد" description="مقاله مورد نظر یافت نشد." path="/blog" noindex />
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

  const articleJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: post.title,
    description: post.meta_description || post.excerpt,
    image: post.cover_image ? [post.cover_image.startsWith('http') ? post.cover_image : `${getSiteUrl()}${post.cover_image}`] : undefined,
    author: { '@type': 'Organization', name: post.author_name || 'تیم پوشه' },
    publisher: {
      '@type': 'Organization',
      name: 'پوشه',
      logo: { '@type': 'ImageObject', url: `${getSiteUrl()}/favicon.svg` },
    },
    datePublished: post.published_at,
    dateModified: post.updated_at || post.published_at,
    inLanguage: 'fa-IR',
    mainEntityOfPage: `${getSiteUrl()}/blog/${post.slug}`,
    articleSection: post.category_label,
  }

  const faqLd = getFaqJsonLd(post.faq ?? [])
  const jsonLd = [articleJsonLd, getBreadcrumbJsonLd(breadcrumbs, getSiteUrl()), ...(faqLd ? [faqLd] : [])]

  return (
    <>
      <SeoHead
        title={post.meta_title || post.title}
        description={post.meta_description || post.excerpt}
        keywords={post.keywords}
        path={`/blog/${post.slug}`}
        type="article"
        image={post.cover_image ? (post.cover_image.startsWith('http') ? post.cover_image : `${getSiteUrl()}${post.cover_image}`) : undefined}
        publishedTime={post.published_at}
        modifiedTime={post.updated_at || post.published_at}
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
          {post.excerpt && <p className="text-muted leading-relaxed mb-4">{post.excerpt}</p>}
          <div className="flex flex-wrap items-center gap-4 text-sm text-muted mb-6">
            <span className="flex items-center gap-1"><Calendar className="h-4 w-4" />{post.published_at_jalali}</span>
            {post.updated_at_jalali && <span>به‌روزرسانی: {post.updated_at_jalali}</span>}
            <span className="flex items-center gap-1"><Clock className="h-4 w-4" />{post.reading_time} دقیقه</span>
            <span>{post.author_name}</span>
          </div>

          {post.cover_image && (
            <img
              src={post.cover_image.startsWith('http') ? post.cover_image : `${getSiteUrl()}${post.cover_image}`}
              alt={post.title}
              width={1200}
              height={630}
              className="w-full rounded-2xl mb-8 object-cover max-h-[420px]"
              loading="eager"
            />
          )}

          {toc.length > 1 && (
            <nav className="mb-8 p-5 rounded-2xl border border-card-border bg-white/5 md:sticky md:top-20">
              <details open className="md:open">
                <summary className="font-semibold mb-3 text-sm cursor-pointer">فهرست مطالب</summary>
                <ol className="space-y-2 text-sm text-muted list-decimal list-inside">
                  {toc.map((item) => (
                    <li key={item.id}>
                      <a href={`#${item.id}`} className="hover:text-primary scroll-smooth">{item.text}</a>
                    </li>
                  ))}
                </ol>
              </details>
            </nav>
          )}

          <div
            className="prose prose-invert max-w-none prose-headings:text-foreground prose-p:text-muted prose-p:leading-relaxed prose-a:text-primary prose-strong:text-foreground prose-li:text-muted prose-table:block prose-table:overflow-x-auto"
            dangerouslySetInnerHTML={{ __html: contentWithIds }}
          />

          <div className="mt-8 flex flex-wrap gap-2 text-sm">
            <span className="text-muted self-center ml-2">اشتراک‌گذاری:</span>
            <a className="px-3 py-1.5 rounded-lg border border-card-border" href={`https://t.me/share/url?url=${encodeURIComponent(`${getSiteUrl()}/blog/${post.slug}`)}&text=${encodeURIComponent(post.title)}`} target="_blank" rel="noreferrer">تلگرام</a>
            <a className="px-3 py-1.5 rounded-lg border border-card-border" href={`https://wa.me/?text=${encodeURIComponent(`${post.title} ${getSiteUrl()}/blog/${post.slug}`)}`} target="_blank" rel="noreferrer">واتساپ</a>
            <a className="px-3 py-1.5 rounded-lg border border-card-border" href={`https://twitter.com/intent/tweet?url=${encodeURIComponent(`${getSiteUrl()}/blog/${post.slug}`)}&text=${encodeURIComponent(post.title)}`} target="_blank" rel="noreferrer">X</a>
            <button
              type="button"
              className="px-3 py-1.5 rounded-lg border border-card-border"
              onClick={() => navigator.clipboard?.writeText(`${getSiteUrl()}/blog/${post.slug}`)}
            >
              کپی لینک
            </button>
          </div>
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

          <div className="mt-12">
            <LeadCaptureBlock
              variant={post.search_intent === 'commercial' || post.search_intent === 'transactional' ? 'specialized' : 'short'}
              source="ARTICLE"
              articleSlug={post.slug}
              categorySlug={post.category_slug}
              intent={post.search_intent}
              cta={post.cro || {
                title: post.cta_text || 'آماده مدیریت حرفه‌ای املاک هستید؟',
                button_text: post.cro?.button_text || 'آشنایی با پوشه',
                url: post.cta_url || '/register',
              }}
              showSticky
            />
          </div>

          <div className="mt-10 grid sm:grid-cols-2 gap-4">
            {post.previous && (
              <Link to={`/blog/${post.previous.slug}`} className="p-4 rounded-xl border border-card-border text-sm">
                <span className="text-muted block mb-1">قبلی</span>
                {post.previous.title}
              </Link>
            )}
            {post.next && (
              <Link to={`/blog/${post.next.slug}`} className="p-4 rounded-xl border border-card-border text-sm sm:text-left">
                <span className="text-muted block mb-1">بعدی</span>
                {post.next.title}
              </Link>
            )}
          </div>
        </article>

        <SiteFooter />
      </div>
    </>
  )
}
