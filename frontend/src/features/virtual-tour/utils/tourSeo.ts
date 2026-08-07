import { applySeo, getSiteUrl } from '@/lib/seo'
import type { TourData } from '../types'

interface SeoPayload {
  title?: string
  description?: string
  canonical?: string
  og_image?: string
  noindex?: boolean
  json_ld?: Record<string, unknown> | Record<string, unknown>[]
}

export function applyTourSeo(tour: TourData, seo?: SeoPayload) {
  const slug = tour.slug || ''
  const path = seo?.canonical?.replace(getSiteUrl(), '') || `/tour/${slug}`
  const title = seo?.title || tour.title
  const description = seo?.description || tour.description || `تور مجازی — ${tour.title}`
  const image = seo?.og_image || tour.scenes?.[0]?.thumbnail_url || undefined

  applySeo({
    title,
    description,
    path,
    image,
    type: 'website',
    noindex: seo?.noindex ?? tour.visibility === 'private',
    jsonLd: seo?.json_ld,
  })
}

export function tourScenePath(tourSlug: string, sceneId: number): string {
  return `/tour/${tourSlug}/scene/${sceneId}`
}
