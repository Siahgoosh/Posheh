/** Camera transform for Smart Walk viewport (GPU-friendly). */
export interface CameraState {
  x: number
  y: number
  scale: number
}

export const MAX_ZOOM_FACTOR = 8
export const DEFAULT_FRICTION = 0.92
export const MOMENTUM_THRESHOLD = 0.5

/** Scale so the full image fits inside the viewport. */
export function computeFitScale(
  imageWidth: number,
  imageHeight: number,
  viewWidth: number,
  viewHeight: number,
): number {
  if (imageWidth <= 0 || imageHeight <= 0 || viewWidth <= 0 || viewHeight <= 0) return 1
  return Math.min(viewWidth / imageWidth, viewHeight / imageHeight)
}

export function clampScale(scale: number, fitScale = 1): number {
  const min = fitScale
  const max = fitScale * MAX_ZOOM_FACTOR
  return Math.max(min, Math.min(max, scale))
}

export function zoomPercent(scale: number, fitScale: number): number {
  if (fitScale <= 0) return 100
  return Math.round((scale / fitScale) * 100)
}

/** Pan bounds with soft overflow for fluid infinite-feel drag at high zoom. */
export function clampPan(
  camera: CameraState,
  imageWidth: number,
  imageHeight: number,
  viewWidth: number,
  viewHeight: number,
  softFactor = 1.08,
): CameraState {
  const scaledW = imageWidth * camera.scale
  const scaledH = imageHeight * camera.scale
  const maxX = Math.max(0, (scaledW - viewWidth) / 2)
  const maxY = Math.max(0, (scaledH - viewHeight) / 2)

  return {
    scale: camera.scale,
    x: Math.max(-maxX * softFactor, Math.min(maxX * softFactor, camera.x)),
    y: Math.max(-maxY * softFactor, Math.min(maxY * softFactor, camera.y)),
  }
}

/** Zoom toward a focal point in viewport coordinates. */
export function zoomAtPoint(
  camera: CameraState,
  deltaScale: number,
  focalX: number,
  focalY: number,
  viewWidth: number,
  viewHeight: number,
  fitScale = 1,
): CameraState {
  const newScale = clampScale(camera.scale + deltaScale, fitScale)
  if (newScale === camera.scale) return camera

  const ratio = newScale / camera.scale
  const cx = viewWidth / 2
  const cy = viewHeight / 2
  const dx = focalX - cx
  const dy = focalY - cy

  return {
    scale: newScale,
    x: camera.x - dx * (ratio - 1),
    y: camera.y - dy * (ratio - 1),
  }
}

/** Convert hotspot percent on image to pan offset target (walk toward hotspot). */
export function hotspotToPanTarget(
  px: number,
  py: number,
  imageWidth: number,
  imageHeight: number,
  _viewWidth: number,
  _viewHeight: number,
  scale: number,
): { x: number; y: number } {
  const offsetX = ((px - 50) / 100) * imageWidth * scale
  const offsetY = ((py - 50) / 100) * imageHeight * scale
  return { x: -offsetX * 0.65, y: -offsetY * 0.65 }
}

export function cameraTransformCss(camera: CameraState): string {
  return `translate3d(${camera.x}px, ${camera.y}px, 0) scale(${camera.scale})`
}

export function lerp(a: number, b: number, t: number): number {
  return a + (b - a) * t
}

export function easeOutCubic(t: number): number {
  return 1 - (1 - t) ** 3
}

export function easeInOutCubic(t: number): number {
  return t < 0.5 ? 4 * t * t * t : 1 - (-2 * t + 2) ** 3 / 2
}
