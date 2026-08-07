import type { TourScene } from '../types'

const MIN_PANORAMA_WIDTH = 512
const MIN_PANORAMA_HEIGHT = 256
const EQUIRECT_MIN_RATIO = 1.75
const EQUIRECT_MAX_RATIO = 2.25

/** Reject thumbnail-sized or non-equirectangular metadata that breaks PSV zoom. */
export function isValidEquirectangularMeta(width: number, height: number): boolean {
  if (width < MIN_PANORAMA_WIDTH || height < MIN_PANORAMA_HEIGHT) return false
  const ratio = width / height
  return ratio >= EQUIRECT_MIN_RATIO && ratio <= EQUIRECT_MAX_RATIO
}

export function resolveSmartWalkDimensions(
  scene: TourScene | null | undefined,
  naturalSize?: { w: number; h: number } | null,
): { w: number; h: number } {
  if (naturalSize && naturalSize.w > 0 && naturalSize.h > 0) {
    return naturalSize
  }
  if (!scene) return { w: 1920, h: 1080 }

  const variantW = scene.image_variants?.width
  const variantH = scene.image_variants?.height
  if (variantW && variantH && variantW >= MIN_PANORAMA_WIDTH && variantH >= MIN_PANORAMA_HEIGHT) {
    return { w: variantW, h: variantH }
  }

  const pw = scene.panorama_width
  const ph = scene.panorama_height
  if (pw && ph && pw >= MIN_PANORAMA_WIDTH && ph >= MIN_PANORAMA_HEIGHT) {
    return { w: pw, h: ph }
  }

  return { w: 1920, h: 1080 }
}

export function buildEquirectangularPanoData(scene: TourScene): {
  fullWidth: number
  fullHeight: number
  croppedWidth: number
  croppedHeight: number
  croppedX: number
  croppedY: number
} | undefined {
  const w = scene.panorama_width
  const h = scene.panorama_height
  if (!w || !h || !isValidEquirectangularMeta(w, h)) return undefined
  return {
    fullWidth: w,
    fullHeight: h,
    croppedWidth: w,
    croppedHeight: h,
    croppedX: 0,
    croppedY: 0,
  }
}
