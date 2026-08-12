import { useRef, useState } from 'react'
import { Music2, Play, Square, Mic } from 'lucide-react'
import { Input } from '@/components/ui/input'
import {
  TOUR_MUSIC_LIBRARY,
  TOUR_VOICE_PRESETS,
  TOUR_MUSIC_CATEGORY_LABELS,
  findTourMusicTrack,
  type TourMusicTrack,
} from './tourMusicLibrary'

interface Props {
  label: string
  value: string
  onChange: (url: string) => void
  allowVoice?: boolean
  placeholder?: string
}

export function TourMusicPicker({
  label,
  value,
  onChange,
  allowVoice = false,
  placeholder = 'یا URL سفارشی موسیقی / ویس',
}: Props) {
  const [previewId, setPreviewId] = useState<string | null>(null)
  const audioRef = useRef<HTMLAudioElement | null>(null)

  const tracks = allowVoice ? [...TOUR_MUSIC_LIBRARY, ...TOUR_VOICE_PRESETS] : TOUR_MUSIC_LIBRARY
  const selected = findTourMusicTrack(value)

  const stopPreview = () => {
    if (audioRef.current) {
      audioRef.current.pause()
      audioRef.current = null
    }
    setPreviewId(null)
  }

  const togglePreview = (track: TourMusicTrack) => {
    if (previewId === track.id) {
      stopPreview()
      return
    }
    stopPreview()
    const audio = new Audio(track.url)
    audio.volume = 0.4
    audioRef.current = audio
    setPreviewId(track.id)
    audio.play().catch(() => setPreviewId(null))
    audio.onended = () => {
      setPreviewId(null)
      audioRef.current = null
    }
  }

  return (
    <div className="space-y-2">
      <div className="flex items-center gap-2">
        {allowVoice ? <Mic className="h-3.5 w-3.5 text-primary" /> : <Music2 className="h-3.5 w-3.5 text-primary" />}
        <label className="text-xs font-medium">{label}</label>
      </div>

      {selected && (
        <p className="text-[10px] text-primary px-1">
          انتخاب فعلی: {selected.title}
        </p>
      )}

      <div className="grid grid-cols-1 gap-1.5 max-h-48 overflow-y-auto pr-1">
        {tracks.map((track) => {
          const active = value === track.url
          const playing = previewId === track.id
          return (
            <div
              key={track.id}
              className={`flex items-center gap-2 rounded-lg border px-2 py-1.5 transition-colors ${
                active ? 'border-primary/50 bg-primary/10' : 'border-card-border/50 hover:border-white/15'
              }`}
            >
              <button
                type="button"
                className="shrink-0 p-1 rounded-md hover:bg-white/10"
                onClick={() => togglePreview(track)}
                title="پیش‌نمایش"
              >
                {playing ? <Square className="h-3 w-3" /> : <Play className="h-3 w-3" />}
              </button>
              <button
                type="button"
                className="flex-1 min-w-0 text-left"
                onClick={() => {
                  stopPreview()
                  onChange(track.url)
                }}
              >
                <span className="text-xs font-medium block truncate">{track.title}</span>
                <span className="text-[9px] text-muted">
                  {TOUR_MUSIC_CATEGORY_LABELS[track.category]}
                  {track.duration ? ` · ${track.duration}` : ''}
                </span>
              </button>
              {active && <span className="text-[9px] text-primary shrink-0">✓</span>}
            </div>
          )
        })}
      </div>

      <Input
        placeholder={placeholder}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="text-xs"
      />

      {value && (
        <button
          type="button"
          className="text-[10px] text-muted hover:text-danger"
          onClick={() => {
            stopPreview()
            onChange('')
          }}
        >
          حذف صدا / موزیک
        </button>
      )}
    </div>
  )
}
