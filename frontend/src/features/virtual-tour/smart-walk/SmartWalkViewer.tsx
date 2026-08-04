import { forwardRef, useImperativeHandle, useState } from 'react'
import { ZoomIn, ZoomOut, Maximize2, Minimize2, RotateCcw } from 'lucide-react'
import type { TourData, TourHotspot } from '../types'
import { useSmartWalkEngine } from './useSmartWalkEngine'
import { TourLoadingOverlay } from '../engine/TourLoadingOverlay'
import { HotspotPopup } from '../hotspots/HotspotPopup'
import { executeHotspotAction } from '../hotspots/hotspotActions'
import { Button } from '@/components/ui/button'

export interface SmartWalkViewerHandle {
  goToScene: (sceneId: number) => void
}

interface Props {
  tour: TourData
  initialSceneId?: number | null
  onSceneChange?: (sceneId: number) => void
  className?: string
  showControls?: boolean
  showSceneName?: boolean
  editorMode?: boolean
  sceneHotspots?: TourHotspot[]
  isPlacingHotspot?: boolean
  onPlaceHotspot?: (x: number, y: number) => void
  onHotspotSelect?: (hotspot: TourHotspot) => void
  onLeadForm?: () => void
}

export const SmartWalkViewer = forwardRef<SmartWalkViewerHandle, Props>(function SmartWalkViewer(
  {
    tour,
    initialSceneId,
    onSceneChange,
    className = '',
    showControls = true,
    showSceneName = true,
    editorMode = false,
    sceneHotspots,
    isPlacingHotspot = false,
    onPlaceHotspot,
    onHotspotSelect,
    onLeadForm,
  },
  ref,
) {
  const [popupHotspot, setPopupHotspot] = useState<TourHotspot | null>(null)
  const [galleryImages, setGalleryImages] = useState<string[] | null>(null)

  const engine = useSmartWalkEngine({
    tour,
    initialSceneId,
    onSceneChange,
    editorMode,
    sceneHotspots,
    isPlacingHotspot,
    onPlaceHotspot,
    onHotspotSelect,
  })

  useImperativeHandle(ref, () => ({
    goToScene: engine.goToScene,
  }))

  const handleHotspotClick = (hotspot: TourHotspot) => {
    if (editorMode && onHotspotSelect) {
      onHotspotSelect(hotspot)
      return
    }

    executeHotspotAction(hotspot, {
      tour,
      onGoToScene: engine.goToScene,
      onShowPopup: setPopupHotspot,
      onShowGallery: setGalleryImages,
      onShowLeadForm: onLeadForm,
    })
  }

  const brandColor = tour.settings?.brand_color || '#2dd4bf'

  return (
    <div className={`relative overflow-hidden bg-black select-none ${className}`}>
      <div
        ref={engine.containerRef}
        className={`absolute inset-0 touch-none overflow-hidden ${isPlacingHotspot ? 'cursor-crosshair' : 'cursor-grab active:cursor-grabbing'}`}
        onWheel={engine.handleWheel}
        onClick={engine.handleClick}
      >
        <div
          className="absolute left-1/2 top-1/2"
          style={{
            width: engine.imageWidth,
            height: engine.imageHeight,
            transform: `translate(-50%, -50%) translate(${engine.transform.x}px, ${engine.transform.y}px) scale(${engine.transform.scale})`,
            transformOrigin: 'center center',
            willChange: 'transform',
          }}
        >
          {engine.imageUrl && (
            <img
              src={engine.imageUrl}
              alt={engine.activeScene?.name || ''}
              width={engine.imageWidth}
              height={engine.imageHeight}
              className={`block transition-opacity duration-300 ${engine.isTransitioning ? 'opacity-0' : 'opacity-100'}`}
              draggable={false}
              loading="eager"
            />
          )}

          {!engine.isLoading && engine.hotspots.map((h) => {
            const px = h.position_x ?? 50
            const py = h.position_y ?? 50
            const size = h.style?.size ?? 32
            const color = h.style?.color || brandColor

            return (
              <button
                key={h.id}
                type="button"
                className={`absolute z-10 rounded-full border-2 flex items-center justify-center transition-transform hover:scale-110 ${
                  h.style?.pulse ? 'animate-pulse' : ''
                }`}
                style={{
                  left: `${px}%`,
                  top: `${py}%`,
                  width: size,
                  height: size,
                  marginLeft: -size / 2,
                  marginTop: -size / 2,
                  borderColor: color,
                  background: `${color}33`,
                  boxShadow: h.style?.glow ? `0 0 12px ${color}` : undefined,
                }}
                title={h.tooltip || h.label || h.title || ''}
                onClick={(e) => {
                  e.stopPropagation()
                  handleHotspotClick(h)
                }}
              >
                <span className="text-xs text-white font-bold">
                  {h.type === 'scene' ? '➡' : '•'}
                </span>
              </button>
            )
          })}
        </div>
      </div>

      {(engine.isLoading || engine.loadError) && (
        <TourLoadingOverlay
          progress={engine.loadProgress}
          isLoading={engine.isLoading}
          error={engine.loadError}
        />
      )}

      {showSceneName && engine.activeScene && (
        <div className="absolute top-4 left-4 z-20 px-3 py-1.5 rounded-lg bg-black/50 backdrop-blur text-sm font-medium">
          {engine.activeScene.name}
        </div>
      )}

      {showControls && (
        <div className="absolute bottom-4 right-4 z-20 flex gap-1.5">
          <Button size="icon" variant="outline" className="h-9 w-9 border-white/20 bg-black/40" onClick={() => engine.zoom(0.2)}>
            <ZoomIn className="h-4 w-4" />
          </Button>
          <Button size="icon" variant="outline" className="h-9 w-9 border-white/20 bg-black/40" onClick={() => engine.zoom(-0.2)}>
            <ZoomOut className="h-4 w-4" />
          </Button>
          <Button size="icon" variant="outline" className="h-9 w-9 border-white/20 bg-black/40" onClick={engine.resetView}>
            <RotateCcw className="h-4 w-4" />
          </Button>
          <Button size="icon" variant="outline" className="h-9 w-9 border-white/20 bg-black/40" onClick={engine.toggleFullscreen}>
            {engine.isFullscreen ? <Minimize2 className="h-4 w-4" /> : <Maximize2 className="h-4 w-4" />}
          </Button>
        </div>
      )}

      {/* Scene strip for multi-room navigation */}
      {tour.scenes.length > 1 && !editorMode && (
        <div className="absolute bottom-4 left-4 z-20 flex gap-2 max-w-[60%] overflow-x-auto">
          {tour.scenes.map((s) => (
            <button
              key={s.id}
              type="button"
              onClick={() => engine.goToScene(s.id)}
              className={`shrink-0 w-14 h-10 rounded-lg border overflow-hidden transition-all ${
                s.id === engine.activeSceneId ? 'border-primary ring-2 ring-primary/50' : 'border-white/20 opacity-70 hover:opacity-100'
              }`}
            >
              {s.thumbnail_url ? (
                <img src={s.thumbnail_url} alt="" className="w-full h-full object-cover" loading="lazy" />
              ) : (
                <div className="w-full h-full bg-white/10 text-[10px] flex items-center justify-center truncate px-1">{s.name}</div>
              )}
            </button>
          ))}
        </div>
      )}

      {popupHotspot && (
        <HotspotPopup hotspot={popupHotspot} tour={tour} onClose={() => setPopupHotspot(null)} onLeadForm={onLeadForm} />
      )}

      {galleryImages && (
        <div className="fixed inset-0 z-50 bg-black/90 flex flex-col">
          <div className="flex justify-between items-center p-4">
            <h2 className="font-bold">گالری</h2>
            <Button variant="ghost" onClick={() => setGalleryImages(null)}>بستن</Button>
          </div>
          <div className="flex-1 overflow-y-auto p-4 grid grid-cols-2 md:grid-cols-3 gap-3">
            {galleryImages.map((url, i) => (
              <img key={i} src={url} alt="" className="w-full rounded-lg object-cover" loading="lazy" />
            ))}
          </div>
        </div>
      )}
    </div>
  )
})
