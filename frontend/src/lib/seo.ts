const SITE_NAME = 'پوشه'
const DEFAULT_DESCRIPTION = 'پوشه — سامانه ابری فایلینگ املاک، CRM املاک، حسابداری املاک، فروش ملک و اجاره ملک برای مشاوران ایران'

export function getSiteUrl(): string {
  if (typeof window !== 'undefined') {
    return import.meta.env.VITE_SITE_URL || window.location.origin
  }
  return import.meta.env.VITE_SITE_URL || 'https://posheapp.ir'
}

interface SeoProps {
  title?: string
  description?: string
  keywords?: string
  path?: string
  image?: string
  type?: 'website' | 'article'
  jsonLd?: Record<string, unknown> | Record<string, unknown>[]
  noindex?: boolean
  articlePublishedTime?: string
  articleModifiedTime?: string
}

function setMeta(name: string, content: string, attr: 'name' | 'property' = 'name') {
  if (!content) return
  let el = document.querySelector(`meta[${attr}="${name}"]`) as HTMLMetaElement | null
  if (!el) {
    el = document.createElement('meta')
    el.setAttribute(attr, name)
    document.head.appendChild(el)
  }
  el.content = content
}

function setCanonical(url: string) {
  let el = document.querySelector('link[rel="canonical"]') as HTMLLinkElement | null
  if (!el) {
    el = document.createElement('link')
    el.rel = 'canonical'
    document.head.appendChild(el)
  }
  el.href = url
}

function setJsonLd(data: Record<string, unknown> | Record<string, unknown>[]) {
  const id = 'seo-jsonld'
  document.getElementById(id)?.remove()
  const script = document.createElement('script')
  script.id = id
  script.type = 'application/ld+json'
  script.textContent = JSON.stringify(data)
  document.head.appendChild(script)
}

export function applySeo({
  title,
  description = DEFAULT_DESCRIPTION,
  keywords,
  path = '',
  image,
  type = 'website',
  jsonLd,
  noindex = false,
  articlePublishedTime,
  articleModifiedTime,
}: SeoProps) {
  const fullTitle = title ? `${title} | ${SITE_NAME}` : `${SITE_NAME} | فایلینگ و CRM املاک`
  const url = `${getSiteUrl()}${path}`
  const ogImage = image?.startsWith('http') ? image : image ? `${getSiteUrl()}${image}` : `${getSiteUrl()}/og-default.svg`

  document.title = fullTitle
  setMeta('description', description)
  if (keywords) setMeta('keywords', keywords)
  setMeta('robots', noindex ? 'noindex,nofollow' : 'index,follow')

  setMeta('og:title', fullTitle, 'property')
  setMeta('og:description', description, 'property')
  setMeta('og:url', url, 'property')
  setMeta('og:type', type, 'property')
  setMeta('og:image', ogImage, 'property')
  setMeta('og:locale', 'fa_IR', 'property')
  setMeta('og:site_name', SITE_NAME, 'property')

  if (type === 'article' && articlePublishedTime) {
    setMeta('article:published_time', articlePublishedTime, 'property')
  }
  if (type === 'article' && articleModifiedTime) {
    setMeta('article:modified_time', articleModifiedTime, 'property')
  }

  setMeta('twitter:card', 'summary_large_image')
  setMeta('twitter:title', fullTitle)
  setMeta('twitter:description', description)
  setMeta('twitter:image', ogImage)

  setCanonical(url)

  if (jsonLd) setJsonLd(jsonLd)
}

export function getArticleJsonLd(opts: {
  title: string
  description?: string
  slug: string
  image?: string
  authorName?: string
  publishedAt?: string
  modifiedAt?: string
  keywords?: string
  section?: string
}) {
  const url = `${getSiteUrl()}/blog/${opts.slug}`
  const image = opts.image?.startsWith('http') ? opts.image : opts.image ? `${getSiteUrl()}${opts.image}` : `${getSiteUrl()}/og-default.svg`

  return {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: opts.title,
    description: opts.description,
    image: [image],
    author: { '@type': 'Organization', name: opts.authorName || 'تیم پوشه' },
    publisher: {
      '@type': 'Organization',
      name: SITE_NAME,
      logo: { '@type': 'ImageObject', url: `${getSiteUrl()}/favicon.svg` },
    },
    datePublished: opts.publishedAt,
    dateModified: opts.modifiedAt || opts.publishedAt,
    inLanguage: 'fa-IR',
    mainEntityOfPage: url,
    url,
    ...(opts.section ? { articleSection: opts.section } : {}),
    ...(opts.keywords ? { keywords: opts.keywords.split(',').map((k) => k.trim()) } : {}),
  }
}

export function getOrganizationJsonLd() {
  return {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: SITE_NAME,
    url: getSiteUrl(),
    logo: `${getSiteUrl()}/favicon.svg`,
    description: DEFAULT_DESCRIPTION,
    email: 'info@posheapp.ir',
    contactPoint: {
      '@type': 'ContactPoint',
      email: 'support@posheapp.ir',
      contactType: 'customer support',
      availableLanguage: 'Persian',
    },
    areaServed: 'IR',
    inLanguage: 'fa-IR',
  }
}

export function getSoftwareJsonLd() {
  return {
    '@context': 'https://schema.org',
    '@type': 'SoftwareApplication',
    name: SITE_NAME,
    applicationCategory: 'BusinessApplication',
    operatingSystem: 'Web, Android, Windows',
    offers: {
      '@type': 'Offer',
      price: '590000',
      priceCurrency: 'IRR',
      description: 'پنل مشاور مستقل — ۴۸ ساعت رایگان',
    },
    description: DEFAULT_DESCRIPTION,
    inLanguage: 'fa-IR',
  }
}

export function getWebSiteJsonLd() {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: SITE_NAME,
    url: getSiteUrl(),
    inLanguage: 'fa-IR',
    potentialAction: {
      '@type': 'SearchAction',
      target: `${getSiteUrl()}/blog?q={search_term_string}`,
      'query-input': 'required name=search_term_string',
    },
  }
}
