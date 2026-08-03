import { useCallback, useEffect, useRef, useState } from 'react'
import { Viewer } from '@photo-sphere-viewer/core'
import { VirtualTourPlugin } from '@photo-sphere-viewer/virtual-tour-plugin'
import { GyroscopePlugin } from '@photo-sphere-viewer/gyroscope-plugin'
import { StereoPlugin } from '@photo-sphere-viewer/stereo-plugin'
import { AutorotatePlugin } from '@photo-sphere-viewer/autorotate-plugin'
import { MarkersPlugin } from '@photo-sphere-viewer/markers-plugin'
import type { TourData, TourScene, TourHotspot, ViewerPosition } from '../types'
import { syncHotspotMarkers } from '../hotspots/useHotspotMarkers'
import { resolvePanoramaUrl } from '../utils/panoramaUrl'

export interface TourEngineControls {
  zoomIn: () => void
  zoomOut: () => void
  resetView: () => void
  toggleAutoRotate: () => void
  toggleGyroscope: () => void
  toggleVr: () => void
  takeScreenshot: () => string | null
  goToScene: (sceneId: number, options?: SceneTransitionOptions) => void
  setAutoRotate: (enabled: boolean) => void
}

export interface SceneTransitionOptions {
  effect?: 'fade' | 'crossfade' | 'none'
  speed?: string | number
  yaw?: number
  pitch?: number
}

export interface UseTourEngineOptions {
  tour: TourData
  initialSceneId?: number | null
  onSceneChange?: (sceneId: number) => void
  enableGyroscope?: boolean
  enableVr?: boolean
  autoRotate?: boolean
  autoRotateSpeed?: number
  /** Hotspots for the active scene (editor or viewer) */
  sceneHotspots?: TourHotspot[]
  editorMode?: boolean
  isPlacingHotspot?: boolean
  onPlaceHotspot?: (yaw: number, pitch: number) => void
  onHotspotSelect?: (hotspot: TourHotspot) => void
  onHotspotActivate?: (hotspot: TourHotspot) => void
  onHotspotMove?: (hotspot: TourHotspot, yaw: number, pitch: number) => void
  isRepositioningHotspot?: boolean
  repositionHotspot?: TourHotspot | null
}

const LOAD_TIMEOUT_MS = 90_000

function zoomToFov(zoom: number, minFov = 30, maxFov = 90): number {
  return maxFov - (zoom / 100) * (maxFov - minFov)
}

