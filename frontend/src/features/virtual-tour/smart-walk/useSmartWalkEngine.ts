import { useCallback, useEffect, useRef, useState } from 'react'
import type { TourData, TourHotspot, TourScene } from '../types'
import { getCachedImage, pickSceneImageUrlForZoom, preloadSceneImages } from '../utils/imageCache'
import { clickToImagePercent } from './smartWalkHotspots'
import { hotspotToPanTarget } from './camera/cameraMath'
import { useSmartWalkCamera } from './camera/useSmartWalkCamera'
import {
  animateSceneLanding,
  animateSceneTransition,
  animateWalkToHotspot,
  type TransitionEffect,
} from './transitions/transitionEngine'

interface Options {
  tour: TourData
  initialSceneId?: number | null
  onSceneChange?: (sceneId: number) => void
  editorMode?: boolean
  sceneHotspots?: TourHotspot[]
  isPlacingHotspot?: boolean
  onPlaceHotspot?: (x: number, y: number) => void
  cameraDisabled?: boolean
}

export function useSmartWalkEngine({
  tour,
  initialSceneId,
  onSceneChange,
  editorMode = false,
  sceneHotspots,
  isPlacingHotspot = false,
  onPlaceHotspot,
  cameraDisabled = false,
}: Options) {
  const defaultScene = tour.scenes.find((s) => s.is_default) ?? tour.scenes[0]
  const [activeSceneId, setActiveSceneId] = useState<number | null>(
    initialSceneId ?? defaultScene?.id ?? null,
  )
  const [visitedSceneIds, setVisitedSceneIds] = useState<number[]>(() =>
    defaultScene ? [defaultScene.id] : [],
  )
  const [isLoading, setIsLoading] = useState(true)
  const [loadProgress, setLoadProgress] = useState(0)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [isTransitioning, setIsTransitioning] = useState(false)
  const [transitionFrame, setTransitionFrame] = useState({
    overlayOpacity: 0,
    blurPx: 0,
    outgoingOpacity: 1,
    incomingOpacity: 1,
  })
  const [pendingSceneUrl, setPendingSceneUrl] = useState<string | null>(null)
  const pendingSceneIdRef = useRef<number | null>(null)
  const isTransitioningRef = useRef(false)

  const activeScene = tour.scenes.find((s) => s.id === activeSceneId) ?? defaultScene ?? null
  const hotspots = sceneHotspots ?? activeScene?.hotspots ?? []

  const imageWidth = activeScene?.image_variants?.width ?? activeScene?.panorama_width ?? 1920
  const imageHeight = activeScene?.image_variants?.height ?? activeScene?.panorama_height ?? 1080

  const camera = useSmartWalkCamera({
    imageWidth,
    imageHeight,
    disabled: cameraDisabled || isPlacingHotspot || isTransitioning,
  })

  const imageUrl = activeScene
    ? pickSceneImageUrlForZoom(
        activeScene.panorama_url,
        activeScene.image_variants,
        camera.camera.scale,
      )
    : ''

  const displayUrl = pendingSceneUrl ?? imageUrl

  // Upgrade image resolution when zooming deep
  useEffect(() => {
    if (!activeScene || isTransitioning) return
    const hi = pickSceneImageUrlForZoom(
      activeScene.panorama_url,
      activeScene.image_variants,
      camera.camera.scale,
    )
    if (hi !== imageUrl) getCachedImage(hi).catch(() => {})
  }, [camera.camera.scale, activeScene, imageUrl, isTransitioning])

  // Load scene image
  useEffect(() => {
    if (!imageUrl) {
      setIsLoading(false)
      return
    }
    setIsLoading(true)
    setLoadProgress(15)
    setLoadError(null)
    getCachedImage(imageUrl)
      .then(() => {
        setLoadProgress(100)
        setIsLoading(false)
      })
      .catch(() => {
        setLoadError('بارگذاری تصویر ناموفق بود.')
        setIsLoading(false)
      })
  }, [imageUrl, activeSceneId])

  // Preload neighbors
  useEffect(() => {
    const idx = tour.scenes.findIndex((s) => s.id === activeSceneId)
    const neighbors = [tour.scenes[idx - 1], tour.scenes[idx + 1]].filter(Boolean) as TourScene[]
    preloadSceneImages(
      neighbors.map((s) => pickSceneImageUrlForZoom(s.panorama_url, s.image_variants, 2)),
    )
  }, [activeSceneId, tour.scenes])

  const markVisited = useCallback((id: number) => {
    setVisitedSceneIds((prev) => (prev.includes(id) ? prev : [...prev, id]))
  }, [])

  const goToScene = useCallback(async (
    sceneId: number,
    options?: {
      walkFromHotspot?: TourHotspot
      effect?: TransitionEffect
      duration?: number
    },
  ) => {
    if (sceneId === activeSceneId || isTransitioningRef.current) return
    const scene = tour.scenes.find((s) => s.id === sceneId)
    if (!scene) return

    const effect = options?.effect ?? (options?.walkFromHotspot ? 'fade' : 'crossfade')
    const duration = options?.duration ?? (options?.walkFromHotspot ? 900 : 700)
    const nextUrl = pickSceneImageUrlForZoom(scene.panorama_url, scene.image_variants, 1)

    isTransitioningRef.current = true
    setIsTransitioning(true)
    camera.stopMomentum()

    const el = camera.containerRef.current
    const viewW = el?.getBoundingClientRect().width ?? 800
    const viewH = el?.getBoundingClientRect().height ?? 600

    try {
      if (options?.walkFromHotspot) {
        const px = options.walkFromHotspot.position_x ?? 50
        const py = options.walkFromHotspot.position_y ?? 50
        const panTarget = hotspotToPanTarget(
          px,
          py,
          imageWidth,
          imageHeight,
          viewW,
          viewH,
          camera.cameraRef.current.scale,
        )
        await animateWalkToHotspot(
          camera.cameraRef.current,
          panTarget,
          imageWidth,
          imageHeight,
          viewW,
          viewH,
          (frame) => {
            camera.setCameraDirect(frame.camera)
            setTransitionFrame({
              overlayOpacity: frame.overlayOpacity,
              blurPx: frame.blurPx,
              outgoingOpacity: frame.outgoingOpacity,
              incomingOpacity: frame.incomingOpacity,
            })
          },
          { duration },
        )
      } else {
        await animateSceneTransition(
          effect,
          duration,
          (frame) => {
            if (frame.camera) camera.setCameraDirect(frame.camera)
            setTransitionFrame({
              overlayOpacity: frame.overlayOpacity,
              blurPx: frame.blurPx,
              outgoingOpacity: frame.outgoingOpacity,
              incomingOpacity: frame.incomingOpacity,
            })
          },
          camera.cameraRef.current,
        )
      }

      await getCachedImage(nextUrl)
      pendingSceneIdRef.current = sceneId
      setPendingSceneUrl(nextUrl)

      setActiveSceneId(sceneId)
      onSceneChange?.(sceneId)
      markVisited(sceneId)

      const nextW = scene.image_variants?.width ?? scene.panorama_width ?? 1920
      const nextH = scene.image_variants?.height ?? scene.panorama_height ?? 1080

      await animateSceneLanding(
        camera.cameraRef.current,
        nextW,
        nextH,
        viewW,
        viewH,
        (frame) => {
          camera.setCameraDirect(frame.camera)
          setTransitionFrame({
            overlayOpacity: frame.overlayOpacity,
            blurPx: frame.blurPx,
            outgoingOpacity: frame.outgoingOpacity,
            incomingOpacity: frame.incomingOpacity,
          })
        },
        { duration: 550 },
      )
    } finally {
      setPendingSceneUrl(null)
      pendingSceneIdRef.current = null
      setTransitionFrame({ overlayOpacity: 0, blurPx: 0, outgoingOpacity: 1, incomingOpacity: 1 })
      isTransitioningRef.current = false
      setIsTransitioning(false)
    }
  }, [
    activeSceneId,
    tour.scenes,
    camera,
    imageWidth,
    imageHeight,
    onSceneChange,
    markVisited,
  ])

  const handleClick = useCallback((e: React.MouseEvent) => {
    if (!isPlacingHotspot || !camera.containerRef.current || !onPlaceHotspot) return
    const { x, y } = clickToImagePercent(
      e.clientX,
      e.clientY,
      camera.containerRef.current,
      camera.cameraRef.current,
      imageWidth,
      imageHeight,
    )
    onPlaceHotspot(x, y)
  }, [isPlacingHotspot, onPlaceHotspot, camera, imageWidth, imageHeight])

  const [isFullscreen, setIsFullscreen] = useState(false)
  const toggleFullscreen = useCallback(() => {
    const el = camera.containerRef.current?.parentElement?.parentElement
    if (!el) return
    if (!document.fullscreenElement) el.requestFullscreen?.().then(() => setIsFullscreen(true))
    else document.exitFullscreen?.().then(() => setIsFullscreen(false))
  }, [camera.containerRef])

  useEffect(() => {
    const onFs = () => setIsFullscreen(!!document.fullscreenElement)
    document.addEventListener('fullscreenchange', onFs)
    return () => document.removeEventListener('fullscreenchange', onFs)
  }, [])

  return {
    ...camera,
    activeSceneId,
    activeScene,
    hotspots,
    imageUrl: displayUrl,
    imageWidth,
    imageHeight,
    isLoading,
    loadProgress,
    loadError,
    isTransitioning,
    transitionFrame,
    visitedSceneIds,
    goToScene,
    handleClick,
    toggleFullscreen,
    isFullscreen,
    editorMode,
  }
}
