import { useCallback, useEffect, useRef, useState } from 'react'
import {
  type CameraState,
  clampPan,
  clampScale,
  cameraTransformCss,
  computeFitScale,
  computeInitialSmartWalkScale,
  DEFAULT_FRICTION,
  MAX_ZOOM_FACTOR,
  MOMENTUM_THRESHOLD,
  zoomAtPoint,
  zoomPercent,
} from './cameraMath'

interface Options {
  imageWidth: number
  imageHeight: number
  disabled?: boolean
  onTransformChange?: (camera: CameraState) => void
}

export function useSmartWalkCamera({
  imageWidth,
  imageHeight,
  disabled = false,
  onTransformChange,
}: Options) {
  const containerRef = useRef<HTMLDivElement | null>(null)
  const [mountNode, setMountNode] = useState<HTMLDivElement | null>(null)
  const cameraRef = useRef<CameraState>({ x: 0, y: 0, scale: 1 })
  const fitScaleRef = useRef(1)
  const velocityRef = useRef({ x: 0, y: 0 })
  const rafRef = useRef<number | null>(null)
  const dragRef = useRef<{
    active: boolean
    pointerId: number
    startX: number
    startY: number
    originX: number
    originY: number
    lastX: number
    lastY: number
    lastTime: number
  } | null>(null)
  const pinchRef = useRef<{
    active: boolean
    startDist: number
    startScale: number
    focalX: number
    focalY: number
  } | null>(null)
  const pointersRef = useRef(new Map<number, { x: number; y: number }>())
  const lastTapRef = useRef<{ time: number; x: number; y: number } | null>(null)
  const dragMovedRef = useRef(false)

  const [camera, setCamera] = useState<CameraState>({ x: 0, y: 0, scale: 1 })
  const [fitScale, setFitScale] = useState(1)
  const [isDragging, setIsDragging] = useState(false)

  const setContainerRef = useCallback((node: HTMLDivElement | null) => {
    containerRef.current = node
    setMountNode(node)
  }, [])

  const getViewSize = useCallback(() => {
    const el = containerRef.current
    if (!el) return { w: 1, h: 1 }
    const rect = el.getBoundingClientRect()
    return { w: Math.max(rect.width, 1), h: Math.max(rect.height, 1) }
  }, [])

  const getFitScale = useCallback(() => {
    const { w, h } = getViewSize()
    return computeFitScale(imageWidth, imageHeight, w, h)
  }, [getViewSize, imageWidth, imageHeight])

  const applyCamera = useCallback((next: CameraState, skipClamp = false) => {
    const { w, h } = getViewSize()
    const fit = getFitScale()
    fitScaleRef.current = fit
    setFitScale(fit)
    const clamped = skipClamp
      ? { ...next, scale: clampScale(next.scale, fit) }
      : clampPan({ ...next, scale: clampScale(next.scale, fit) }, imageWidth, imageHeight, w, h)
    cameraRef.current = clamped
    setCamera(clamped)
    onTransformChange?.(clamped)
  }, [getViewSize, getFitScale, imageWidth, imageHeight, onTransformChange])

  const setCameraDirect = useCallback((next: CameraState) => {
    cameraRef.current = next
    setCamera(next)
    onTransformChange?.(next)
  }, [onTransformChange])

  const stopMomentum = useCallback(() => {
    if (rafRef.current) {
      cancelAnimationFrame(rafRef.current)
      rafRef.current = null
    }
    velocityRef.current = { x: 0, y: 0 }
  }, [])

  const startMomentum = useCallback(() => {
    stopMomentum()
    const tick = () => {
      const v = velocityRef.current
      if (Math.abs(v.x) < MOMENTUM_THRESHOLD && Math.abs(v.y) < MOMENTUM_THRESHOLD) {
        rafRef.current = null
        return
      }
      const c = cameraRef.current
      applyCamera({ x: c.x + v.x, y: c.y + v.y, scale: c.scale })
      velocityRef.current = { x: v.x * DEFAULT_FRICTION, y: v.y * DEFAULT_FRICTION }
      rafRef.current = requestAnimationFrame(tick)
    }
    rafRef.current = requestAnimationFrame(tick)
  }, [applyCamera, stopMomentum])

  const zoom = useCallback((delta: number, focalX?: number, focalY?: number) => {
    stopMomentum()
    const { w, h } = getViewSize()
    const fit = getFitScale()
    const fx = focalX ?? w / 2
    const fy = focalY ?? h / 2
    const next = zoomAtPoint(cameraRef.current, delta, fx, fy, w, h, fit)
    applyCamera(next)
  }, [applyCamera, getFitScale, getViewSize, stopMomentum])

  const resetView = useCallback(() => {
    stopMomentum()
    const fit = getFitScale()
    applyCamera({ x: 0, y: 0, scale: fit })
  }, [applyCamera, getFitScale, stopMomentum])

  const fitToView = useCallback(() => {
    resetView()
  }, [resetView])

  const zoomToScale = useCallback((targetScale: number) => {
    stopMomentum()
    const { w, h } = getViewSize()
    const fit = getFitScale()
    const next = zoomAtPoint(cameraRef.current, targetScale - cameraRef.current.scale, w / 2, h / 2, w, h, fit)
    applyCamera({ ...next, scale: clampScale(next.scale, fit) })
  }, [applyCamera, getFitScale, getViewSize, stopMomentum])

  const beginPinch = useCallback((el: HTMLElement) => {
    const pts = [...pointersRef.current.values()]
    if (pts.length < 2) return
    stopMomentum()
    dragRef.current = null
    setIsDragging(false)
    const rect = el.getBoundingClientRect()
    const dist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y)
    const midX = (pts[0].x + pts[1].x) / 2 - rect.left
    const midY = (pts[0].y + pts[1].y) / 2 - rect.top
    pinchRef.current = {
      active: true,
      startDist: Math.max(dist, 1),
      startScale: cameraRef.current.scale,
      focalX: midX,
      focalY: midY,
    }
  }, [stopMomentum])

  const updatePinch = useCallback(() => {
    if (!pinchRef.current?.active) return
    const pts = [...pointersRef.current.values()]
    if (pts.length < 2) return
    const dist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y)
    const fit = getFitScale()
    const ratio = dist / pinchRef.current.startDist
    const targetScale = clampScale(pinchRef.current.startScale * ratio, fit)
    const delta = targetScale - cameraRef.current.scale
    const { w, h } = getViewSize()
    applyCamera(
      zoomAtPoint(cameraRef.current, delta, pinchRef.current.focalX, pinchRef.current.focalY, w, h, fit),
    )
  }, [applyCamera, getFitScale, getViewSize])

  // Fit image when dimensions change or container resizes
  useEffect(() => {
    const el = containerRef.current
    if (!el) return

    const syncFit = (preserveRelativeZoom = false) => {
      const { w, h } = getViewSize()
      const fit = computeFitScale(imageWidth, imageHeight, w, h)
      const initial = computeInitialSmartWalkScale(imageWidth, imageHeight, w, h)
      const prevFit = fitScaleRef.current
      fitScaleRef.current = fit
      setFitScale(fit)
      if (preserveRelativeZoom && prevFit > 0) {
        const ratio = cameraRef.current.scale / prevFit
        applyCamera({ ...cameraRef.current, scale: clampScale(fit * ratio, fit) })
      } else {
        applyCamera({ x: 0, y: 0, scale: initial })
      }
    }

    syncFit(false)

    const ro = new ResizeObserver(() => syncFit(true))
    ro.observe(el)
    return () => ro.disconnect()
  }, [imageWidth, imageHeight, getFitScale, applyCamera, mountNode])

  // Native wheel + touch pinch/double-tap (passive:false required)
  useEffect(() => {
    const el = mountNode
    if (!el || disabled) return

    const onWheel = (e: WheelEvent) => {
      e.preventDefault()
      stopMomentum()
      const rect = el.getBoundingClientRect()
      const fx = e.clientX - rect.left
      const fy = e.clientY - rect.top
      const delta = e.deltaY > 0 ? -0.15 * fitScaleRef.current : 0.15 * fitScaleRef.current
      zoom(delta, fx, fy)
    }

    const getTouchDist = (touches: TouchList) => {
      if (touches.length < 2) return 0
      const dx = touches[0].clientX - touches[1].clientX
      const dy = touches[0].clientY - touches[1].clientY
      return Math.hypot(dx, dy)
    }

    const onTouchStart = (e: TouchEvent) => {
      if (e.touches.length !== 2) return
      e.preventDefault()
      stopMomentum()
      dragRef.current = null
      setIsDragging(false)
      const rect = el.getBoundingClientRect()
      const midX = (e.touches[0].clientX + e.touches[1].clientX) / 2 - rect.left
      const midY = (e.touches[0].clientY + e.touches[1].clientY) / 2 - rect.top
      pinchRef.current = {
        active: true,
        startDist: Math.max(getTouchDist(e.touches), 1),
        startScale: cameraRef.current.scale,
        focalX: midX,
        focalY: midY,
      }
    }

    const onTouchMove = (e: TouchEvent) => {
      if (!pinchRef.current?.active || e.touches.length < 2) return
      e.preventDefault()
      const dist = getTouchDist(e.touches)
      const fit = getFitScale()
      const ratio = dist / pinchRef.current.startDist
      const targetScale = clampScale(pinchRef.current.startScale * ratio, fit)
      const delta = targetScale - cameraRef.current.scale
      const { w, h } = getViewSize()
      applyCamera(
        zoomAtPoint(cameraRef.current, delta, pinchRef.current.focalX, pinchRef.current.focalY, w, h, fit),
      )
    }

    const onTouchEnd = (e: TouchEvent) => {
      if (e.touches.length < 2) pinchRef.current = null
    }

    const onTouchEndTap = (e: TouchEvent) => {
      if (e.touches.length > 0 || pinchRef.current?.active) return
      const t = e.changedTouches[0]
      const now = performance.now()
      const last = lastTapRef.current
      if (last && now - last.time < 300 && Math.hypot(t.clientX - last.x, t.clientY - last.y) < 40) {
        const rect = el.getBoundingClientRect()
        const fx = t.clientX - rect.left
        const fy = t.clientY - rect.top
        const fit = getFitScale()
        const target = cameraRef.current.scale < fit * 1.5 ? fit * 2.5 : fit
        zoom(target - cameraRef.current.scale, fx, fy)
        lastTapRef.current = null
      } else {
        lastTapRef.current = { time: now, x: t.clientX, y: t.clientY }
      }
    }

    el.addEventListener('wheel', onWheel, { passive: false })
    el.addEventListener('touchstart', onTouchStart, { passive: false })
    el.addEventListener('touchmove', onTouchMove, { passive: false })
    el.addEventListener('touchend', onTouchEnd)
    el.addEventListener('touchend', onTouchEndTap)

    return () => {
      el.removeEventListener('wheel', onWheel)
      el.removeEventListener('touchstart', onTouchStart)
      el.removeEventListener('touchmove', onTouchMove)
      el.removeEventListener('touchend', onTouchEnd)
      el.removeEventListener('touchend', onTouchEndTap)
    }
  }, [mountNode, disabled, zoom, stopMomentum, applyCamera, getFitScale, getViewSize])

  const onViewportPointerDown = useCallback((e: React.PointerEvent<HTMLDivElement>) => {
    if (disabled) return
    if (e.pointerType === 'mouse' && e.button !== 0) return

    pointersRef.current.set(e.pointerId, { x: e.clientX, y: e.clientY })

    if (pointersRef.current.size >= 2) {
      beginPinch(e.currentTarget)
      return
    }

    dragMovedRef.current = false
    stopMomentum()
    dragRef.current = {
      active: true,
      pointerId: e.pointerId,
      startX: e.clientX,
      startY: e.clientY,
      originX: cameraRef.current.x,
      originY: cameraRef.current.y,
      lastX: e.clientX,
      lastY: e.clientY,
      lastTime: performance.now(),
    }
    setIsDragging(true)
    e.currentTarget.setPointerCapture(e.pointerId)
  }, [disabled, stopMomentum, beginPinch])

  const onViewportPointerMove = useCallback((e: React.PointerEvent<HTMLDivElement>) => {
    if (pointersRef.current.has(e.pointerId)) {
      pointersRef.current.set(e.pointerId, { x: e.clientX, y: e.clientY })
    }

    if (pinchRef.current?.active && pointersRef.current.size >= 2) {
      updatePinch()
      return
    }

    if (!dragRef.current?.active || e.pointerId !== dragRef.current.pointerId) return
    const now = performance.now()
    const dx = e.clientX - dragRef.current.startX
    const dy = e.clientY - dragRef.current.startY
    if (Math.hypot(dx, dy) > 5) dragMovedRef.current = true
    const dt = now - dragRef.current.lastTime
    if (dt > 0) {
      const vx = (e.clientX - dragRef.current.lastX) / dt * 16
      const vy = (e.clientY - dragRef.current.lastY) / dt * 16
      velocityRef.current = { x: vx, y: vy }
    }
    dragRef.current.lastX = e.clientX
    dragRef.current.lastY = e.clientY
    dragRef.current.lastTime = now
    applyCamera({
      x: dragRef.current.originX + dx,
      y: dragRef.current.originY + dy,
      scale: cameraRef.current.scale,
    })
  }, [applyCamera, updatePinch])

  const onViewportPointerUp = useCallback((e: React.PointerEvent<HTMLDivElement>) => {
    pointersRef.current.delete(e.pointerId)
    if (pointersRef.current.size < 2) pinchRef.current = null

    if (!dragRef.current?.active || e.pointerId !== dragRef.current.pointerId) return
    dragRef.current = null
    setIsDragging(false)
    try {
      e.currentTarget.releasePointerCapture(e.pointerId)
    } catch {
      // ignore
    }
    startMomentum()
  }, [startMomentum])

  const onViewportDoubleClick = useCallback((e: React.MouseEvent<HTMLDivElement>) => {
    if (disabled) return
    const rect = e.currentTarget.getBoundingClientRect()
    const fx = e.clientX - rect.left
    const fy = e.clientY - rect.top
    const fit = getFitScale()
    const target = cameraRef.current.scale < fit * 1.5 ? fit * 2.5 : fit
    zoom(target - cameraRef.current.scale, fx, fy)
  }, [disabled, getFitScale, zoom])

  // Keyboard navigation
  useEffect(() => {
    if (disabled) return
    const onKey = (e: KeyboardEvent) => {
      const step = e.shiftKey ? 80 : 40
      const c = cameraRef.current
      switch (e.key) {
        case '+':
        case '=':
          zoom(0.25 * fitScaleRef.current)
          break
        case '-':
        case '_':
          zoom(-0.25 * fitScaleRef.current)
          break
        case '0':
          resetView()
          break
        case 'ArrowLeft':
          applyCamera({ ...c, x: c.x + step })
          break
        case 'ArrowRight':
          applyCamera({ ...c, x: c.x - step })
          break
        case 'ArrowUp':
          applyCamera({ ...c, y: c.y + step })
          break
        case 'ArrowDown':
          applyCamera({ ...c, y: c.y - step })
          break
      }
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [disabled, applyCamera, zoom, resetView])

  return {
    containerRef,
    setContainerRef,
    camera,
    cameraRef,
    fitScale,
    fitScaleRef,
    dragMovedRef,
    isDragging,
    applyCamera,
    setCameraDirect,
    zoom,
    resetView,
    fitToView,
    zoomToScale,
    stopMomentum,
    onViewportPointerDown,
    onViewportPointerMove,
    onViewportPointerUp,
    onViewportDoubleClick,
    transformCss: cameraTransformCss(camera),
    zoomPercent: zoomPercent(camera.scale, fitScale),
    maxZoomFactor: MAX_ZOOM_FACTOR,
  }
}
