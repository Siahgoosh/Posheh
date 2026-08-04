export type TourType = 'panorama_360' | 'smart_walk'

export type SceneType = 'equirectangular' | 'flat_image'

export type SceneStatus = 'draft' | 'published' | 'archived'

export type HotspotType =
  | 'scene'
  | 'info'
  | 'gallery'
  | 'image'
  | 'video'
  | 'audio'
  | 'pdf'
  | 'website'
  | 'whatsapp'
  | 'telegram'
  | 'phone'
  | 'email'
  | 'maps'
  | 'floor_plan'
  | 'custom'
  | 'link'

export interface HotspotStyle {
  icon?: string
  label?: string
  tooltip?: string
  size?: number
  color?: string
  glow?: boolean
  pulse?: boolean
  hoverAnimation?: 'scale' | 'bounce' | 'none'
  clickAnimation?: 'ripple' | 'none'
  opacity?: number
  border?: string
  shadow?: boolean
}

export interface HotspotAction {
  type?: 'scene' | 'popup' | 'property' | 'gallery' | 'video' | 'audio' | 'link' | 'call' | 'download' | 'map' | 'email'
  target_scene_id?: number
  url?: string
  phone?: string
  email?: string
  media_urls?: string[]
  download_url?: string
  /** Entrance view after scene transition */
  entrance_yaw?: number
  entrance_pitch?: number
  transition_effect?: 'fade' | 'crossfade' | 'none'
  transition_duration?: number
  hidden?: boolean
}

export interface HotspotPopup {
  title?: string
  description?: string
  images?: string[]
  gallery?: string[]
  video_url?: string
  audio_url?: string
  property?: {
    price?: number
    area?: number
    features?: string[]
    phone?: string
  }
  show_lead_form?: boolean
}

export interface TourHotspot {
  id: number | string
  type: HotspotType
  target_scene_id?: number | null
  yaw: number
  pitch: number
  position_x?: number | null
  position_y?: number | null
  position_z?: number | null
  title?: string | null
  label?: string | null
  tooltip?: string | null
  content?: string | null
  link_url?: string | null
  icon?: string
  style?: HotspotStyle
  action?: HotspotAction
  popup?: HotspotPopup
  sort_order?: number
  target_scene?: { id: number; name: string } | null
}

export interface SceneSettings {
  initial_direction?: number
  [key: string]: unknown
}

export interface SceneImageVariants {
  original?: string
  thumbnail?: string
  medium?: string
  large?: string
  ultra?: string
  width?: number
  height?: number
  format?: string
}

export interface SceneMetadata {
  gps?: { lat: number; lng: number }
  audio?: string
  video?: string
  preview?: string
  [key: string]: unknown
}

export interface TourScene {
  id: number
  name: string
  scene_type?: SceneType
  status: SceneStatus
  is_default: boolean
  is_visible: boolean
  panorama_url: string
  thumbnail_url?: string | null
  image_variants?: SceneImageVariants | null
  metadata?: SceneMetadata
  default_yaw?: number
  default_pitch?: number
  default_fov?: number | null
  background_music?: string | null
  ambient_sound?: string | null
  transition_effect?: 'fade' | 'crossfade' | 'none'
  scene_settings?: SceneSettings
  sort_order?: number
  panorama_width?: number | null
  panorama_height?: number | null
  file_size?: number | null
  floor_plan_x?: number | null
  floor_plan_y?: number | null
  hotspots: TourHotspot[]
}

export interface GuidedTourStep {
  scene_id: number
  yaw?: number
  pitch?: number
  narration?: string
  delay?: number
}

export interface TourSettings {
  brand_color?: string
  enable_gyroscope?: boolean
  enable_vr?: boolean
  show_floor_plan?: boolean
  show_contact_form?: boolean
  show_gallery?: boolean
  auto_rotate?: boolean
  auto_rotate_speed?: number
  auto_tour?: boolean
  auto_tour_interval?: number
  guided_tour?: boolean
  guided_tour_steps?: GuidedTourStep[]
  bookmarks?: boolean
  favorites?: boolean
  history?: boolean
  mini_map?: boolean
  floor_selector?: boolean
  embed_enabled?: boolean
  share_enabled?: boolean
  qr_enabled?: boolean
  phone?: string
  whatsapp?: string
  telegram?: string
  map_lat?: number
  map_lng?: number
  music_url?: string
}

export interface TourData {
  id?: number
  title: string
  slug?: string
  description?: string
  tour_type?: TourType
  status?: SceneStatus
  visibility?: 'public' | 'private'
  view_count?: number
  version?: number
  expires_at?: string | null
  archived_at?: string | null
  has_password?: boolean
  share_token?: string
  settings?: TourSettings
  scenes: TourScene[]
  gallery?: { id: number; type: string; url: string; title?: string }[]
  property?: {
    code: string
    type: string
    price?: number
    area?: number
    city?: string
    district?: string
  }
  office?: { name: string; phone?: string }
  public_url?: string
  private_url?: string
  embed_url?: string
}

export interface ViewerPosition {
  yaw: number
  pitch: number
  zoom: number
  fov: number
}

export type SceneFilter = 'all' | 'draft' | 'published' | 'visible' | 'hidden'
export type SceneSort = 'order' | 'name' | 'status'
export type EditorTab = 'scenes' | 'hotspots' | 'scene-settings' | 'tour-settings' | 'sharing'

export interface UploadTask {
  id: string
  file: File
  name: string
  progress: number
  status: 'pending' | 'uploading' | 'success' | 'error' | 'cancelled'
  error?: string
  previewUrl?: string
  abortController?: AbortController
}

export interface BookmarkItem {
  sceneId: number
  yaw: number
  pitch: number
  label: string
  createdAt: number
}
