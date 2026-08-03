import { useEffect, useRef } from 'react'
import type { MarkersPlugin } from '@photo-sphere-viewer/markers-plugin'
import type { Viewer } from '@photo-sphere-viewer/core'
import type { TourHotspot } from '../types'
import { buildHotspotMarkerHtml } from './markerHtml'

export function syncHotspotMarkers(
  markersPlugin: MarkersPlugin,
  hotspots: TourHotspot[],
  brandColor?: string,
  editorMode = false,
): void {
  markersPlugin.clearMarkers()

  hotspots.forEach((hotspot) => {
    const id = `hotspot-${hotspot.id}`
    markersPlugin.addMarker({
      id,
      position: { yaw: `${hotspot.yaw}deg`, pitch: `${hotspot.pitch}deg` },
      html: buildHotspotMarkerHtml(hotspot, brandColor),
      anchor: 'center bottom',
      tooltip: hotspot.tooltip || hotspot.label || hotspot.title || undefined,
      data: hotspot,
      visible: true,
    })
  })

  if (editorMode) {
    markersPlugin.getMarkers().forEach((m) => {
      const el = markersPlugin.getMarker(m.id)?.domElement
      if (el) el.style.cursor = 'pointer'
    })
  }
}

export function useViewerClickPlacement(
  viewer: Viewer | null,
  enabled: boolean,
  onPlace: (yaw: number, pitch: number) => void,
): void {
  const handlerRef = useRef<((e: { data: { yaw: number; pitch: number } }) => void) | null>(null)

  useEffect(() => {
    if (!viewer || !enabled) return

    const handler = (e: { data: { yaw: number; pitch: number } }) => {
      const yaw = (e.data.yaw * 180) / Math.PI
      const pitch = (e.data.pitch * 180) / Math.PI
      onPlace(yaw, pitch)
    }

    handlerRef.current = handler
    viewer.addEventListener('click', handler as never)

    return () => {
      if (handlerRef.current) {
        viewer.removeEventListener('click', handlerRef.current as never)
      }
    }
  }, [viewer, enabled, onPlace])
}
