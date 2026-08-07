import { useState } from 'react'
import { Link, useParams, Navigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { motion } from 'framer-motion'
import {
  ArrowLeft, CheckCircle2, ChevronDown, Sparkles, Zap, Menu, X,
} from 'lucide-react'
import { SeoHead } from '@/components/seo/SeoHead'
import { SeoBreadcrumb, getBreadcrumbJsonLd, getFaqJsonLd } from '@/components/seo/SeoBreadcrumb'
import { SiteFooter } from '@/components/layout/SiteFooter'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import api from '@/lib/api'
import { getOrganizationJsonLd, getSiteUrl, getSoftwareJsonLd } from '@/lib/seo'
import {
  getLandingBySlug,
  landingPath,
  type KeywordLandingConfig,
} from '@/content/keywordLandings'

function LandingView({ config }: { config: KeywordLandingConfig }) {
  const [openFaq, setOpenFaq] = useState<number | null>(0)
  const [mobileNav, setMobileNav] = useState(false)
  const path = landingPath(config.slug)

  const { data: blogPosts } = useQuery({
    queryKey: ['landing-blog-posts'],
    queryFn: async () => {
      const res = await api.get('/blog', { params: { category: 'virtual-tour', per_page: 4 } })
      return (res.data.data ?? []) as { slug: string; title: string; excerpt?: string }[]
    },
    staleTime: 120000,
  })

  const breadcrumbs = [
    { label: 'خانه', href: '/' },
    { label: config.keyword },
  ]

  const faqLd = getFaqJsonLd(config.faq.map((f) => ({ question: f.q, answer: f.a })))
  const jsonLd = [
    getOrganizationJsonLd(),
    getSoftwareJsonLd(),
    {
      '@context': 'https://schema.org',
      '@type': 'WebPage',
      name: config.title,
      description: config.metaDescription,
      url: `${getSiteUrl()}${path}`,
      inLanguage: 'fa-IR',
      isPartOf: { '@type': 'WebSite', name: 'پوشه', url: getSiteUrl() },
    },
    getBreadcrumbJsonLd(breadcrumbs, getSiteUrl()),
    ...(faqLd ? [faqLd] : []),
  ]

  return (
    <div className="min-h-screen bg-[#06060a] text-white overflow-x-hidden">
      <SeoHead
        title={config.title}
        description={config.metaDescription}
        keywords={config.keywords}
        path={path}
        image={config.heroImage}
        jsonLd={jsonLd}
      />

      <div className="pointer-events-none fixed inset-0 -z-10">
        <div className={`absolute top-0 right-0 w-[min(100%,600px)] h-[500px] rounded-full blur-[120px] opacity-40 ${config.glow}`} />
        <div className="absolute bottom-0 left-0 w-96 h-96 rounded-full bg-primary/10 blur-[100px]" />
      </div>

      <header className="sticky top-0 z-50 border-b border-white/10 bg-black/50 backdrop-blur-xl">
        <div className="container mx-auto max-w-6xl flex h-14 sm:h-16 items-center justify-between px-4">
          <Link to="/" className="font-bold text-lg gradient-text shrink-0">پوشه</Link>
          <nav className="hidden md:flex items-center gap-6 text-sm text-white/70">
            <a href="#benefits" className="hover:text-white transition-colors">مزایا</a>
            <a href="#steps" className="hover:text-white transition-colors">چطور کار می‌کنه</a>
            <Link to="/blog" className="hover:text-white transition-colors">وبلاگ</Link>
          </nav>
          <div className="flex items-center gap-2">
            <Link to="/register" className="hidden sm:block">
              <Button size="sm" className="shadow-lg shadow-primary/25">ثبت‌نام رایگان</Button>
            </Link>
            <button type="button" className="md:hidden p-2" onClick={() => setMobileNav((v) => !v)} aria-label="منو">
              {mobileNav ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
            </button>
          </div>
        </div>
        {mobileNav && (
          <div className="md:hidden border-t border-white/10 px-4 py-3 space-y-2 bg-black/90">
            <a href="#benefits" className="block py-2 text-sm text-white/80" onClick={() => setMobileNav(false)}>مزایا</a>
            <a href="#steps" className="block py-2 text-sm text-white/80" onClick={() => setMobileNav(false)}>چطور کار می‌کنه</a>
            <Link to="/blog" className="block py-2 text-sm text-white/80">وبلاگ</Link>
            <Link to="/register" onClick={() => setMobileNav(false)}>
              <Button className="w-full mt-2">ثبت‌نام رایگان</Button>
            </Link>
          </div>
        )}
      </header>

      <section className="container mx-auto max-w-6xl px-4 pt-10 sm:pt-16 pb-16">
        <SeoBreadcrumb items={breadcrumbs} />

        <div className="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
          <motion.div
            initial={{ opacity: 0, y: 24 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
          >
            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-white/10 border border-white/15 mb-5">
              <Sparkles className="h-3.5 w-3.5 text-primary" />
              {config.heroBadge}
            </span>
            <h1 className="text-3xl sm:text-4xl lg:text-[2.75rem] font-bold leading-tight mb-5 tracking-tight">
              {config.heroTitle}
            </h1>
            <p className="text-base sm:text-lg text-white/65 leading-relaxed mb-8 max-w-xl">
              {config.heroSubtitle}
            </p>
            <div className="flex flex-col sm:flex-row gap-3">
              <Link to="/register">
                <Button size="lg" className="w-full sm:w-auto text-base px-8 h-12 shadow-xl shadow-primary/30">
                  {config.ctaTitle}
                </Button>
              </Link>
              <Link to="/blog/category/virtual-tour">
                <Button size="lg" variant="outline" className="w-full sm:w-auto border-white/20 text-white hover:bg-white/10">
                  مقالات رایگان
                </Button>
              </Link>
            </div>
            <p className="text-xs text-white/45 mt-4 flex items-center gap-2">
              <CheckCircle2 className="h-4 w-4 text-emerald-400 shrink-0" />
              {config.ctaSubtitle}
            </p>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, scale: 0.96 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ duration: 0.6, delay: 0.1 }}
            className="relative"
          >
            <div className={`absolute -inset-3 rounded-3xl bg-gradient-to-br ${config.accent} opacity-20 blur-2xl`} />
            <div className="relative rounded-2xl overflow-hidden border border-white/15 shadow-2xl aspect-[4/3]">
              <img
                src={config.heroImage}
                alt={config.keyword}
                className="w-full h-full object-cover"
                loading="eager"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent" />
              <div className="absolute bottom-4 left-4 right-4 flex items-center justify-between gap-2">
                <span className="text-sm font-medium px-3 py-1.5 rounded-lg bg-black/60 backdrop-blur border border-white/10">
                  {config.keyword}
                </span>
                <span className="text-xs text-white/80 px-2 py-1 rounded bg-primary/80">پوشه</span>
              </div>
            </div>
          </motion.div>
        </div>
      </section>

      <section className="container mx-auto max-w-6xl px-4 py-12 sm:py-16">
        <h2 className="text-xl sm:text-2xl font-bold mb-6">{config.painTitle}</h2>
        <div className="grid sm:grid-cols-2 gap-3">
          {config.painPoints.map((pain, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, x: -12 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.05 }}
              className="flex gap-3 p-4 rounded-xl bg-white/5 border border-white/10 text-sm text-white/75"
            >
              <span className="text-primary shrink-0">—</span>
              <span>{pain}</span>
            </motion.div>
          ))}
        </div>
      </section>

      <section id="benefits" className="container mx-auto max-w-6xl px-4 py-12 sm:py-16">
        <div className="text-center mb-10">
          <h2 className="text-2xl sm:text-3xl font-bold mb-2">پوشه چی کار می‌کنه؟</h2>
          <p className="text-white/55 text-sm sm:text-base">ساده، خودمونی، بدون کلاس درس</p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {config.benefits.map((b, i) => (
            <Card
              key={i}
              className="p-5 bg-white/5 border-white/10 hover:border-primary/40 hover:bg-white/[0.07] transition-all duration-300 group"
            >
              <div className={`w-10 h-10 rounded-xl bg-gradient-to-br ${config.accent} flex items-center justify-center mb-4 group-hover:scale-110 transition-transform`}>
                <b.icon className="h-5 w-5 text-white" />
              </div>
              <h3 className="font-semibold mb-2 text-sm sm:text-base">{b.title}</h3>
              <p className="text-xs sm:text-sm text-white/55 leading-relaxed">{b.desc}</p>
            </Card>
          ))}
        </div>
      </section>

      <section id="steps" className="container mx-auto max-w-6xl px-4 py-12 sm:py-16">
        <h2 className="text-2xl font-bold text-center mb-10">۳ قدم — همین امروز</h2>
        <div className="grid md:grid-cols-3 gap-6">
          {config.steps.map((step, i) => (
            <div key={i} className="relative text-center p-6 rounded-2xl border border-white/10 bg-white/[0.03]">
              <div className="w-10 h-10 mx-auto mb-4 rounded-full bg-primary/20 text-primary font-bold flex items-center justify-center text-lg">
                {i + 1}
              </div>
              <h3 className="font-semibold mb-2">{step.title}</h3>
              <p className="text-sm text-white/55">{step.desc}</p>
            </div>
          ))}
        </div>
      </section>

      {config.faq.length > 0 && (
        <section className="container mx-auto max-w-3xl px-4 py-12">
          <h2 className="text-xl font-bold mb-6 text-center">سوالی داری؟</h2>
          <div className="space-y-2">
            {config.faq.map((f, i) => (
              <div key={i} className="rounded-xl border border-white/10 overflow-hidden">
                <button
                  type="button"
                  className="w-full flex items-center justify-between gap-3 p-4 text-left text-sm font-medium hover:bg-white/5"
                  onClick={() => setOpenFaq(openFaq === i ? null : i)}
                >
                  {f.q}
                  <ChevronDown className={`h-4 w-4 shrink-0 transition-transform ${openFaq === i ? 'rotate-180' : ''}`} />
                </button>
                {openFaq === i && (
                  <p className="px-4 pb-4 text-sm text-white/60 leading-relaxed">{f.a}</p>
                )}
              </div>
            ))}
          </div>
        </section>
      )}

      {blogPosts && blogPosts.length > 0 && (
        <section className="container mx-auto max-w-6xl px-4 py-10">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-bold">مقالات مرتبط در وبلاگ</h2>
            <Link to="/blog/category/virtual-tour" className="text-sm text-primary hover:underline">
              همه مقالات تور مجازی
            </Link>
          </div>
          <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {blogPosts.map((post) => (
              <Link key={post.slug} to={`/blog/${post.slug}`}>
                <Card className="p-4 h-full border-white/10 bg-white/5 hover:border-primary/40 transition-colors">
                  <p className="font-medium text-sm leading-snug mb-1">{post.title}</p>
                  {post.excerpt && <p className="text-xs text-white/50 line-clamp-2">{post.excerpt}</p>}
                </Card>
              </Link>
            ))}
          </div>
        </section>
      )}

      {config.relatedSlugs.length > 0 && (
        <section className="container mx-auto max-w-6xl px-4 py-8">
          <p className="text-sm text-white/50 mb-3">صفحات مرتبط:</p>
          <div className="flex flex-wrap gap-2">
            {config.relatedSlugs.map((slug) => {
              const rel = getLandingBySlug(slug)
              if (!rel) return null
              return (
                <Link
                  key={slug}
                  to={landingPath(slug)}
                  className="text-xs px-3 py-1.5 rounded-full border border-white/15 bg-white/5 hover:border-primary/50 hover:text-primary transition-colors"
                >
                  {rel.keyword}
                </Link>
              )
            })}
          </div>
        </section>
      )}

      <section className="container mx-auto max-w-4xl px-4 py-16 sm:py-24">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className={`relative overflow-hidden rounded-3xl border border-white/15 p-8 sm:p-12 text-center bg-gradient-to-br ${config.accent}`}
        >
          <div className="absolute inset-0 bg-black/30" />
          <div className="relative z-10">
            <Zap className="h-10 w-10 mx-auto mb-4 text-white" />
            <h2 className="text-2xl sm:text-3xl font-bold mb-3">{config.ctaTitle}</h2>
            <p className="text-white/85 mb-8 max-w-lg mx-auto">{config.ctaSubtitle}</p>
            <Link to="/register">
              <Button size="lg" variant="secondary" className="text-base px-10 h-12 font-bold shadow-2xl">
                ثبت‌نام در نرم‌افزار پوشه — رایگان
              </Button>
            </Link>
            <p className="text-xs text-white/70 mt-4">بدون کارت بانکی · ۴۸ ساعت تست کامل · لغو هر وقت خواستی</p>
          </div>
        </motion.div>
      </section>

      <div className="container mx-auto max-w-6xl px-4 pb-8">
        <Link to="/" className="inline-flex items-center gap-2 text-sm text-white/50 hover:text-white transition-colors">
          <ArrowLeft className="h-4 w-4" />
          بازگشت به صفحه اصلی
        </Link>
      </div>

      <SiteFooter />
    </div>
  )
}

export function KeywordLandingPage() {
  const { slug } = useParams<{ slug: string }>()
  const config = slug ? getLandingBySlug(slug) : undefined
  if (!config) return <Navigate to="/" replace />
  return <LandingView config={config} />
}
