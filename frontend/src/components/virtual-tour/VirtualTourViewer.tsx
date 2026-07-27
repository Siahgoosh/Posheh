import { useEffect, useRef, useState } from 'react'
import { Viewer } from '@photo-sphere-viewer/core'
import { MarkersPlugin } from '@photo-sphere-viewer/markers-plugin'
import { VirtualTourPlugin } from '@photo-sphere-viewer/virtual-tour-plugin'
import { GyroscopePlugin } from '@photo-sphere-viewer/gyroscope-plugin'
import '@photo-sphere-viewer/core/index.css'
import '@photo-sphere-viewer/markers-plugin/index.css'
import '@photo-sphere-viewer/virtual-tour-plugin/index.css'

export interface TourScene {
  id: number
  name: string
  panorama_url: string
  default_yaw?: number
  default_pitch?: number
  floor_plan_x?: number | null
  floor_plan_y?: number | null
  hotspots: TourHotspot[]
}

export interface TourHotspot {
  id: number
  type: 'scene' | 'info' | 'link' | 'video'
  target_scene_id?: number | null
  yaw: number
  pitch: number
  title?: string | null
  content?: string | null
  link_url?: string | null
  icon?: string
}

export interface TourData {
  title: string
  description?: string
  settings?: {
    brand_color?: string
    enable_gyroscope?: boolean
    enable_vr?: boolean
    show_floor_plan?: boolean
    map_lat?: number
    map_lng?: number
  }
  scenes: TourScene[]
}

interface Props {
  tour: TourData
  onSceneChange?: (sceneId: number) => void
  className?: string
}

export function VirtualTourViewer({ tour, onSceneChange, className }: Props) {
  const containerRef = useRef<HTMLDivElement>(null)
  const viewerRef = useRef<Viewer | null>(null)
  const vtRef = useRef<VirtualTourPlugin | null>(null)
  const [activeScene, setActiveScene] = useState<number | null>(tour.scenes[0]?.id ?? null)
  const [infoPopup, setInfoPopup] = useState<TourHotspot | null>(null)

  useEffect(() => {
    if (!containerRef.current || !tour.scenes.length) return

    const nodes = tour.scenes.map((scene) => ({
      id: String(scene.id),
      panorama: scene.panorama_url,
      name: scene.name,
      markers: scene.hotspots
        .filter((h) => h.type === 'info' || h.type === 'link')
        .map((h) => ({
          id: `info-${h.id}`,
          position: { yaw: `${h.yaw}deg`, pitch: `${h.pitch}deg` },
          html: `<div class="vt-info-marker" title="${h.title || ''}">ℹ️</div>`,
          tooltip: h.title || 'اطلاعات',
          data: h,
        })),
      links: scene.hotspots
        .filter((h) => h.type === 'scene' && h.target_scene_id)
        .map((h) => ({
          nodeId: String(h.target_scene_id),
          position: { yaw: `${h.yaw}deg`, pitch: `${h.pitch}deg` },
          name: h.title || 'ادامه',
        })),
    }))

    const plugins: unknown[] = [
      VirtualTourPlugin.withConfig({
        dataMode: 'client',
        positionMode: 'manual',
        nodes,
        startNodeId: String(tour.scenes[0].id),
        renderMode: '3d',
      }),
      MarkersPlugin,
    ]

    if (tour.settings?.enable_gyroscope !== false) {
      plugins.push(GyroscopePlugin)
    }

    const viewer = new Viewer({
      container: containerRef.current,
      navbar: ['zoom', 'move', 'fullscreen', 'caption'],
      defaultYaw: `${tour.scenes[0].default_yaw ?? 0}deg`,
      defaultPitch: `${tour.scenes[0].default_pitch ?? 0}deg`,
      touchmoveTwoFingers: true,
      mousewheelCtrlKey: false,
      plugins: plugins as never[],
    })

    const vt = viewer.getPlugin(VirtualTourPlugin) as VirtualTourPlugin
    const markers = viewer.getPlugin(MarkersPlugin) as MarkersPlugin

    vt.addEventListener('node-changed', (e: { node: { id: string } }) => {
      const id = Number(e.node.id)
      setActiveScene(id)
      onSceneChange?.(id)
      setInfoPopup(null)
    })

    markers.addEventListener('select-marker', (e: { marker: { data?: TourHotspot } }) => {
      const hotspot = e.marker?.data
      if (!hotspot) return
      if (hotspot.type === 'link' && hotspot.link_url) {
        window.open(hotspot.link_url, '_blank')
        return
      }
      setInfoPopup(hotspot)
    })

    vtRef.current = vt
    viewerRef.current = viewer

    return () => {
      vtRef.current = null
      viewer.destroy()
      viewerRef.current = null
    }
  }, [tour, onSceneChange])

  const currentScene = tour.scenes.find((s) => s.id === activeScene)

  return (
    <div className={`relative w-full h-full min-h-[60vh] bg-black ${className || ''}`}>
      <div ref={containerRef} className="absolute inset-0" />

      {tour.settings?.show_floor_plan && currentScene?.floor_plan_x != null && (
        <div className="absolute bottom-4 left-4 w-32 h-32 rounded-xl bg-black/60 border border-white/20 backdrop-blur p-2 z-10">
          <p className="text-[10px] text-white/70 mb-1 text-center">پلان</p>
          <div className="relative w-full h-[calc(100%-16px)] bg-white/10 rounded">
            {tour.scenes.map((s) => (
              <button
                key={s.id}
                type="button"
                onClick={() => vtRef.current?.setCurrentNode(String(s.id))}
                className={`absolute w-3 h-3 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 ${
                  s.id === activeScene ? 'bg-primary border-white scale-125' : 'bg-white/50 border-white/50'
                }`}
                style={{ left: `${s.floor_plan_x ?? 50}%`, top: `${s.floor_plan_y ?? 50}%` }}
                title={s.name}
              />
            ))}
          </div>
        </div>
      )}

      {currentScene && (
        <div className="absolute top-4 right-4 z-10 px-4 py-2 rounded-full bg-black/50 text-white text-sm backdrop-blur">
          {currentScene.name}
        </div>
      )}

      {infoPopup && (
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-20 max-w-sm w-[90%]">
          <div className="bg-white dark:bg-card rounded-2xl shadow-2xl p-5 border border-card-border">
            <h3 className="font-bold text-lg mb-2">{infoPopup.title}</h3>
            {infoPopup.content && <p className="text-sm text-muted leading-relaxed">{infoPopup.content}</p>}
            <button
              type="button"
              className="mt-4 text-sm text-primary"
              onClick={() => setInfoPopup(null)}
            >
              بستن
            </button>
          </div>
        </div>
      )}

      <style>{`
        .vt-info-marker {
          width: 36px; height: 36px; border-radius: 50%;
          background: rgba(99,102,241,0.9); display: flex; align-items: center; justify-content: center;
          font-size: 16px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.4);
          border: 2px solid white;
        }
        .psv-navbar { direction: ltr; }
      `}</style>
    </div>
  )
}
