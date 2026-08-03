import { useCallback, useEffect, useRef, useState } from 'react'
import { Viewer } from '@photo-sphere-viewer/core'
import { VirtualTourPlugin } from '@photo-sphere-viewer/virtual-tour-plugin'
import { GyroscopePlugin } from '@photo-sphere-viewer/gyroscope-plugin'
import { StereoPlugin } from '@photo-sphere-viewer/stereo-plugin'
import { AutorotatePlugin } from '@photo-sphere-viewer/autorotate-plugin'
import type { TourData, TourScene, ViewerPosition } from '../types'

export interface TourEngineControls {
  zoomIn: () => void
  zoomOut: () => void
  resetView: () => void
  toggleAutoRotate: () => void
  toggleGyroscope: () => void
  toggleVr: () => void
  takeScreenshot: () => string | null
  goToScene: (sceneId: number) => void
  setAutoRotate: (enabled: boolean) => void
}

export interface UseTourEngineOptions {
  tour: TourData
  initialSceneId?: number | null
  onSceneChange?: (sceneId: number) => void
  enableGyroscope?: boolean
  enableVr?: boolean
  autoRotate?: boolean
  autoRotateSpeed?: number
}

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
}: UseTourEngineOptions) {
  const containerRef = useRef<HTMLDivElement>(null)
  const viewerRef = useRef<Viewer | null>(null)
  const vtRef = useRef<VirtualTourPlugin | null>(null)
  const gyroRef = useRef<GyroscopePlugin | null>(null)
  const stereoRef = useRef<StereoPlugin | null>(null)
  const autorotateRef = useRef<AutorotatePlugin | null>(null)

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

  const buildNodes = useCallback((scenes: TourScene[]) => {
    return scenes.map((scene) => ({
      id: String(scene.id),
      panorama: scene.panorama_url,
      thumbnail: scene.thumbnail_url || undefined,
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
        ?.filter((h) => h.type === 'scene' && h.target_scene_id)
        .map((h) => ({
          nodeId: String(h.target_scene_id),
          position: { yaw: `${h.yaw}deg`, pitch: `${h.pitch}deg` },
          name: h.title || 'ادامه',
        })) ?? [],
    }))
  }, [])

  useEffect(() => {
    if (!containerRef.current || !visibleScenes.length) return

    setLoadError(null)
    setIsLoading(true)
    setLoadProgress(0)

    const startScene = visibleScenes.find((s) => s.id === activeSceneId) || visibleScenes[0]
    const nodes = buildNodes(visibleScenes)

    const plugins: unknown[] = [
      VirtualTourPlugin.withConfig({
        dataMode: 'client',
        positionMode: 'manual',
        nodes,
        startNodeId: String(startScene.id),
        renderMode: '3d',
        transitionOptions: { showLoader: false, speed: '20rpm' },
      }),
      AutorotatePlugin.withConfig({
        autostartDelay: autoRotate ? 0 : undefined,
        autorotateSpeed: `${autoRotateSpeed}rpm`,
        autorotatePitch: '0deg',
      }),
    ]

    if (enableGyroscope && tour.settings?.enable_gyroscope !== false) {
      plugins.push(GyroscopePlugin)
    }
    if (enableVr && tour.settings?.enable_vr !== false) {
      plugins.push(StereoPlugin)
    }

    const viewer = new Viewer({
      container: containerRef.current,
      panorama: startScene.panorama_url,
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

    const vt = viewer.getPlugin(VirtualTourPlugin) as VirtualTourPlugin
    autorotateRef.current = viewer.getPlugin(AutorotatePlugin) as AutorotatePlugin
    gyroRef.current = enableGyroscope ? (viewer.getPlugin(GyroscopePlugin) as GyroscopePlugin) : null
    stereoRef.current = enableVr ? (viewer.getPlugin(StereoPlugin) as StereoPlugin) : null

    const onReady = () => {
      setIsLoading(false)
      setLoadProgress(100)
      if (autoRotate) {
        autorotateRef.current?.start()
        setIsAutoRotating(true)
      }
    }

    const onProgress = (e: { progress: number }) => {
      setLoadProgress(Math.round(e.progress * 100))
    }

    const onError = () => {
      setLoadError('بارگذاری تصویر پانوراما ناموفق بود.')
      setIsLoading(false)
    }

    const onPosition = () => {
      const pos = viewer.getPosition()
      const zoom = viewer.getZoomLevel()
      setPosition({
        yaw: (pos.yaw * 180) / Math.PI,
        pitch: (pos.pitch * 180) / Math.PI,
        zoom,
        fov: zoomToFov(zoom),
      })
    }

    const onNodeChanged = (e: { node: { id: string } }) => {
      const id = Number(e.node.id)
      setActiveSceneId(id)
      onSceneChange?.(id)
      setIsLoading(false)
    }

    viewer.addEventListener('ready', onReady)
    viewer.addEventListener('load-progress', onProgress)
    viewer.addEventListener('panorama-error', onError)
    viewer.addEventListener('position-updated', onPosition)
    viewer.addEventListener('zoom-updated', onPosition)
    vt.addEventListener('node-changed', onNodeChanged)

    vtRef.current = vt
    viewerRef.current = viewer

    return () => {
      viewer.destroy()
      viewerRef.current = null
      vtRef.current = null
      gyroRef.current = null
      stereoRef.current = null
      autorotateRef.current = null
    }
  }, [visibleScenes.map((s) => `${s.id}-${s.panorama_url}`).join(','), tour.title, buildNodes, enableGyroscope, enableVr, autoRotate, autoRotateSpeed])

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
    goToScene: (sceneId: number) => {
      vtRef.current?.setCurrentNode(String(sceneId))
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
