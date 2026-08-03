import { forwardRef, useImperativeHandle, useState, useRef } from 'react'
import '@photo-sphere-viewer/core/index.css'
import '@photo-sphere-viewer/virtual-tour-plugin/index.css'
import '@photo-sphere-viewer/markers-plugin/index.css'
import type { TourData, TourHotspot } from '../types'
import { useTourEngine } from './useTourEngine'
import { TourViewerControls } from './TourViewerControls'
import { TourLoadingOverlay } from './TourLoadingOverlay'
import { HotspotPopup } from '../hotspots/HotspotPopup'
import { executeHotspotAction } from '../hotspots/hotspotActions'
import { TourFeaturesOverlay } from '../features/TourFeaturesOverlay'
import { HOTSPOT_MARKER_CSS } from '../hotspots/markerHtml'

export interface TourViewerHandle {
  goToScene: (sceneId: number) => void
}

interface Props {
  tour: TourData
  initialSceneId?: number | null
  onSceneChange?: (sceneId: number) => void
  className?: string
  showControls?: boolean
  showSceneName?: boolean
  showFeatures?: boolean
  editorMode?: boolean
  sceneHotspots?: TourHotspot[]
  isPlacingHotspot?: boolean
  onPlaceHotspot?: (yaw: number, pitch: number) => void
  onHotspotSelect?: (hotspot: TourHotspot) => void
  onLeadForm?: () => void
  publicUrl?: string
}

export const TourViewer = forwardRef<TourViewerHandle, Props>(function TourViewer(
  {
    tour,
    initialSceneId,
    onSceneChange,
    className = '',
    showControls = true,
    showSceneName = true,
    showFeatures = false,
    editorMode = false,
    sceneHotspots,
    isPlacingHotspot = false,
    onPlaceHotspot,
    onHotspotSelect,
    onLeadForm,
    publicUrl,
  },
  ref,
) {
  const [popupHotspot, setPopupHotspot] = useState<TourHotspot | null>(null)
  const [galleryImages, setGalleryImages] = useState<string[] | null>(null)
  const [isAutoTouring, setIsAutoTouring] = useState(false)
  const goToSceneRef = useRef<(id: number) => void>(() => {})

  const {
    containerRef,
    activeSceneId,
    activeScene,
    position,
    isLoading,
    loadProgress,
    loadError,
    isAutoRotating,
    isGyroActive,
    isVrActive,
    isFullscreen,
    controls,
    toggleFullscreen,
  } = useTourEngine({
    tour,
    initialSceneId,
    onSceneChange,
    enableGyroscope: tour.settings?.enable_gyroscope !== false,
    enableVr: tour.settings?.enable_vr !== false,
    autoRotate: tour.settings?.auto_rotate ?? false,
    autoRotateSpeed: tour.settings?.auto_rotate_speed ?? 0.5,
    sceneHotspots: sceneHotspots ?? [],
    editorMode,
    isPlacingHotspot,
    onPlaceHotspot,
    onHotspotSelect,
    onHotspotActivate: (hotspot) => {
      executeHotspotAction(hotspot, {
        tour,
        onGoToScene: (id) => goToSceneRef.current(id),
        onShowPopup: setPopupHotspot,
        onShowGallery: setGalleryImages,
        onShowLeadForm: onLeadForm,
      })
    },
  })

  goToSceneRef.current = controls.goToScene

  useImperativeHandle(ref, () => ({
    goToScene: controls.goToScene,
  }))

  const brandColor = tour.settings?.brand_color || '#2dd4bf'

  const startAutoTour = () => {
    setIsAutoTouring(true)
    const scenes = tour.scenes.filter((s) => s.is_visible !== false)
    let idx = scenes.findIndex((s) => s.id === activeSceneId)
    const interval = (tour.settings?.auto_tour_interval ?? 8) * 1000
    const timer = setInterval(() => {
      idx = (idx + 1) % scenes.length
      controls.goToScene(scenes[idx].id)
    }, interval)
    ;(window as unknown as { __vtAutoTour?: ReturnType<typeof setInterval> }).__vtAutoTour = timer
  }

  const stopAutoTour = () => {
    setIsAutoTouring(false)
    const w = window as unknown as { __vtAutoTour?: ReturnType<typeof setInterval> }
    if (w.__vtAutoTour) clearInterval(w.__vtAutoTour)
  }

  if (!tour.scenes.length) {
    return (
      <div className={`flex items-center justify-center bg-black/90 text-white/50 ${className}`}>
        <p className="text-sm">صحنه‌ای برای نمایش وجود ندارد</p>
      </div>
    )
  }

  return (
    <div className={`relative w-full h-full min-h-[50vh] bg-black overflow-hidden ${className}`}>
      <div ref={containerRef} className="absolute inset-0" />

      {isPlacingHotspot && (
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-30 px-4 py-2 rounded-full bg-primary/90 text-primary-foreground text-sm font-medium pointer-events-none animate-pulse">
          روی تصویر کلیک کنید
        </div>
      )}

      <TourLoadingOverlay progress={loadProgress} isLoading={isLoading} error={loadError} />

      {showControls && !loadError && (
        <TourViewerControls
          controls={controls}
          position={position}
          isAutoRotating={isAutoRotating}
          isGyroActive={isGyroActive}
          isVrActive={isVrActive}
          isFullscreen={isFullscreen}
          onToggleFullscreen={toggleFullscreen}
          showVr={tour.settings?.enable_vr !== false}
          showGyro={tour.settings?.enable_gyroscope !== false}
          brandColor={brandColor}
        />
      )}

      {showFeatures && !editorMode && !loadError && (
        <TourFeaturesOverlay
          tour={tour}
          activeSceneId={activeSceneId}
          position={position}
          onGoToScene={controls.goToScene}
          onStartAutoTour={startAutoTour}
          onStopAutoTour={stopAutoTour}
          isAutoTouring={isAutoTouring}
          publicUrl={publicUrl}
        />
      )}

      {showSceneName && activeScene && !isLoading && (
        <div className="absolute top-16 right-4 z-10 px-4 py-2 rounded-xl bg-black/40 backdrop-blur-md border border-white/10 text-white text-sm">
          {activeScene.name}
        </div>
      )}

      {popupHotspot && (
        <HotspotPopup
          hotspot={popupHotspot}
          tour={tour}
          onClose={() => setPopupHotspot(null)}
          onLeadForm={onLeadForm}
        />
      )}

      {galleryImages && (
        <div className="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4" onClick={() => setGalleryImages(null)}>
          <div className="grid grid-cols-2 gap-3 max-w-2xl" onClick={(e) => e.stopPropagation()}>
            {galleryImages.map((url, i) => (
              <img key={i} src={url} alt="" className="rounded-xl w-full object-cover" />
            ))}
          </div>
        </div>
      )}

      <style>{`
        .psv-container { font-family: inherit; }
        .psv-canvas-container {
          cursor: ${isPlacingHotspot ? 'crosshair' : 'grab'};
          touch-action: none;
          -webkit-tap-highlight-color: transparent;
        }
        .psv-canvas-container:active { cursor: ${isPlacingHotspot ? 'crosshair' : 'grabbing'}; }
        @media (max-width: 768px) {
          .psv-container { height: 100% !important; }
          .psv-canvas-container canvas { image-rendering: auto; }
        }
        ${HOTSPOT_MARKER_CSS}
      `}</style>
    </div>
  )
})
