import { Compass, Maximize, Minimize, Minus, Pause, Play, Plus, RotateCcw, Smartphone, Camera, Glasses, ZoomIn } from 'lucide-react'
import type { TourEngineControls } from './useTourEngine'
import type { ViewerPosition } from '../types'

interface Props {
  controls: TourEngineControls
  position: ViewerPosition
  isAutoRotating: boolean
  isGyroActive: boolean
  isVrActive: boolean
  isFullscreen: boolean
  onToggleFullscreen: () => void
  showVr?: boolean
  showGyro?: boolean
  brandColor?: string
}

function formatDeg(value: number): string {
  return `${Math.round(value)}°`
}

export function TourViewerControls({
  controls,
  position,
  isAutoRotating,
  isGyroActive,
  isVrActive,
  isFullscreen,
  onToggleFullscreen,
  showVr = true,
  showGyro = true,
  brandColor = '#2dd4bf',
}: Props) {
  const handleScreenshot = () => {
    const dataUrl = controls.takeScreenshot()
    if (!dataUrl) return
    const link = document.createElement('a')
    link.href = dataUrl
    link.download = `tour-screenshot-${Date.now()}.jpg`
    link.click()
  }

  const btnClass =
    'flex items-center justify-center w-9 h-9 rounded-xl bg-black/40 backdrop-blur-md border border-white/10 text-white/90 hover:bg-white/10 hover:border-white/20 transition-all duration-200 hover:scale-105 active:scale-95'

  return (
    <>
      {/* Top stats bar */}
      <div className="absolute top-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2 px-3 py-1.5 rounded-full bg-black/40 backdrop-blur-md border border-white/10 text-[11px] text-white/80 font-mono">
        <span>Yaw {formatDeg(position.yaw)}</span>
        <span className="text-white/30">|</span>
        <span>Pitch {formatDeg(position.pitch)}</span>
        <span className="text-white/30">|</span>
        <span>Zoom {Math.round(position.zoom)}</span>
      </div>

      {/* Compass */}
      <div className="absolute top-4 right-4 z-20">
        <div
          className="w-12 h-12 rounded-full bg-black/40 backdrop-blur-md border border-white/10 flex items-center justify-center"
          style={{ transform: `rotate(${-position.yaw}deg)` }}
        >
          <Compass className="h-6 w-6" style={{ color: brandColor }} />
        </div>
        <p className="text-[9px] text-center text-white/50 mt-1">N</p>
      </div>

      {/* Bottom toolbar */}
      <div className="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5 p-1.5 rounded-2xl bg-black/50 backdrop-blur-xl border border-white/10">
        <button type="button" className={btnClass} onClick={controls.zoomIn} title="بزرگنمایی">
          <Plus className="h-4 w-4" />
        </button>
        <button type="button" className={btnClass} onClick={controls.zoomOut} title="کوچک‌نمایی">
          <Minus className="h-4 w-4" />
        </button>
        <button type="button" className={btnClass} onClick={controls.resetView} title="بازنشانی نما">
          <RotateCcw className="h-4 w-4" />
        </button>
        <div className="w-px h-6 bg-white/10" />
        <button
          type="button"
          className={`${btnClass} ${isAutoRotating ? 'bg-primary/30 border-primary/50' : ''}`}
          onClick={controls.toggleAutoRotate}
          title={isAutoRotating ? 'توقف چرخش' : 'چرخش خودکار'}
        >
          {isAutoRotating ? <Pause className="h-4 w-4" /> : <Play className="h-4 w-4" />}
        </button>
        {showGyro && (
          <button
            type="button"
            className={`${btnClass} ${isGyroActive ? 'bg-primary/30 border-primary/50' : ''}`}
            onClick={controls.toggleGyroscope}
            title="ژیروسکوپ"
          >
            <Smartphone className="h-4 w-4" />
          </button>
        )}
        {showVr && (
          <button
            type="button"
            className={`${btnClass} ${isVrActive ? 'bg-primary/30 border-primary/50' : ''}`}
            onClick={controls.toggleVr}
            title="حالت VR"
          >
            <Glasses className="h-4 w-4" />
          </button>
        )}
        <div className="w-px h-6 bg-white/10" />
        <button type="button" className={btnClass} onClick={handleScreenshot} title="اسکرین‌شات">
          <Camera className="h-4 w-4" />
        </button>
        <button type="button" className={btnClass} onClick={onToggleFullscreen} title="تمام‌صفحه">
          {isFullscreen ? <Minimize className="h-4 w-4" /> : <Maximize className="h-4 w-4" />}
        </button>
      </div>

      {/* Zoom indicator */}
      <div className="absolute bottom-4 right-4 z-20 flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-black/40 backdrop-blur-md border border-white/10 text-[10px] text-white/70">
        <ZoomIn className="h-3 w-3" />
        FOV ~{Math.round(90 - position.zoom * 0.6)}°
      </div>
    </>
  )
}
