import { useState, useEffect } from 'react'
import {
  Share2, QrCode, Code, Bookmark, History, Star, Layers,
  Play, Pause, Copy, Check,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import type { TourData, BookmarkItem } from '../types'

interface Props {
  tour: TourData
  activeSceneId: number | null
  position: { yaw: number; pitch: number }
  onGoToScene: (id: number) => void
  onStartAutoTour?: () => void
  onStopAutoTour?: () => void
  isAutoTouring?: boolean
  publicUrl?: string
}

export function TourFeaturesOverlay({
  tour,
  activeSceneId,
  position,
  onGoToScene,
  onStartAutoTour,
  onStopAutoTour,
  isAutoTouring,
  publicUrl,
}: Props) {
  const [showShare, setShowShare] = useState(false)
  const [copied, setCopied] = useState(false)
  const [bookmarks, setBookmarks] = useState<BookmarkItem[]>([])
  const [history, setHistory] = useState<number[]>([])
  const [favorites, setFavorites] = useState<number[]>([])
  const settings = tour.settings || {}
  const url = publicUrl || `${window.location.origin}/tour/${tour.slug}`

  useEffect(() => {
    if (activeSceneId) {
      setHistory((h) => [...new Set([...h, activeSceneId])].slice(-10))
    }
  }, [activeSceneId])

  const addBookmark = () => {
    if (!activeSceneId) return
    const scene = tour.scenes.find((s) => s.id === activeSceneId)
    setBookmarks((b) => [...b, {
      sceneId: activeSceneId,
      yaw: position.yaw,
      pitch: position.pitch,
      label: scene?.name || 'بوکمارک',
      createdAt: Date.now(),
    }])
  }

  const toggleFavorite = (sceneId: number) => {
    setFavorites((f) => f.includes(sceneId) ? f.filter((id) => id !== sceneId) : [...f, sceneId])
  }

  const copyLink = async () => {
    await navigator.clipboard.writeText(url)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  const embedCode = `<iframe src="${url}" width="100%" height="500" frameborder="0" allowfullscreen></iframe>`

  return (
    <>
      {/* Mini map */}
      {settings.mini_map !== false && tour.scenes.some((s) => s.floor_plan_x != null) && (
        <div className="absolute bottom-20 left-4 z-20 w-32 h-32 rounded-xl bg-black/50 backdrop-blur-md border border-white/10 p-2">
          <p className="text-[9px] text-white/60 mb-1 text-center">مینی‌مپ</p>
          <div className="relative w-full h-[calc(100%-14px)] bg-white/10 rounded">
            {tour.scenes.map((s) => (
              <button
                key={s.id}
                type="button"
                onClick={() => onGoToScene(s.id)}
                className={`absolute w-2.5 h-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full border transition-transform ${
                  s.id === activeSceneId ? 'border-white scale-125 bg-primary' : 'bg-white/50 border-white/50'
                }`}
                style={{ left: `${s.floor_plan_x ?? 50}%`, top: `${s.floor_plan_y ?? 50}%` }}
                title={s.name}
              />
            ))}
          </div>
        </div>
      )}

      {/* Floor selector */}
      {settings.floor_selector !== false && tour.scenes.length > 1 && (
        <div className="absolute top-20 left-4 z-20 flex flex-col gap-1 max-h-48 overflow-y-auto">
          {tour.scenes.map((s) => (
            <button
              key={s.id}
              type="button"
              onClick={() => onGoToScene(s.id)}
              className={`flex items-center gap-2 px-2.5 py-1.5 text-[10px] rounded-lg backdrop-blur border transition-all ${
                s.id === activeSceneId ? 'bg-primary/30 border-primary/50 text-white' : 'bg-black/40 border-white/10 text-white/80 hover:bg-white/10'
              }`}
            >
              <Layers className="h-3 w-3" />
              {s.name}
              {favorites.includes(s.id) && <Star className="h-2.5 w-2.5 fill-warning text-warning" />}
            </button>
          ))}
        </div>
      )}

      {/* Feature toolbar */}
      <div className="absolute top-4 left-4 z-20 flex flex-col gap-1">
        {settings.auto_tour && (
          <Button
            size="icon"
            variant="ghost"
            className="h-8 w-8 bg-black/40 backdrop-blur border border-white/10 text-white"
            onClick={isAutoTouring ? onStopAutoTour : onStartAutoTour}
            title="تور خودکار"
          >
            {isAutoTouring ? <Pause className="h-3.5 w-3.5" /> : <Play className="h-3.5 w-3.5" />}
          </Button>
        )}
        {settings.bookmarks !== false && (
          <Button size="icon" variant="ghost" className="h-8 w-8 bg-black/40 backdrop-blur border border-white/10 text-white" onClick={addBookmark} title="بوکمارک">
            <Bookmark className="h-3.5 w-3.5" />
          </Button>
        )}
        {settings.share_enabled !== false && (
          <Button size="icon" variant="ghost" className="h-8 w-8 bg-black/40 backdrop-blur border border-white/10 text-white" onClick={() => setShowShare(!showShare)} title="اشتراک">
            <Share2 className="h-3.5 w-3.5" />
          </Button>
        )}
        {activeSceneId && settings.favorites !== false && (
          <Button
            size="icon"
            variant="ghost"
            className="h-8 w-8 bg-black/40 backdrop-blur border border-white/10 text-white"
            onClick={() => toggleFavorite(activeSceneId)}
            title="علاقه‌مندی"
          >
            <Star className={`h-3.5 w-3.5 ${favorites.includes(activeSceneId) ? 'fill-warning text-warning' : ''}`} />
          </Button>
        )}
      </div>

      {/* Share panel */}
      {showShare && (
        <div className="absolute top-4 left-14 z-30 w-72 p-4 rounded-xl bg-black/80 backdrop-blur-xl border border-white/10 space-y-3">
          <p className="text-sm font-medium">اشتراک‌گذاری</p>
          <div className="flex gap-2">
            <Button size="sm" variant="outline" className="flex-1 border-white/20" onClick={copyLink}>
              {copied ? <Check className="h-3 w-3" /> : <Copy className="h-3 w-3" />}
              کپی لینک
            </Button>
            {settings.qr_enabled !== false && (
              <a href={`https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(url)}`} target="_blank" rel="noreferrer">
                <Button size="sm" variant="outline" className="border-white/20"><QrCode className="h-3 w-3" /></Button>
              </a>
            )}
          </div>
          {settings.embed_enabled !== false && (
            <div>
              <p className="text-[10px] text-muted mb-1 flex items-center gap-1"><Code className="h-3 w-3" />کد Embed</p>
              <textarea readOnly value={embedCode} className="w-full text-[9px] bg-black/40 border border-white/10 rounded p-2 font-mono h-16" onClick={(e) => (e.target as HTMLTextAreaElement).select()} />
            </div>
          )}
        </div>
      )}

      {/* Bookmarks list */}
      {bookmarks.length > 0 && settings.bookmarks !== false && (
        <div className="absolute bottom-20 right-4 z-20 w-40 max-h-32 overflow-y-auto rounded-xl bg-black/50 backdrop-blur border border-white/10 p-2">
          <p className="text-[9px] text-white/60 mb-1">بوکمارک‌ها</p>
          {bookmarks.map((b, i) => (
            <button key={i} type="button" onClick={() => onGoToScene(b.sceneId)} className="block w-full text-[10px] text-white/80 hover:text-primary truncate text-right py-0.5">
              {b.label}
            </button>
          ))}
        </div>
      )}

      {/* History */}
      {history.length > 1 && settings.history !== false && (
        <div className="absolute top-20 right-20 z-20 flex gap-1">
          <History className="h-3 w-3 text-white/40 self-center" />
          {history.slice(-4).map((id) => {
            const s = tour.scenes.find((sc) => sc.id === id)
            return s ? (
              <button key={id} type="button" onClick={() => onGoToScene(id)} className="text-[9px] px-1.5 py-0.5 rounded bg-black/40 text-white/70 hover:text-primary border border-white/10">
                {s.name.slice(0, 6)}
              </button>
            ) : null
          })}
        </div>
      )}
    </>
  )
}
