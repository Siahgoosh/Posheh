import type { HotspotType } from '../types'

export interface HotspotTypeDef {
  type: HotspotType
  label: string
  icon: string
  emoji: string
  defaultAction: string
}

export const HOTSPOT_TYPES: HotspotTypeDef[] = [
  { type: 'scene', label: 'لینک صحنه', icon: 'arrow', emoji: '➡️', defaultAction: 'scene' },
  { type: 'info', label: 'اطلاعات', icon: 'info', emoji: 'ℹ️', defaultAction: 'popup' },
  { type: 'gallery', label: 'گالری', icon: 'images', emoji: '🖼️', defaultAction: 'gallery' },
  { type: 'image', label: 'تصویر', icon: 'image', emoji: '📷', defaultAction: 'gallery' },
  { type: 'video', label: 'ویدئو', icon: 'video', emoji: '🎬', defaultAction: 'video' },
  { type: 'audio', label: 'صدا', icon: 'audio', emoji: '🔊', defaultAction: 'audio' },
  { type: 'pdf', label: 'PDF', icon: 'file', emoji: '📄', defaultAction: 'download' },
  { type: 'website', label: 'وبسایت', icon: 'globe', emoji: '🌐', defaultAction: 'link' },
  { type: 'whatsapp', label: 'واتساپ', icon: 'message', emoji: '💬', defaultAction: 'call' },
  { type: 'telegram', label: 'تلگرام', icon: 'send', emoji: '✈️', defaultAction: 'link' },
  { type: 'phone', label: 'تماس', icon: 'phone', emoji: '📞', defaultAction: 'call' },
  { type: 'email', label: 'ایمیل', icon: 'mail', emoji: '📧', defaultAction: 'email' },
  { type: 'maps', label: 'گوگل مپ', icon: 'map', emoji: '📍', defaultAction: 'map' },
  { type: 'floor_plan', label: 'پلان طبقه', icon: 'layout', emoji: '🗺️', defaultAction: 'popup' },
  { type: 'custom', label: 'دکمه سفارشی', icon: 'star', emoji: '⭐', defaultAction: 'popup' },
]

export const DEFAULT_HOTSPOT_STYLE = {
  size: 36,
  color: '#2dd4bf',
  glow: true,
  pulse: true,
  hoverAnimation: 'scale' as const,
  clickAnimation: 'ripple' as const,
  opacity: 1,
  shadow: true,
}

export function getHotspotTypeDef(type: HotspotType): HotspotTypeDef {
  return HOTSPOT_TYPES.find((t) => t.type === type) ?? HOTSPOT_TYPES[1]
}
