import { useCallback, useEffect, useRef, useState } from 'react'
import type { TourData, TourHotspot, TourScene } from '../types'
import { getCachedImage, pickSceneImageUrl, preloadSceneImages } from '../utils/imageCache'
import { clickToImagePercent } from './smartWalkHotspots'

const MIN_SCALE = 1
const MAX_SCALE = 4
const ZOOM_STEP = 0.15

interface Transform {
  x: number
  y: number
  scale: number
}

interface Options {
  tour: TourData
  initialSceneId?: number | null
  onSceneChange?: (sceneId: number) => void
  editorMode?: boolean
  sceneHotspots?: TourHotspot[]
  isPlacingHotspot?: boolean
  onPlaceHotspot?: (x: number, y: number) => void
  onHotspotSelect?: (hotspot: TourHotspot) => void
}

export function useSmartWalkEngine({
  tour,
  initialSceneId,
  onSceneChange,
  editorMode = false,
  sceneHotspots,
  isPlacingHotspot = false,
  onPlaceHotspot,
  onHotspotSelect,
}: Options) {
  const containerRef = useRef<HTMLDivElement>(null)
  const transformRef = useRef<Transform>({ x: 0, y: 0, scale: 1 })
  const dragRef = useRef<{ active: boolean; startX: number; startY: number; originX: number; originY: number } | null>(null)

  const defaultScene = tour.scenes.find((s) => s.is_default) ?? tour.scenes[0]
  const [activeSceneId, setActiveSceneId] = useState<number | null>(
    initialSceneId ?? defaultScene?.id ?? null,
  )
  const [transform, setTransform] = useState<Transform>({ x: 0, y: 0, scale: 1 })
  const [isLoading, setIsLoading] = useState(true)
  const [loadProgress, setLoadProgress] = useState(0)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [isTransitioning, setIsTransitioning] = useState(false)
  const [isFullscreen, setIsFullscreen] = useState(false)

  const activeScene = tour.scenes.find((s) => s.id === activeSceneId) ?? defaultScene ?? null
  const hotspots = sceneHotspots ?? activeScene?.hotspots ?? []

  const imageUrl = activeScene
    ? pickSceneImageUrl(activeScene.panorama_url, activeScene.image_variants)
    : ''

  const imageWidth = activeScene?.image_variants?.width ?? activeScene?.panorama_width ?? 1920
  const imageHeight = activeScene?.image_variants?.height ?? activeScene?.panorama_height ?? 1080

  const clampTransform = useCallback((t: Transform, scale: number): Transform => {
    const container = containerRef.current
    if (!container) return { ...t, scale }

    const rect = container.getBoundingClientRect()
    const imgW = imageWidth * scale
    const imgH = imageHeight * scale

    const maxX = Math.max(0, (imgW - rect.width) / 2)
    const maxY = Math.max(0, (imgH - rect.height) / 2)

    return {
      scale,
      x: Math.max(-maxX, Math.min(maxX, t.x)),
      y: Math.max(-maxY, Math.min(maxY, t.y)),
    }
  }, [imageWidth, imageHeight])

  const applyTransform = useCallback((t: Transform) => {
    const clamped = clampTransform(t, t.scale)
    transformRef.current = clamped
    setTransform(clamped)
  }, [clampTransform])

  const resetView = useCallback(() => {
    applyTransform({ x: 0, y: 0, scale: 1 })
  }, [applyTransform])

  const zoom = useCallback((delta: number) => {
    const next = Math.max(MIN_SCALE, Math.min(MAX_SCALE, transformRef.current.scale + delta))
    applyTransform({ ...transformRef.current, scale: next })
  }, [applyTransform])

  const goToScene = useCallback((sceneId: number) => {
    if (sceneId === activeSceneId || isTransitioning) return

    const scene = tour.scenes.find((s) => s.id === sceneId)
    if (!scene) return

    setIsTransitioning(true)
    const url = pickSceneImageUrl(scene.panorama_url, scene.image_variants)

    getCachedImage(url)
      .then(() => {
        setActiveSceneId(sceneId)
        resetView()
        onSceneChange?.(sceneId)
      })
      .finally(() => {
        window.setTimeout(() => setIsTransitioning(false), 400)
      })
  }, [activeSceneId, isTransitioning, tour.scenes, onSceneChange, resetView])

  // Load active scene image
  useEffect(() => {
    if (!imageUrl) {
      setIsLoading(false)
      return
    }

    setIsLoading(true)
    setLoadProgress(10)
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
  }, [imageUrl])

  // Preload adjacent scenes
  useEffect(() => {
    const idx = tour.scenes.findIndex((s) => s.id === activeSceneId)
    const neighbors = [tour.scenes[idx - 1], tour.scenes[idx + 1]].filter(Boolean) as TourScene[]
    const urls = neighbors.map((s) => pickSceneImageUrl(s.panorama_url, s.image_variants))
    preloadSceneImages(urls)
  }, [activeSceneId, tour.scenes])

  // Pointer drag pan
  useEffect(() => {
    const el = containerRef.current
    if (!el) return

    const onPointerDown = (e: PointerEvent) => {
      if (isPlacingHotspot) return
      dragRef.current = {
        active: true,
        startX: e.clientX,
        startY: e.clientY,
        originX: transformRef.current.x,
        originY: transformRef.current.y,
      }
      el.setPointerCapture(e.pointerId)
    }

    const onPointerMove = (e: PointerEvent) => {
      if (!dragRef.current?.active) return
      const dx = e.clientX - dragRef.current.startX
      const dy = e.clientY - dragRef.current.startY
      applyTransform({
        x: dragRef.current.originX + dx,
        y: dragRef.current.originY + dy,
        scale: transformRef.current.scale,
      })
    }

    const onPointerUp = (e: PointerEvent) => {
      if (dragRef.current?.active) {
        dragRef.current = null
        el.releasePointerCapture(e.pointerId)
      }
    }

    el.addEventListener('pointerdown', onPointerDown)
    el.addEventListener('pointermove', onPointerMove)
    el.addEventListener('pointerup', onPointerUp)
    el.addEventListener('pointercancel', onPointerUp)

    return () => {
      el.removeEventListener('pointerdown', onPointerDown)
      el.removeEventListener('pointermove', onPointerMove)
      el.removeEventListener('pointerup', onPointerUp)
      el.removeEventListener('pointercancel', onPointerUp)
    }
  }, [applyTransform, isPlacingHotspot])

  const handleWheel = useCallback((e: React.WheelEvent) => {
    e.preventDefault()
    const delta = e.deltaY > 0 ? -ZOOM_STEP : ZOOM_STEP
    zoom(delta)
  }, [zoom])

  const handleClick = useCallback((e: React.MouseEvent) => {
    if (!isPlacingHotspot || !containerRef.current || !onPlaceHotspot) return
    const { x, y } = clickToImagePercent(
      e.clientX,
      e.clientY,
      containerRef.current,
      transformRef.current,
      imageWidth,
      imageHeight,
    )
    onPlaceHotspot(x, y)
  }, [isPlacingHotspot, onPlaceHotspot, imageWidth, imageHeight])

  const toggleFullscreen = useCallback(() => {
    const el = containerRef.current?.parentElement
    if (!el) return
    if (!document.fullscreenElement) {
      el.requestFullscreen?.().then(() => setIsFullscreen(true))
    } else {
      document.exitFullscreen?.().then(() => setIsFullscreen(false))
    }
  }, [])

  useEffect(() => {
    const onFs = () => setIsFullscreen(!!document.fullscreenElement)
    document.addEventListener('fullscreenchange', onFs)
    return () => document.removeEventListener('fullscreenchange', onFs)
  }, [])

  return {
    containerRef,
    activeSceneId,
    activeScene,
    hotspots,
    transform,
    imageUrl,
    imageWidth,
    imageHeight,
    isLoading,
    loadProgress,
    loadError,
    isTransitioning,
    isFullscreen,
    goToScene,
    zoom,
    resetView,
    toggleFullscreen,
    handleWheel,
    handleClick,
    editorMode,
    onHotspotSelect,
  }
}