export function useTourEngine({
  tour,
  initialSceneId,
  onSceneChange,
  enableGyroscope = true,
  enableVr = true,
  autoRotate = false,
  autoRotateSpeed = 0.5,
  sceneHotspots = [],
  editorMode = false,
  isPlacingHotspot = false,
  onPlaceHotspot,
  onHotspotSelect,
  onHotspotActivate,
  onHotspotMove,
  isRepositioningHotspot = false,
  repositionHotspot = null,
}: UseTourEngineOptions) {
  const containerRef = useRef<HTMLDivElement>(null)
  const viewerRef = useRef<Viewer | null>(null)
  const vtRef = useRef<VirtualTourPlugin | null>(null)
  const markersRef = useRef<MarkersPlugin | null>(null)
  const gyroRef = useRef<GyroscopePlugin | null>(null)
  const stereoRef = useRef<StereoPlugin | null>(null)
  const autorotateRef = useRef<AutorotatePlugin | null>(null)
  const positionRafRef = useRef<number | null>(null)

  const [activeSceneId, setActiveSceneId] = useState<number | null>(
    initialSceneId ?? tour.scenes.find((s) => s.is_default)?.id ?? tour.scenes[0]?.id ?? null,
  )
  const [position, setPosition] = useState<ViewerPosition>({ yaw: 0, pitch: 0, zoom: 50, fov: 75 })
  const [isLoading, setIsLoading] = useState(true)
  const [loadProgress, setLoadProgress] = useState(0)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [isAutoRotating, setIsAutoRotating] = useState(autoRotate)
  const [isGyroActive, setIsGyroActive] = useState(false)
  const [isVrActive, setIsVrActive] = useState(false)
  const [isFullscreen, setIsFullscreen] = useState(false)

  const visibleScenes = tour.scenes.filter((s) => s.is_visible !== false)
  const brandColor = tour.settings?.brand_color || '#2dd4bf'

  const buildNodes = useCallback((scenes: TourScene[]) => {
    return scenes.map((scene) => ({
      id: String(scene.id),
      panorama: resolvePanoramaUrl(scene.panorama_url),
      thumbnail: scene.thumbnail_url ? resolvePanoramaUrl(scene.thumbnail_url) : undefined,
      name: scene.name,
      panoData: scene.panorama_width && scene.panorama_height
        ? {
            fullWidth: scene.panorama_width,
            fullHeight: scene.panorama_height,
            croppedWidth: scene.panorama_width,
            croppedHeight: scene.panorama_height,
            croppedX: 0,
            croppedY: 0,
          }
        : undefined,
      links: scene.hotspots
        ?.filter((h) => h.type === 'scene' && h.target_scene_id && !editorMode)
        .map((h) => ({
          nodeId: String(h.target_scene_id),
          position: { yaw: `${h.yaw}deg`, pitch: `${h.pitch}deg` },
          name: h.title || 'ادامه',
        })) ?? [],
    }))
  }, [editorMode])

  useEffect(() => {
    if (!containerRef.current || !visibleScenes.length) return

    setLoadError(null)
    setIsLoading(true)
    setLoadProgress(0)

    const startScene = visibleScenes.find((s) => s.id === activeSceneId) || visibleScenes[0]
    const nodes = buildNodes(visibleScenes)
    const startPanorama = resolvePanoramaUrl(startScene.panorama_url)

    if (!startPanorama) {
      setLoadError('آدرس تصویر پانوراما نامعتبر است.')
      setIsLoading(false)
      return
    }

    let loadTimeoutId: ReturnType<typeof setTimeout> | null = null
    let disposed = false

    const plugins: unknown[] = [
      VirtualTourPlugin.withConfig({
        dataMode: 'client',
        positionMode: 'manual',
        nodes,
        startNodeId: String(startScene.id),
        renderMode: '2d',
        transitionOptions: { showLoader: false, speed: '800ms', effect: 'fade' },
      }),
      AutorotatePlugin.withConfig({
        autostartDelay: autoRotate ? 0 : undefined,
        autorotateSpeed: `${autoRotateSpeed}rpm`,
        autorotatePitch: '0deg',
      }),
      MarkersPlugin,
    ]

    if (enableGyroscope && tour.settings?.enable_gyroscope !== false) {
      plugins.push(GyroscopePlugin)
    }
    if (enableVr && tour.settings?.enable_vr !== false) {
      plugins.push(StereoPlugin)
    }

    const viewer = new Viewer({
      container: containerRef.current,
      caption: tour.title,
      navbar: false,
      defaultYaw: `${startScene.default_yaw ?? 0}deg`,
      defaultPitch: `${startScene.default_pitch ?? 0}deg`,
      defaultZoomLvl: 50,
      minFov: 30,
      maxFov: 90,
      touchmoveTwoFingers: true,
      mousewheelCtrlKey: false,
      keyboard: 'always',
      moveSpeed: 1.2,
      zoomSpeed: 1.2,
      loadingTxt: '',
      plugins: plugins as never[],
    })

    const markReady = () => {
      if (disposed) return
      if (loadTimeoutId) {
        clearTimeout(loadTimeoutId)
        loadTimeoutId = null
      }
      setIsLoading(false)
      setLoadProgress(100)
    }

    const failLoad = (message: string) => {
      if (disposed) return
      if (loadTimeoutId) {
        clearTimeout(loadTimeoutId)
        loadTimeoutId = null
      }
      setLoadError(message)
      setIsLoading(false)
    }

    const vt = viewer.getPlugin(VirtualTourPlugin) as VirtualTourPlugin
    markersRef.current = viewer.getPlugin(MarkersPlugin) as MarkersPlugin
    autorotateRef.current = viewer.getPlugin(AutorotatePlugin) as AutorotatePlugin
    gyroRef.current = enableGyroscope ? (viewer.getPlugin(GyroscopePlugin) as GyroscopePlugin) : null
    stereoRef.current = enableVr ? (viewer.getPlugin(StereoPlugin) as StereoPlugin) : null

    const onReady = () => {
      markReady()
      if (autoRotate) {
        autorotateRef.current?.start()
        setIsAutoRotating(true)
      }
    }

    const onPanoramaLoaded = () => {
      markReady()
    }

    const onProgress = (e: { progress: number }) => {
      if (disposed) return
      setLoadProgress(Math.max(1, Math.round(e.progress * 100)))
    }

    const onError = () => {
      failLoad('بارگذاری تصویر پانوراما ناموفق بود. لینک تصویر را بررسی کنید.')
    }

    const onPosition = () => {
      if (positionRafRef.current !== null) return
      positionRafRef.current = requestAnimationFrame(() => {
        positionRafRef.current = null
        const pos = viewer.getPosition()
        const zoom = viewer.getZoomLevel()
        setPosition({
          yaw: (pos.yaw * 180) / Math.PI,
          pitch: (pos.pitch * 180) / Math.PI,
          zoom,
          fov: zoomToFov(zoom),
        })
      })
    }

    const onNodeChanged = (e: { node: { id: string } }) => {
      markReady()
      const id = Number(e.node.id)
      setActiveSceneId(id)
      onSceneChange?.(id)
    }

    const onClick = (e: { data: { yaw: number; pitch: number } }) => {
      const yaw = (e.data.yaw * 180) / Math.PI
      const pitch = (e.data.pitch * 180) / Math.PI
      if (isRepositioningHotspot && onHotspotMove && repositionHotspot) {
        onHotspotMove(repositionHotspot, yaw, pitch)
        return
      }
      if (!isPlacingHotspot || !onPlaceHotspot) return
      onPlaceHotspot(yaw, pitch)
    }

    const markers = markersRef.current
    const onSelectMarker = (e: { marker: { data?: TourHotspot } }) => {
      const hotspot = e.marker?.data
      if (!hotspot) return
      if (editorMode) {
        onHotspotSelect?.(hotspot)
      } else {
        onHotspotActivate?.(hotspot)
      }
    }

    viewer.addEventListener('ready', onReady)
    viewer.addEventListener('panorama-loaded', onPanoramaLoaded)
    viewer.addEventListener('load-progress', onProgress as never)
    viewer.addEventListener('panorama-error', onError)
    viewer.addEventListener('position-updated', onPosition)
    viewer.addEventListener('zoom-updated', onPosition)
    viewer.addEventListener('click', onClick as never)
    vt.addEventListener('node-changed', onNodeChanged)
    markers?.addEventListener('select-marker', onSelectMarker as never)

    loadTimeoutId = setTimeout(() => {
      failLoad('بارگذاری تصویر پانوراما بیش از حد طول کشید. اتصال اینترنت یا فایل تصویر را بررسی کنید.')
    }, LOAD_TIMEOUT_MS)

    vtRef.current = vt
    viewerRef.current = viewer

    return () => {
      disposed = true
      if (loadTimeoutId) clearTimeout(loadTimeoutId)
      if (positionRafRef.current !== null) {
        cancelAnimationFrame(positionRafRef.current)
        positionRafRef.current = null
      }
      viewer.removeEventListener('ready', onReady)
      viewer.removeEventListener('panorama-loaded', onPanoramaLoaded)
      viewer.removeEventListener('load-progress', onProgress as never)
      viewer.removeEventListener('panorama-error', onError)
      viewer.removeEventListener('position-updated', onPosition)
      viewer.removeEventListener('zoom-updated', onPosition)
      viewer.removeEventListener('click', onClick as never)
      vt.removeEventListener('node-changed', onNodeChanged)
      markers?.removeEventListener('select-marker', onSelectMarker as never)
      viewer.destroy()
      viewerRef.current = null
      vtRef.current = null
      markersRef.current = null
      gyroRef.current = null
      stereoRef.current = null
      autorotateRef.current = null
    }
  }, [visibleScenes.map((s) => `${s.id}-${s.panorama_url}`).join(','), tour.title, buildNodes, enableGyroscope, enableVr, autoRotate, autoRotateSpeed, editorMode, isPlacingHotspot, isRepositioningHotspot])

  // Sync markers when hotspots change
  useEffect(() => {
    if (!markersRef.current || isLoading) return
    syncHotspotMarkers(markersRef.current, sceneHotspots, brandColor, editorMode)
  }, [sceneHotspots, brandColor, editorMode, isLoading, activeSceneId])

  useEffect(() => {
    const onFsChange = () => setIsFullscreen(!!document.fullscreenElement)
    document.addEventListener('fullscreenchange', onFsChange)
    return () => document.removeEventListener('fullscreenchange', onFsChange)
  }, [])

  const controls: TourEngineControls = {
    zoomIn: () => viewerRef.current?.zoomIn(10),
    zoomOut: () => viewerRef.current?.zoomOut(10),
    resetView: () => {
      const scene = visibleScenes.find((s) => s.id === activeSceneId)
      viewerRef.current?.animate({
        yaw: `${scene?.default_yaw ?? 0}deg`,
        pitch: `${scene?.default_pitch ?? 0}deg`,
        zoom: 50,
        speed: '4rpm',
      })
    },
    toggleAutoRotate: () => {
      if (!autorotateRef.current) return
      if (isAutoRotating) {
        autorotateRef.current.stop()
        setIsAutoRotating(false)
      } else {
        autorotateRef.current.start()
        setIsAutoRotating(true)
      }
    },
    setAutoRotate: (enabled: boolean) => {
      if (!autorotateRef.current) return
      if (enabled) {
        autorotateRef.current.start()
      } else {
        autorotateRef.current.stop()
      }
      setIsAutoRotating(enabled)
    },
    toggleGyroscope: () => {
      if (!gyroRef.current) return
      if (isGyroActive) {
        gyroRef.current.stop()
        setIsGyroActive(false)
      } else {
        gyroRef.current.start()
        setIsGyroActive(true)
      }
    },
    toggleVr: () => {
      if (!stereoRef.current) return
      if (isVrActive) {
        stereoRef.current.stop()
        setIsVrActive(false)
      } else {
        stereoRef.current.start()
        setIsVrActive(true)
      }
    },
    takeScreenshot: () => {
      try {
        const canvas = containerRef.current?.querySelector('canvas')
        return canvas?.toDataURL('image/jpeg', 0.92) ?? null
      } catch {
        return null
      }
    },
    goToScene: (sceneId: number, options?: SceneTransitionOptions) => {
      const targetScene = visibleScenes.find((s) => s.id === sceneId)
      const effect = options?.effect || targetScene?.transition_effect || 'fade'
      const speed = options?.speed ?? `${options?.effect === 'none' ? 0 : 800}ms`
      vtRef.current?.setCurrentNode(String(sceneId), {
        effect: effect === 'crossfade' ? 'fade' : effect,
        speed,
        showLoader: false,
        rotation: true,
      })
      const viewer = viewerRef.current
      if (viewer && (options?.yaw !== undefined || options?.pitch !== undefined)) {
        setTimeout(() => {
          viewer.animate({
            yaw: `${options?.yaw ?? targetScene?.default_yaw ?? 0}deg`,
            pitch: `${options?.pitch ?? targetScene?.default_pitch ?? 0}deg`,
            speed: '6rpm',
          })
        }, 400)
      }
    },
  }

  const toggleFullscreen = () => {
    viewerRef.current?.toggleFullscreen()
  }

  return {
    containerRef,
    activeSceneId,
    activeScene: visibleScenes.find((s) => s.id === activeSceneId) ?? null,
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
  }
}
