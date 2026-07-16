import { Link } from 'react-router-dom'
import { ChevronLeft } from 'lucide-react'

export interface BreadcrumbItem {
  label: string
  href?: string
}

export function SeoBreadcrumb({ items }: { items: BreadcrumbItem[] }) {
  return (
    <nav aria-label="مسیر" className="flex flex-wrap items-center gap-1 text-sm text-muted mb-6">
      {items.map((item, i) => (
        <span key={i} className="flex items-center gap-1">
          {i > 0 && <ChevronLeft className="h-3 w-3 opacity-50" />}
          {item.href ? (
            <Link to={item.href} className="hover:text-primary transition-colors">{item.label}</Link>
          ) : (
            <span className="text-foreground">{item.label}</span>
          )}
        </span>
      ))}
    </nav>
  )
}

export function getBreadcrumbJsonLd(items: BreadcrumbItem[], siteUrl: string) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name: item.label,
      item: item.href ? `${siteUrl}${item.href}` : undefined,
    })),
  }
}

export function getFaqJsonLd(faq: { question: string; answer: string }[]) {
  if (!faq.length) return null
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: faq.map((f) => ({
      '@type': 'Question',
      name: f.question,
      acceptedAnswer: { '@type': 'Answer', text: f.answer },
    })),
  }
}
