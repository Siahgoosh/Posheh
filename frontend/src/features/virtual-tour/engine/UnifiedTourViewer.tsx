import { forwardRef } from 'react'
import type { TourData, TourHotspot } from '../types'
import { TourViewer, type TourViewerHandle } from '../engine/TourViewer'
import { SmartWalkViewer, type SmartWalkViewerHandle } from '../smart-walk/SmartWalkViewer'
import type { SceneTransitionOptions } from '../engine/useTourEngine'

export type UnifiedTourViewerHandle = TourViewerHandle | SmartWalkViewerHandle

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
  onPlaceHotspot?: (a: number, b: number) => void
  onHotspotSelect?: (hotspot: TourHotspot) => void
  onHotspotMove?: (hotspot: TourHotspot, yaw: number, pitch: number) => void
  onHotspotUpdate?: (hotspot: TourHotspot) => void
  selectedHotspotId?: number | string | null
  onUndo?: () => void
  onRedo?: () => void
  onCopy?: () => void
  onPaste?: () => void
  canUndo?: boolean
  canRedo?: boolean
  isRepositioningHotspot?: boolean
  repositionHotspot?: TourHotspot | null
  onLeadForm?: () => void
  publicUrl?: string
  onHotspotActivate?: (hotspot: TourHotspot, sceneId: number) => void
}

export const UnifiedTourViewer = forwardRef<UnifiedTourViewerHandle, Props>(function UnifiedTourViewer(
  props,
  ref,
) {
  const isSmartWalk = props.tour.tour_type === 'smart_walk'

  if (isSmartWalk) {
    return (
      <SmartWalkViewer
        ref={ref as React.Ref<SmartWalkViewerHandle>}
        tour={props.tour}
        initialSceneId={props.initialSceneId}
        onSceneChange={props.onSceneChange}
        className={props.className}
        showControls={props.showControls}
        showSceneName={props.showSceneName}
        editorMode={props.editorMode}
        sceneHotspots={props.sceneHotspots}
        isPlacingHotspot={props.isPlacingHotspot}
        onPlaceHotspot={props.onPlaceHotspot}
        onHotspotSelect={props.onHotspotSelect}
        onHotspotUpdate={props.onHotspotUpdate}
        selectedHotspotId={props.selectedHotspotId}
        onUndo={props.onUndo}
        onRedo={props.onRedo}
        onCopy={props.onCopy}
        onPaste={props.onPaste}
        canUndo={props.canUndo}
        canRedo={props.canRedo}
        onLeadForm={props.onLeadForm}
        onHotspotActivate={props.onHotspotActivate}
      />
    )
  }

  return (
    <TourViewer
      ref={ref as React.Ref<TourViewerHandle>}
      tour={props.tour}
      initialSceneId={props.initialSceneId}
      onSceneChange={props.onSceneChange}
      className={props.className}
      showControls={props.showControls}
      showSceneName={props.showSceneName}
      showFeatures={props.showFeatures}
      editorMode={props.editorMode}
      sceneHotspots={props.sceneHotspots}
      isPlacingHotspot={props.isPlacingHotspot}
      onPlaceHotspot={props.onPlaceHotspot}
      onHotspotSelect={props.onHotspotSelect}
      onHotspotMove={props.onHotspotMove}
      isRepositioningHotspot={props.isRepositioningHotspot}
      repositionHotspot={props.repositionHotspot}
      onLeadForm={props.onLeadForm}
      publicUrl={props.publicUrl}
      onHotspotActivate={props.onHotspotActivate}
    />
  )
})

export function goToSceneUnified(
  handle: UnifiedTourViewerHandle | null,
  sceneId: number,
  options?: SceneTransitionOptions,
): void {
  if (!handle) return
  if ('goToScene' in handle) {
    handle.goToScene(sceneId, options)
  }
}
