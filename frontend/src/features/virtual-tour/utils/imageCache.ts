import { resolvePanoramaUrl } from './panoramaUrl'
import type { SceneImageVariants } from '../types'

const cache = new Map<string, Promise<void>>()

export function getCachedImage(url: string): Promise<void> {
  const src = resolvePanoramaUrl(url)
  if (!src) return Promise.reject(new Error('empty url'))

  const existing = cache.get(src)
  if (existing) return existing

  const promise = new Promise<void>((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve()
    img.onerror = () => reject(new Error(`failed: ${src}`))
    img.src = src
  })

  cache.set(src, promise)
  return promise
}

export function preloadSceneImages(urls: string[]): void {
  urls.forEach((url) => {
    getCachedImage(url).catch(() => {})
  })
}

/** Pick the best responsive variant for the current viewport width. */
export function pickSceneImageUrl(
  panoramaUrl: string,
  variants?: SceneImageVariants | null,
  viewportWidth = window.innerWidth,
): string {
  if (!variants) return resolvePanoramaUrl(panoramaUrl)

  if (viewportWidth <= 640 && variants.medium) {
    return resolvePanoramaUrl(variants.medium)
  }
  if (viewportWidth <= 1280 && variants.large) {
    return resolvePanoramaUrl(variants.large)
  }
  if (viewportWidth <= 1920 && variants.ultra) {
    return resolvePanoramaUrl(variants.ultra)
  }
  if (variants.ultra) return resolvePanoramaUrl(variants.ultra)
  if (variants.large) return resolvePanoramaUrl(variants.large)
  if (variants.medium) return resolvePanoramaUrl(variants.medium)
  if (variants.original) return resolvePanoramaUrl(variants.original)

  return resolvePanoramaUrl(panoramaUrl)
}

/** Pick highest quality variant for deep zoom (800%). */
export function pickSceneImageUrlForZoom(
  panoramaUrl: string,
  variants?: SceneImageVariants | null,
  scale = 1,
  fitScale = 1,
): string {
  const relative = fitScale > 0 ? scale / fitScale : scale
  if (!variants) return resolvePanoramaUrl(panoramaUrl)
  if (relative >= 4 && variants.original) return resolvePanoramaUrl(variants.original)
  if (relative >= 2.5 && variants.ultra) return resolvePanoramaUrl(variants.ultra)
  if (relative >= 1.5 && variants.large) return resolvePanoramaUrl(variants.large)
  return pickSceneImageUrl(panoramaUrl, variants)
}

export function clearImageCache(): void {
  cache.clear()
}
