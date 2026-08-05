import { forwardRef, useImperativeHandle, useState, useCallback, useRef } from 'react'
import { ZoomIn, ZoomOut, Maximize2, Minimize2, RotateCcw, Undo2, Redo2, Copy, ClipboardPaste } from 'lucide-react'
import type { TourData, TourHotspot } from '../types'
import { useSmartWalkEngine } from './useSmartWalkEngine'
import { clickToImagePercent } from './smartWalkHotspots'
import { TourLoadingOverlay } from '../engine/TourLoadingOverlay'
import { HotspotPopup } from '../hotspots/HotspotPopup'
import { executeHotspotAction } from '../hotspots/hotspotActions'
import { SmartWalkThemeProvider } from './theme/SmartWalkTheme'
import { SmartWalkMiniMap } from './components/SmartWalkMiniMap'
import { SmartWalkTimeline } from './components/SmartWalkTimeline'
import { SmartWalkSidebar } from './components/SmartWalkSidebar'
import { SmartWalkHotspotLayer } from './components/SmartWalkHotspotLayer'
import { Button } from '@/components/ui/button'
import type { TransitionEffect } from './transitions/transitionEngine'
import './smart-walk.css'

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
  onHotspotUpdate?: (hotspot: TourHotspot) => void
  onUndo?: () => void
  onRedo?: () => void
  onCopy?: () => void
  onPaste?: () => void
  canUndo?: boolean
  canRedo?: boolean
  selectedHotspotId?: number | string | null
  onLeadForm?: () => void
  onHotspotActivate?: (hotspot: TourHotspot, sceneId: number) => void
}

