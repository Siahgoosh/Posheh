export type SceneStatus = 'draft' | 'published'

export interface TourHotspot {
  id: number
  type: 'scene' | 'info' | 'link' | 'video'
  target_scene_id?: number | null
  yaw: number
  pitch: number
  title?: string | null
  content?: string | null
  link_url?: string | null
  icon?: string
}

export interface TourScene {
  id: number
  name: string
  status: SceneStatus
  is_default: boolean
  is_visible: boolean
  panorama_url: string
  thumbnail_url?: string | null
  default_yaw?: number
  default_pitch?: number
  sort_order?: number
  panorama_width?: number | null
  panorama_height?: number | null
  file_size?: number | null
  floor_plan_x?: number | null
  floor_plan_y?: number | null
  hotspots: TourHotspot[]
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
  phone?: string
  whatsapp?: string
  map_lat?: number
  map_lng?: number
  music_url?: string
}

export interface TourData {
  id?: number
  title: string
  slug?: string
  description?: string
  status?: SceneStatus
  view_count?: number
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
}

export interface ViewerPosition {
  yaw: number
  pitch: number
  zoom: number
  fov: number
}

export type SceneFilter = 'all' | 'draft' | 'published' | 'visible' | 'hidden'
export type SceneSort = 'order' | 'name' | 'status'

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
