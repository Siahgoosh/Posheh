import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowRight, Calendar, Clock } from 'lucide-react'
import api from '@/lib/api'
import { SeoHead } from '@/components/seo/SeoHead'
import { Button } from '@/components/ui/button'

interface BlogPostDetail {
  slug: string
  title: string
  excerpt?: string
  content: string
  meta_title?: string
  meta_description?: string
  keywords?: string
  author_name?: string
  reading_time?: number
  published_at_jalali?: string
  updated_at?: string
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

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  if (error || !post) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center gap-4">
        <p className="text-muted">مقاله یافت نشد</p>
        <Link to="/blog"><Button variant="outline">بازگشت به وبلاگ</Button></Link>
      </div>
    )
  }

  const articleJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: post.title,
    description: post.meta_description || post.excerpt,
    author: { '@type': 'Organization', name: post.author_name || 'تیم پوشه' },
    datePublished: post.published_at_jalali,
    dateModified: post.updated_at,
    inLanguage: 'fa-IR',
    mainEntityOfPage: `${window.location.origin}/blog/${post.slug}`,
  }

  return (
    <>
      <SeoHead
        title={post.meta_title || post.title}
        description={post.meta_description || post.excerpt}
        keywords={post.keywords}
        path={`/blog/${post.slug}`}
        type="article"
        jsonLd={articleJsonLd}
      />

      <div className="min-h-screen bg-background">
        <header className="border-b border-card-border glass sticky top-0 z-50">
          <div className="container mx-auto max-w-3xl flex h-16 items-center gap-3 px-4">
            <Link to="/blog"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
            <Link to="/" className="font-bold gradient-text">پوشه</Link>
          </div>
        </header>

        <article className="container mx-auto max-w-3xl px-4 py-10">
          <h1 className="text-3xl md:text-4xl font-bold leading-tight mb-4">{post.title}</h1>
          <div className="flex items-center gap-4 text-sm text-muted mb-8">
            <span className="flex items-center gap-1"><Calendar className="h-4 w-4" />{post.published_at_jalali}</span>
            <span className="flex items-center gap-1"><Clock className="h-4 w-4" />{post.reading_time} دقیقه</span>
            <span>{post.author_name}</span>
          </div>

          <div
            className="prose prose-invert max-w-none prose-headings:text-foreground prose-p:text-muted prose-p:leading-relaxed prose-a:text-primary prose-strong:text-foreground prose-li:text-muted"
            dangerouslySetInnerHTML={{ __html: post.content }}
          />

          <div className="mt-12 p-6 rounded-2xl bg-primary/10 border border-primary/20 text-center">
            <p className="font-medium mb-3">آماده مدیریت حرفه‌ای املاک هستید؟</p>
            <Link to="/login"><Button>شروع رایگان با پوشه</Button></Link>
          </div>
        </article>
      </div>
    </>
  )
}
