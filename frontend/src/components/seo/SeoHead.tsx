import { useEffect } from 'react'
import { applySeo } from '@/lib/seo'

interface SeoHeadProps {
  title?: string
  description?: string
  keywords?: string
  path?: string
  image?: string
  type?: 'website' | 'article'
  jsonLd?: Record<string, unknown> | Record<string, unknown>[]
  noindex?: boolean
}

export function SeoHead(props: SeoHeadProps) {
  useEffect(() => {
    applySeo(props)
  }, [props.title, props.description, props.keywords, props.path, props.image, props.type, props.noindex])

  return null
}