export const SmartWalkViewer = forwardRef<SmartWalkViewerHandle, Props>(function SmartWalkViewer(
  props,
  ref,
) {
  const {
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
    onHotspotUpdate,
    onUndo,
    onRedo,
    onCopy,
    onPaste,
    canUndo,
    canRedo,
    selectedHotspotId,
    onLeadForm,
    onHotspotActivate,
  } = props
  const [popupHotspot, setPopupHotspot] = useState<TourHotspot | null>(null)
  const [galleryImages, setGalleryImages] = useState<string[] | null>(null)
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [favorite, setFavorite] = useState(false)
  const [placementPreview, setPlacementPreview] = useState<{ x: number; y: number } | null>(null)
  const layerRef = useRef<HTMLDivElement>(null)

  const themeMode = tour.settings?.theme_mode ?? 'dark'
  const showMiniMap = tour.settings?.mini_map !== false && tour.settings?.show_floor_plan !== false

  const engine = useSmartWalkEngine({
    tour,
    initialSceneId,
    onSceneChange,
    editorMode,
    sceneHotspots,
    isPlacingHotspot,
    onPlaceHotspot,
    cameraDisabled: false,
  })

  const navigateToScene = useCallback((sceneId: number, hotspot?: TourHotspot) => {
    const effect = (hotspot?.action?.transition_effect ?? 'fade') as TransitionEffect
    const duration = hotspot?.action?.transition_duration ?? 900
    if (hotspot && (hotspot.type === 'scene' || hotspot.action?.type === 'scene')) {
      engine.goToScene(sceneId, { walkFromHotspot: hotspot, effect, duration })
    } else {
      engine.goToScene(sceneId, { effect, duration })
    }
  }, [engine])

  useImperativeHandle(ref, () => ({
    goToScene: (id: number) => engine.goToScene(id),
  }))

  const handleHotspotClick = (hotspot: TourHotspot) => {
    if (editorMode) {
      onHotspotSelect?.(hotspot)
      return
    }

    if (engine.activeSceneId) {
      onHotspotActivate?.(hotspot, engine.activeSceneId)
    }

    if (hotspot.type === 'scene' || hotspot.action?.type === 'scene') {
      const target = hotspot.target_scene_id ?? hotspot.action?.target_scene_id
      if (target) {
        navigateToScene(target, hotspot)
        return
      }
    }

    if (hotspot.type === 'custom' && hotspot.action?.url) {
      try {
        // Future: sandboxed custom actions; for now support share URLs
        if (hotspot.action.url.startsWith('javascript:')) return
        window.open(hotspot.action.url, '_blank')
      } catch { /* ignore */ }
      return
    }

    executeHotspotAction(hotspot, {
      tour,
      onGoToScene: (id) => navigateToScene(id, hotspot),
      onShowPopup: setPopupHotspot,
      onShowGallery: setGalleryImages,
      onShowLeadForm: onLeadForm,
    })
  }

  const brandColor = tour.settings?.brand_color || '#2dd4bf'
  const tf = engine.transitionFrame

  const shareTour = async () => {
    const url = tour.public_url || window.location.href
    if (navigator.share) {
      await navigator.share({ title: tour.title, url })
    } else {
      await navigator.clipboard.writeText(url)
    }
  }

  const handleViewportMouseMove = useCallback((e: React.MouseEvent) => {
    if (!isPlacingHotspot || !engine.containerRef.current) {
      setPlacementPreview(null)
      return
    }
    const { x, y } = clickToImagePercent(
      e.clientX,
      e.clientY,
      engine.containerRef.current,
      engine.cameraRef.current,
      engine.imageWidth,
      engine.imageHeight,
    )
    setPlacementPreview({ x, y })
  }, [isPlacingHotspot, engine.containerRef, engine.cameraRef, engine.imageWidth, engine.imageHeight])

  const handleViewportMouseLeave = useCallback(() => {
    setPlacementPreview(null)
  }, [])

  return (
    <SmartWalkThemeProvider initialMode={themeMode}>
      <div className={`smart-walk-root relative overflow-hidden h-full min-h-[320px] ${className}`}>
        {/* Viewport */}
        <div
          ref={engine.setContainerRef}
          className={`smart-walk-viewport absolute inset-0 overflow-hidden ${
            isPlacingHotspot
              ? 'cursor-crosshair'
              : editorMode
                ? 'cursor-grab active:cursor-grabbing'
                : engine.isDragging
                  ? 'cursor-grabbing'
                  : 'cursor-grab'
          }`}
          style={{
            filter: tf.blurPx > 0 ? `blur(${tf.blurPx}px)` : undefined,
            transition: 'filter 0.15s ease-out',
          }}
          onClick={engine.handleClick}
          onPointerDown={engine.onViewportPointerDown}
          onPointerMove={engine.onViewportPointerMove}
          onPointerUp={engine.onViewportPointerUp}
          onPointerCancel={engine.onViewportPointerUp}
          onDoubleClick={engine.onViewportDoubleClick}
          onMouseMove={handleViewportMouseMove}
          onMouseLeave={handleViewportMouseLeave}
        >
          <div
            ref={layerRef}
            className="smart-walk-layer absolute left-1/2 top-1/2"
            style={{
              width: engine.imageWidth,
              height: engine.imageHeight,
              transform: `translate(-50%, -50%) ${engine.transformCss}`,
            }}
          >
            {engine.imageUrl && (
              <img
                src={engine.imageUrl}
                alt={engine.activeScene?.name || ''}
                width={engine.imageWidth}
                height={engine.imageHeight}
                className="smart-walk-image block"
                style={{ opacity: tf.outgoingOpacity }}
                draggable={false}
                loading="eager"
                onLoad={engine.handleImageLoad}
              />
            )}

            {!engine.isLoading && (
              <SmartWalkHotspotLayer
                hotspots={engine.hotspots}
                brandColor={brandColor}
                layerRef={layerRef}
                editorMode={editorMode}
                selectedId={selectedHotspotId}
                placementPreview={isPlacingHotspot ? placementPreview : null}
                onHotspotClick={handleHotspotClick}
                onHotspotMove={(h, x, y) => {
                  onHotspotUpdate?.({ ...h, position_x: x, position_y: y })
                }}
                onHotspotResize={(h, size) => onHotspotUpdate?.({ ...h, style: { ...h.style, size } })}
                onHotspotRotate={(h, rotation) => onHotspotUpdate?.({ ...h, style: { ...h.style, rotation } })}
                showGuides={editorMode}
              />
            )}
          </div>
        </div>

        {/* Transition overlay */}
        {tf.overlayOpacity > 0 && (
          <div
            className="absolute inset-0 z-20 pointer-events-none bg-black"
            style={{ opacity: tf.overlayOpacity }}
          />
        )}

        {editorMode && isPlacingHotspot && (
          <div className="absolute top-14 left-1/2 -translate-x-1/2 z-30 px-3 py-1.5 rounded-full bg-primary/90 text-white text-[11px] pointer-events-none max-w-[90%] text-center leading-relaxed">
            بکشید برای جابجایی — نشانگر را دنبال کنید — کلیک برای ثبت نقطه
          </div>
        )}

        {(engine.isLoading || engine.loadError) && (
          <TourLoadingOverlay progress={engine.loadProgress} isLoading={engine.isLoading} error={engine.loadError} />
        )}

        {showSceneName && engine.activeScene && (
          <div className="sw-panel absolute top-4 left-1/2 -translate-x-1/2 z-30 px-4 py-1.5 text-sm font-medium rounded-full">
            {engine.activeScene.name}
          </div>
        )}

        {showMiniMap && !editorMode && (
          <SmartWalkMiniMap
            scenes={tour.scenes}
            activeSceneId={engine.activeSceneId}
            visitedSceneIds={engine.visitedSceneIds}
            floorPlanUrl={tour.settings?.floor_plan_url}
            onSelectScene={(id) => engine.goToScene(id)}
          />
        )}

        <SmartWalkSidebar
          tour={tour}
          open={sidebarOpen}
          onToggle={() => setSidebarOpen((v) => !v)}
          onContact={onLeadForm}
          onShare={shareTour}
          favorite={favorite}
          onToggleFavorite={() => setFavorite((v) => !v)}
        />

        {!editorMode && (
          <SmartWalkTimeline
            scenes={tour.scenes}
            activeSceneId={engine.activeSceneId}
            onSelectScene={(id) => engine.goToScene(id)}
          />
        )}

        {showControls && (
          <div className="absolute bottom-20 md:bottom-24 right-3 z-30 flex flex-col gap-1.5">
            {editorMode && onUndo && (
              <Button size="icon" variant="outline" className="h-9 w-9 sw-btn" disabled={!canUndo} onClick={onUndo}>
                <Undo2 className="h-4 w-4" />
              </Button>
            )}
            {editorMode && onRedo && (
              <Button size="icon" variant="outline" className="h-9 w-9 sw-btn" disabled={!canRedo} onClick={onRedo}>
                <Redo2 className="h-4 w-4" />
              </Button>
            )}
            {editorMode && onCopy && (
              <Button size="icon" variant="outline" className="h-9 w-9 sw-btn" onClick={onCopy}>
                <Copy className="h-4 w-4" />
              </Button>
            )}
            {editorMode && onPaste && (
              <Button size="icon" variant="outline" className="h-9 w-9 sw-btn" onClick={onPaste}>
                <ClipboardPaste className="h-4 w-4" />
              </Button>
            )}
            <Button size="icon" variant="outline" className="h-9 w-9 sw-btn" onClick={() => engine.zoom(0.2 * engine.fitScale)}>
              <ZoomIn className="h-4 w-4" />
            </Button>
            <Button size="icon" variant="outline" className="h-9 w-9 sw-btn" onClick={() => engine.zoom(-0.2 * engine.fitScale)}>
              <ZoomOut className="h-4 w-4" />
            </Button>
            <Button size="icon" variant="outline" className="h-9 w-9 sw-btn" onClick={engine.resetView}>
              <RotateCcw className="h-4 w-4" />
            </Button>
            <Button size="icon" variant="outline" className="h-9 w-9 sw-btn" onClick={engine.toggleFullscreen}>
              {engine.isFullscreen ? <Minimize2 className="h-4 w-4" /> : <Maximize2 className="h-4 w-4" />}
            </Button>
          </div>
        )}

        {/* Zoom indicator */}
        <div className="sw-panel absolute bottom-20 md:bottom-24 left-3 z-30 px-2 py-1 text-[10px] font-mono rounded">
          {engine.zoomPercent ?? Math.round(engine.camera.scale * 100)}%
        </div>

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
    </SmartWalkThemeProvider>
  )
})
