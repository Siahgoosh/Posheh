import { useCallback, useEffect, useRef, useState } from 'react'
import { Music2, Volume2 } from 'lucide-react'

interface Props {
  musicUrl?: string | null
  editorMode?: boolean
  volume?: number
}

/**
 * Persistent tour-level background music.
 * Uses a DOM <audio> element (better Android/WebView support than `new Audio()`).
 * Music continues across scene changes — only depends on tour music_url.
 */
export function TourBackgroundMusic({ musicUrl, editorMode = false, volume = 0.28 }: Props) {
  const audioRef = useRef<HTMLAudioElement>(null)
  const [blocked, setBlocked] = useState(false)
  const [playing, setPlaying] = useState(false)

  const tryPlay = useCallback(() => {
    const el = audioRef.current
    if (!el || !musicUrl || editorMode) return
    el.play()
      .then(() => {
        setBlocked(false)
        setPlaying(true)
      })
      .catch(() => setBlocked(true))
  }, [musicUrl, editorMode])

  useEffect(() => {
    const el = audioRef.current
    if (!el || editorMode || !musicUrl) {
      setPlaying(false)
      setBlocked(false)
      return
    }

    const resolved = musicUrl.startsWith('http') ? musicUrl : `${window.location.origin}${musicUrl.startsWith('/') ? '' : '/'}${musicUrl}`
    if (el.src !== resolved) {
      el.src = resolved
      el.load()
    }
    el.loop = true
    el.volume = volume
    el.setAttribute('playsinline', '')
    el.setAttribute('webkit-playsinline', 'true')

    tryPlay()

    const onPlay = () => setPlaying(true)
    const onPause = () => setPlaying(false)
    el.addEventListener('play', onPlay)
    el.addEventListener('pause', onPause)

    return () => {
      el.removeEventListener('play', onPlay)
      el.removeEventListener('pause', onPause)
    }
  }, [musicUrl, editorMode, volume, tryPlay])

  useEffect(() => {
    if (editorMode || !musicUrl) return
    const unlock = () => tryPlay()
    document.addEventListener('touchstart', unlock, { passive: true })
    document.addEventListener('click', unlock)
    return () => {
      document.removeEventListener('touchstart', unlock)
      document.removeEventListener('click', unlock)
    }
  }, [editorMode, musicUrl, tryPlay])

  if (editorMode || !musicUrl) return null

  return (
    <>
      <audio ref={audioRef} preload="auto" className="hidden" aria-hidden="true" />
      {blocked && !playing && (
        <button
          type="button"
          onClick={tryPlay}
          className="absolute bottom-20 left-4 z-40 flex items-center gap-2 px-3 py-2 rounded-full bg-black/70 border border-white/20 text-white text-xs backdrop-blur-md hover:bg-black/85 transition-colors max-w-[calc(100%-2rem)]"
          aria-label="پخش موزیک تور"
        >
          <Music2 className="h-4 w-4 shrink-0 text-primary" />
          <span>پخش موزیک تور</span>
          <Volume2 className="h-3.5 w-3.5 shrink-0 opacity-70" />
        </button>
      )}
    </>
  )
}
