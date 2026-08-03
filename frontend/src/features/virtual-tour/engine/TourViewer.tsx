import { forwardRef, useImperativeHandle } from 'react'
import '@photo-sphere-viewer/core/index.css'
import '@photo-sphere-viewer/virtual-tour-plugin/index.css'
import type { TourData } from '../types'
import { useTourEngine } from './useTourEngine'
import { TourViewerControls } from './TourViewerControls'
import { TourLoadingOverlay } from './TourLoadingOverlay'

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
}

export const TourViewer = forwardRef<TourViewerHandle, Props>(function TourViewer(
  {
    tour,
    initialSceneId,
    onSceneChange,
    className = '',
    showControls = true,
    showSceneName = true,
  },
  ref,
) {
  const {
    containerRef,
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
  })

  useImperativeHandle(ref, () => ({
    goToScene: controls.goToScene,
  }))

  const brandColor = tour.settings?.brand_color || '#2dd4bf'

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

      {showSceneName && activeScene && !isLoading && (
        <div className="absolute top-16 right-4 z-10 px-4 py-2 rounded-xl bg-black/40 backdrop-blur-md border border-white/10 text-white text-sm">
          {activeScene.name}
        </div>
      )}

      <style>{`
        .psv-container { font-family: inherit; }
        .psv-canvas-container { cursor: grab; }
        .psv-canvas-container:active { cursor: grabbing; }
      `}</style>
    </div>
  )
})
