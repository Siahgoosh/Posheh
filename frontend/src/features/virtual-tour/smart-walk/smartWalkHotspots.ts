import type { TourHotspot } from '../types'
import { DEFAULT_HOTSPOT_STYLE } from '../hotspots/constants'

export function createSmartWalkSceneLinkHotspot(x: number, y: number): TourHotspot {
  return {
    id: `temp-${Date.now()}`,
    type: 'scene',
    yaw: 0,
    pitch: 0,
    position_x: x,
    position_y: y,
    title: '',
    label: '',
    tooltip: 'رفتن به صحنه بعدی',
    icon: 'arrow',
    style: {
      ...DEFAULT_HOTSPOT_STYLE,
      color: '#2dd4bf',
      size: 36,
      pulse: true,
      glow: true,
      hoverAnimation: 'scale',
    },
    action: {
      type: 'scene',
      transition_effect: 'fade',
      transition_duration: 600,
    },
    popup: {},
    sort_order: 0,
  }
}

export function createSmartWalkHotspot(
  x: number,
  y: number,
  type: TourHotspot['type'] = 'info',
): TourHotspot {
  return {
    id: `temp-${Date.now()}`,
    type,
    yaw: 0,
    pitch: 0,
    position_x: x,
    position_y: y,
    title: '',
    label: '',
    tooltip: '',
    icon: 'pin',
    style: {},
    action: {},
    popup: {},
    sort_order: 0,
  }
}

/** Convert container click coordinates to percentage position on the image. */
export function clickToImagePercent(
  clientX: number,
  clientY: number,
  container: HTMLElement,
  transform: { x: number; y: number; scale: number },
  imageWidth: number,
  imageHeight: number,
): { x: number; y: number } {
  const rect = container.getBoundingClientRect()
  const cx = clientX - rect.left
  const cy = clientY - rect.top

  const containerW = rect.width
  const containerH = rect.height

  const imgDisplayW = imageWidth * transform.scale
  const imgDisplayH = imageHeight * transform.scale

  const imgLeft = (containerW - imgDisplayW) / 2 + transform.x
  const imgTop = (containerH - imgDisplayH) / 2 + transform.y

  const relX = (cx - imgLeft) / imgDisplayW
  const relY = (cy - imgTop) / imgDisplayH

  return {
    x: Math.max(0, Math.min(100, relX * 100)),
    y: Math.max(0, Math.min(100, relY * 100)),
  }
}
