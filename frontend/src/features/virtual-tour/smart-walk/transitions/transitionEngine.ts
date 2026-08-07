import type { CameraState } from '../camera/cameraMath'
import { clampPan, clampScale, computeFitScale, easeInOutCubic, easeOutCubic, lerp } from '../camera/cameraMath'

export type TransitionEffect =
  | 'fade'
  | 'crossfade'
  | 'zoom'
  | 'push'
  | 'pull'
  | 'motion_blur'
  | 'blur'
  | 'slide'
  | 'scale'
  | 'opacity_blend'
  | 'none'

export interface TransitionConfig {
  effect: TransitionEffect
  duration: number
}

export interface TransitionFrame {
  camera: CameraState
  overlayOpacity: number
  blurPx: number
  outgoingOpacity: number
  incomingOpacity: number
}

const DEFAULT_DURATION = 800

function frameAt(
  from: CameraState,
  to: CameraState,
  overlayFrom: number,
  overlayTo: number,
  blurFrom: number,
  blurTo: number,
  outOp: number,
  inOp: number,
  t: number,
): TransitionFrame {
  const e = easeInOutCubic(t)
  return {
    camera: {
      x: lerp(from.x, to.x, e),
      y: lerp(from.y, to.y, e),
      scale: lerp(from.scale, to.scale, e),
    },
    overlayOpacity: lerp(overlayFrom, overlayTo, e),
    blurPx: lerp(blurFrom, blurTo, e),
    outgoingOpacity: lerp(outOp, outOp * (1 - e), t),
    incomingOpacity: lerp(inOp * (1 - e), inOp, t),
  }
}

/** Animate camera toward hotspot then fade — walking illusion before scene swap. */
export function animateWalkToHotspot(
  from: CameraState,
  panTarget: { x: number; y: number },
  imageWidth: number,
  imageHeight: number,
  viewWidth: number,
  viewHeight: number,
  onFrame: (frame: TransitionFrame) => void,
  config?: Partial<TransitionConfig>,
  fitScale = 1,
): Promise<void> {
  const duration = config?.duration ?? DEFAULT_DURATION
  const walkZoom = clampScale(from.scale * 1.35, fitScale)

  const approach: CameraState = clampPan(
    { x: panTarget.x, y: panTarget.y, scale: walkZoom },
    imageWidth,
    imageHeight,
    viewWidth,
    viewHeight,
  )

  const phase1 = duration * 0.55
  const phase2 = duration * 0.45

  return new Promise((resolve) => {
    const start = performance.now()

    const tick = () => {
      const elapsed = performance.now() - start
      if (elapsed < phase1) {
        const t = easeOutCubic(elapsed / phase1)
        onFrame(frameAt(from, approach, 0, 0, 0, 2, 1, 0, t))
        requestAnimationFrame(tick)
        return
      }

      if (elapsed < duration) {
        const t = (elapsed - phase1) / phase2
        const overlay = easeInOutCubic(t)
        onFrame({
          camera: approach,
          overlayOpacity: overlay * 0.85,
          blurPx: 4 + overlay * 6,
          outgoingOpacity: 1 - overlay,
          incomingOpacity: 0,
        })
        requestAnimationFrame(tick)
        return
      }

      onFrame({
        camera: approach,
        overlayOpacity: 0.85,
        blurPx: 8,
        outgoingOpacity: 0,
        incomingOpacity: 0,
      })
      resolve()
    }

    requestAnimationFrame(tick)
  })
}

/** Land in new scene after swap — gentle settle. */
export function animateSceneLanding(
  from: CameraState,
  imageWidth: number,
  imageHeight: number,
  viewWidth: number,
  viewHeight: number,
  onFrame: (frame: TransitionFrame) => void,
  config?: Partial<TransitionConfig>,
  fitScale?: number,
): Promise<void> {
  const duration = config?.duration ?? 600
  const landScale = fitScale ?? computeFitScale(imageWidth, imageHeight, viewWidth, viewHeight)
  const land: CameraState = clampPan(
    { x: 0, y: 0, scale: landScale },
    imageWidth,
    imageHeight,
    viewWidth,
    viewHeight,
  )

  return new Promise((resolve) => {
    const start = performance.now()
    const tick = () => {
      const t = Math.min(1, (performance.now() - start) / duration)
      const e = easeOutCubic(t)
      onFrame({
        camera: {
          x: lerp(from.x, land.x, e),
          y: lerp(from.y, land.y, e),
          scale: lerp(from.scale, land.scale, e),
        },
        overlayOpacity: lerp(0.85, 0, e),
        blurPx: lerp(8, 0, e),
        outgoingOpacity: 0,
        incomingOpacity: lerp(0, 1, e),
      })
      if (t < 1) requestAnimationFrame(tick)
      else resolve()
    }
    requestAnimationFrame(tick)
  })
}

/** Non-walk scene transition effects. */
export function animateSceneTransition(
  effect: TransitionEffect,
  duration: number,
  onFrame: (frame: Omit<TransitionFrame, 'camera'> & { camera?: CameraState }) => void,
  fromCamera: CameraState,
): Promise<void> {
  if (effect === 'none') {
    onFrame({ overlayOpacity: 0, blurPx: 0, outgoingOpacity: 1, incomingOpacity: 1 })
    return Promise.resolve()
  }

  return new Promise((resolve) => {
    const start = performance.now()
    const tick = () => {
      const t = Math.min(1, (performance.now() - start) / duration)
      const e = easeInOutCubic(t)

      let overlay = 0
      let blur = 0
      let outOp = 1
      let inOp = 0
      let scale = fromCamera.scale

      switch (effect) {
        case 'fade':
        case 'opacity_blend':
          overlay = e * 0.9
          outOp = 1 - e
          inOp = e
          break
        case 'crossfade':
          outOp = 1 - e
          inOp = e
          break
        case 'zoom':
          scale = lerp(fromCamera.scale, fromCamera.scale * 1.2, e)
          outOp = 1 - e
          inOp = e
          break
        case 'push':
          overlay = e * 0.5
          outOp = 1 - e
          inOp = e
          break
        case 'pull':
          scale = lerp(fromCamera.scale, fromCamera.scale * 0.85, e)
          outOp = 1 - e
          inOp = e
          break
        case 'blur':
        case 'motion_blur':
          blur = e * 12
          outOp = 1 - e
          inOp = e
          break
        case 'slide':
        case 'scale':
          outOp = 1 - e
          inOp = e
          scale = effect === 'scale' ? lerp(1, 1.08, e) : fromCamera.scale
          break
        default:
          outOp = 1 - e
          inOp = e
      }

      onFrame({
        camera: { ...fromCamera, scale },
        overlayOpacity: overlay,
        blurPx: blur,
        outgoingOpacity: outOp,
        incomingOpacity: inOp,
      })

      if (t < 1) requestAnimationFrame(tick)
      else resolve()
    }
    requestAnimationFrame(tick)
  })
}
