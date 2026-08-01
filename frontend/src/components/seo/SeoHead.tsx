import { useEffect } from 'react'
import { applySeo } from '@/lib/seo'

interface SeoHeadProps {
  title?: string
  description?: string
  keywords?: string
  path?: string
  image?: string
  type?: 'website' | 'article'
  publishedTime?: string
  modifiedTime?: string
  jsonLd?: Record<string, unknown> | Record<string, unknown>[]
  noindex?: boolean
}

export function SeoHead(props: SeoHeadProps) {
  useEffect(() => {
    applySeo(props)
  }, [props.title, props.description, props.keywords, props.path, props.image, props.type, props.publishedTime, props.modifiedTime, props.noindex])

  return null
}
