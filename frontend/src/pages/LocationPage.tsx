import { Link, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { SeoHead } from '@/components/seo/SeoHead'
import { SeoBreadcrumb, getBreadcrumbJsonLd } from '@/components/seo/SeoBreadcrumb'
import { getSiteUrl } from '@/lib/seo'
import { Button } from '@/components/ui/button'
import { SiteFooter } from '@/components/layout/SiteFooter'
import { LeadCaptureBlock } from '@/components/cro/LeadCaptureBlock'

interface LocationDetail {
  name: string
  slug: string
  type: string
  description?: string
  unique_value?: string
  meta_title?: string
  meta_description?: string
  robots_directive?: string
  canonical_path?: string
  parent?: { name: string; slug: string; type: string } | null
  children?: { name: string; slug: string; type: string }[]
  articles?: { slug: string; title: string; excerpt?: string }[]
  properties?: { id: number; title: string; code?: string; city?: string; price?: number }[]
  knowledge?: { fact_type: string; fact: string; source?: string; fact_date?: string }[]
}

export function LocationPage() {
  const { slug } = useParams<{ slug: string }>()
  const { data, isLoading, error } = useQuery({
    queryKey: ['local-location', slug],
    queryFn: async () => (await api.get(`/local/locations/${slug}`)).data.data as LocationDetail,
    enabled: !!slug,
  })

  if (isLoading) return <div className="p-8 text-center text-muted">در حال بارگذاری…</div>
  if (error || !data) {
    return (
      <>
        <SeoHead title="صفحه یافت نشد" path={`/locations/${slug}`} noindex />
        <div className="p-8 text-center">صفحه مکان یافت نشد یا منتشر نشده است.</div>
      </>
    )
  }

  const crumbs = [
    { label: 'خانه', href: '/' },
    ...(data.parent ? [{ label: data.parent.name, href: `/locations/${data.parent.slug}` }] : []),
    { label: data.name, href: `/locations/${data.slug}` },
  ]

  const noindex = !!data.robots_directive?.toLowerCase().includes('noindex')

  return (
    <>
      <SeoHead
        title={data.meta_title || data.name}
        description={data.meta_description || data.unique_value || data.description}
        path={data.canonical_path || `/locations/${data.slug}`}
        robots={data.robots_directive}
        noindex={noindex}
        jsonLd={getBreadcrumbJsonLd(crumbs, getSiteUrl())}
      />
      <div className="min-h-screen bg-background flex flex-col">
        <header className="border-b border-card-border glass sticky top-0 z-50">
          <div className="container mx-auto max-w-3xl flex h-16 items-center justify-between px-4">
            <Link to="/" className="font-bold gradient-text">پوشه</Link>
            <Link to="/blog"><Button variant="ghost" size="sm">وبلاگ</Button></Link>
          </div>
        </header>
        <main className="container mx-auto max-w-3xl px-4 py-10 flex-1">
          <SeoBreadcrumb items={crumbs} />
          <p className="text-xs text-muted mb-2">{data.type}</p>
          <h1 className="text-3xl font-bold mb-4">{data.name}</h1>
          {data.unique_value && <p className="text-muted leading-relaxed mb-6">{data.unique_value}</p>}
          {data.description && <div className="prose prose-invert max-w-none mb-8 whitespace-pre-wrap">{data.description}</div>}

          {!!data.knowledge?.length && (
            <section className="mb-10">
              <h2 className="text-xl font-semibold mb-3">نکات محلی تأییدشده</h2>
              <ul className="space-y-3">
                {data.knowledge.map((k, i) => (
                  <li key={i} className="rounded-xl border border-card-border p-3 text-sm">
                    <p>{k.fact}</p>
                    <p className="text-xs text-muted mt-1">
                      {k.source ? `منبع: ${k.source}` : 'منبع ثبت نشده'}
                      {k.fact_date ? ` · ${k.fact_date}` : ''}
                    </p>
                  </li>
                ))}
              </ul>
            </section>
          )}

          {!!data.properties?.length && (
            <section className="mb-10">
              <h2 className="text-xl font-semibold mb-3">فایل‌های فعال مرتبط</h2>
              <div className="space-y-2">
                {data.properties.map((p) => (
                  <div key={p.id} className="rounded-xl border border-card-border px-3 py-2 text-sm">
                    <p className="font-medium">{p.title}</p>
                    <p className="text-xs text-muted">{p.code} · {p.city}</p>
                  </div>
                ))}
              </div>
            </section>
          )}

          {!!data.articles?.length && (
            <section className="mb-10">
              <h2 className="text-xl font-semibold mb-3">مقالات مرتبط</h2>
              <ul className="space-y-2">
                {data.articles.map((a) => (
                  <li key={a.slug}>
                    <Link className="text-primary" to={`/blog/${a.slug}`}>{a.title}</Link>
                  </li>
                ))}
              </ul>
            </section>
          )}

          {!!data.children?.length && (
            <section className="mb-10">
              <h2 className="text-xl font-semibold mb-3">مناطق مرتبط</h2>
              <div className="flex flex-wrap gap-2">
                {data.children.map((c) => (
                  <Link key={c.slug} to={`/locations/${c.slug}`} className="text-sm rounded-lg border border-card-border px-3 py-1">
                    {c.name}
                  </Link>
                ))}
              </div>
            </section>
          )}

          <LeadCaptureBlock
            source="LOCATION"
            categorySlug={data.type}
            variant="specialized"
          />
        </main>
        <SiteFooter />
      </div>
    </>
  )
}
