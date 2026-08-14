/**
 * Preset tracks for virtual tours — bundled assets + Google Material ambience.
 * SoundHelix clips are trimmed royalty-free previews (SoundHelix license).
 */
export type TourMusicCategory = 'ambient' | 'calm' | 'modern' | 'nature' | 'voice'

export interface TourMusicTrack {
  id: string
  title: string
  category: TourMusicCategory
  url: string
  duration?: string
  description?: string
}

const BASE = '/audio/virtual-tour'

export const TOUR_MUSIC_LIBRARY: TourMusicTrack[] = [
  {
    id: 'coffee-shop',
    title: 'کافه و فضای داخلی',
    category: 'ambient',
    url: `${BASE}/coffee-shop.ogg`,
    duration: '1:10',
    description: 'صدای محیط کافه — مناسب تور داخلی (Google Sounds)',
  },
  {
    id: 'ambient-calm',
    title: 'آرامش محیطی',
    category: 'calm',
    url: `${BASE}/ambient-calm.mp3`,
    duration: '0:50',
    description: 'ملودی نرم برای فضاهای لوکس',
  },
  {
    id: 'soft-piano',
    title: 'پیانوی ملایم',
    category: 'calm',
    url: `${BASE}/soft-piano.mp3`,
    duration: '0:50',
    description: 'آرام و حرفه‌ای',
  },
  {
    id: 'dreamscape',
    title: 'رویای محیطی',
    category: 'ambient',
    url: `${BASE}/dreamscape.mp3`,
    duration: '0:50',
    description: 'اتمری و رویایی',
  },
  {
    id: 'modern-lounge',
    title: 'لانژ مدرن',
    category: 'modern',
    url: `${BASE}/modern-lounge.mp3`,
    duration: '0:50',
    description: 'مدرن — مناسب معماری و دکور',
  },
  {
    id: 'nature-peace',
    title: 'آرامش طبیعت',
    category: 'nature',
    url: `${BASE}/nature-peace.mp3`,
    duration: '0:50',
    description: 'حس فضای باز و طبیعت',
  },
]

/** Voice / narration — users typically paste their own hosted file URL */
export const TOUR_VOICE_PRESETS: TourMusicTrack[] = []

export const TOUR_MUSIC_CATEGORY_LABELS: Record<TourMusicCategory, string> = {
  ambient: 'محیطی',
  calm: 'آرام',
  modern: 'مدرن',
  nature: 'طبیعت',
  voice: 'ویس / صدا',
}

export function findTourMusicTrack(url: string | null | undefined): TourMusicTrack | undefined {
  if (!url) return undefined
  return [...TOUR_MUSIC_LIBRARY, ...TOUR_VOICE_PRESETS].find((t) => t.url === url)
}
