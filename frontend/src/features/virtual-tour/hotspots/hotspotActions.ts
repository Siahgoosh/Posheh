import type { TourData, TourHotspot } from '../types'
import { DEFAULT_HOTSPOT_STYLE } from './constants'

export interface HotspotActionContext {
  tour: TourData
  onGoToScene: (sceneId: number) => void
  onShowPopup: (hotspot: TourHotspot) => void
  onShowGallery: (images: string[]) => void
  onShowLeadForm?: () => void
}

export function executeHotspotAction(hotspot: TourHotspot, ctx: HotspotActionContext): void {
  const action = hotspot.action
  const popup = hotspot.popup

  switch (hotspot.type) {
    case 'scene':
      if (hotspot.target_scene_id || action?.target_scene_id) {
        ctx.onGoToScene(hotspot.target_scene_id || action!.target_scene_id!)
      }
      break

    case 'info':
    case 'floor_plan':
    case 'custom':
      ctx.onShowPopup(hotspot)
      break

    case 'gallery':
    case 'image':
      if (popup?.gallery?.length || popup?.images?.length) {
        ctx.onShowGallery(popup.gallery || popup.images || [])
      } else if (action?.media_urls?.length) {
        ctx.onShowGallery(action.media_urls)
      } else {
        ctx.onShowPopup(hotspot)
      }
      break

    case 'video':
      ctx.onShowPopup(hotspot)
      break

    case 'audio':
      if (popup?.audio_url || action?.url) {
        const audio = new Audio(popup?.audio_url || action?.url)
        audio.play().catch(() => {})
      }
      break

    case 'pdf':
      if (action?.download_url || hotspot.link_url) {
        window.open(action?.download_url || hotspot.link_url!, '_blank')
      }
      break

    case 'website':
    case 'link':
    case 'telegram':
      if (hotspot.link_url || action?.url) {
        window.open(hotspot.link_url || action?.url, '_blank')
      }
      break

    case 'whatsapp': {
      const phone = action?.phone || hotspot.link_url || ctx.tour.settings?.whatsapp
      if (phone) window.open(`https://wa.me/98${phone.replace(/^0/, '').replace(/\D/g, '')}`, '_blank')
      break
    }

    case 'phone': {
      const phone = action?.phone || hotspot.link_url || ctx.tour.settings?.phone
      if (phone) window.location.href = `tel:${phone}`
      break
    }

    case 'email': {
      const email = action?.email || hotspot.link_url
      if (email) window.location.href = `mailto:${email}`
      break
    }

    case 'maps': {
      const lat = ctx.tour.settings?.map_lat
      const lng = ctx.tour.settings?.map_lng
      if (lat && lng) window.open(`https://maps.google.com/?q=${lat},${lng}`, '_blank')
      else if (action?.url) window.open(action.url, '_blank')
      break
    }

    default:
      if (action?.type === 'scene' && action.target_scene_id) {
        ctx.onGoToScene(action.target_scene_id)
      } else if (popup) {
        ctx.onShowPopup(hotspot)
      } else if (hotspot.link_url) {
        window.open(hotspot.link_url, '_blank')
      }
  }
}

export function createSceneLinkHotspot(yaw: number, pitch: number): TourHotspot {
  return {
    id: `temp-${Date.now()}`,
    type: 'scene',
    yaw,
    pitch,
    title: '',
    label: '',
    tooltip: 'رفتن به صحنه بعدی',
    icon: 'arrow',
    style: {
      ...DEFAULT_HOTSPOT_STYLE,
      color: '#2dd4bf',
      size: 48,
      pulse: true,
      glow: true,
      hoverAnimation: 'scale',
    },
    action: {
      type: 'scene',
      transition_effect: 'fade',
      transition_duration: 800,
    },
    popup: {},
    sort_order: 0,
  }
}

export function createDefaultHotspot(yaw: number, pitch: number, type: TourHotspot['type'] = 'info'): TourHotspot {
  return {
    id: `temp-${Date.now()}`,
    type,
    yaw,
    pitch,
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
