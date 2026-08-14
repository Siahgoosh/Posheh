import { useEffect } from 'react'
import { applySeo } from '@/lib/seo'

interface SeoHeadProps {
  title?: string
  description?: string
  keywords?: string
  path?: string
  canonicalUrl?: string
  image?: string
  type?: 'website' | 'article'
  publishedTime?: string
  modifiedTime?: string
  jsonLd?: Record<string, unknown> | Record<string, unknown>[]
  noindex?: boolean
  robots?: string
}

export function SeoHead(props: SeoHeadProps) {
  useEffect(() => {
    applySeo(props)
  }, [
    props.title,
    props.description,
    props.keywords,
    props.path,
    props.canonicalUrl,
    props.image,
    props.type,
    props.publishedTime,
    props.modifiedTime,
    props.noindex,
    props.robots,
    props.jsonLd,
  ])

  return null
}
