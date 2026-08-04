import { useCallback, useEffect, useRef, useState } from 'react'
import {
  type CameraState,
  clampPan,
  clampScale,
  cameraTransformCss,
  DEFAULT_FRICTION,
  MAX_SCALE,
  MIN_SCALE,
  MOMENTUM_THRESHOLD,
  zoomAtPoint,
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
  const containerRef = useRef<HTMLDivElement>(null)
  const cameraRef = useRef<CameraState>({ x: 0, y: 0, scale: 1 })
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
  const lastTapRef = useRef<{ time: number; x: number; y: number } | null>(null)

  const [camera, setCamera] = useState<CameraState>({ x: 0, y: 0, scale: 1 })
  const [isDragging, setIsDragging] = useState(false)

  const getViewSize = useCallback(() => {
    const el = containerRef.current
    if (!el) return { w: 1, h: 1 }
    const rect = el.getBoundingClientRect()
    return { w: rect.width, h: rect.height }
  }, [])

  const applyCamera = useCallback((next: CameraState, skipClamp = false) => {
    const { w, h } = getViewSize()
    const clamped = skipClamp
      ? next
      : clampPan(next, imageWidth, imageHeight, w, h)
    cameraRef.current = clamped
    setCamera(clamped)
    onTransformChange?.(clamped)
  }, [getViewSize, imageWidth, imageHeight, onTransformChange])

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
    const fx = focalX ?? w / 2
    const fy = focalY ?? h / 2
    const next = zoomAtPoint(cameraRef.current, delta, fx, fy, w, h)
    applyCamera(next)
  }, [applyCamera, getViewSize, stopMomentum])

  const resetView = useCallback(() => {
    stopMomentum()
    applyCamera({ x: 0, y: 0, scale: 1 })
  }, [applyCamera, stopMomentum])

  const zoomToScale = useCallback((scale: number) => {
    stopMomentum()
    const { w, h } = getViewSize()
    const next = zoomAtPoint(cameraRef.current, scale - cameraRef.current.scale, w / 2, h / 2, w, h)
    applyCamera(next)
  }, [applyCamera, getViewSize, stopMomentum])

  // Pointer drag + pinch
  useEffect(() => {
    const el = containerRef.current
    if (!el || disabled) return

    const getTouchDist = (touches: TouchList) => {
      if (touches.length < 2) return 0
      const dx = touches[0].clientX - touches[1].clientX
      const dy = touches[0].clientY - touches[1].clientY
      return Math.hypot(dx, dy)
    }

    const onPointerDown = (e: PointerEvent) => {
      if (e.pointerType === 'touch') return
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
      el.setPointerCapture(e.pointerId)
    }

    const onPointerMove = (e: PointerEvent) => {
      if (!dragRef.current?.active || e.pointerId !== dragRef.current.pointerId) return
      const now = performance.now()
      const dx = e.clientX - dragRef.current.startX
      const dy = e.clientY - dragRef.current.startY
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
    }

    const onPointerUp = (e: PointerEvent) => {
      if (!dragRef.current?.active || e.pointerId !== dragRef.current.pointerId) return
      dragRef.current = null
      setIsDragging(false)
      el.releasePointerCapture(e.pointerId)
      startMomentum()
    }

    const onTouchStart = (e: TouchEvent) => {
      if (e.touches.length === 2) {
        e.preventDefault()
        stopMomentum()
        const rect = el.getBoundingClientRect()
        const midX = (e.touches[0].clientX + e.touches[1].clientX) / 2 - rect.left
        const midY = (e.touches[0].clientY + e.touches[1].clientY) / 2 - rect.top
        pinchRef.current = {
          active: true,
          startDist: getTouchDist(e.touches),
          startScale: cameraRef.current.scale,
          focalX: midX,
          focalY: midY,
        }
        dragRef.current = null
      } else if (e.touches.length === 1 && !pinchRef.current?.active) {
        stopMomentum()
        const t = e.touches[0]
        dragRef.current = {
          active: true,
          pointerId: -1,
          startX: t.clientX,
          startY: t.clientY,
          originX: cameraRef.current.x,
          originY: cameraRef.current.y,
          lastX: t.clientX,
          lastY: t.clientY,
          lastTime: performance.now(),
        }
        setIsDragging(true)
      }
    }

    const onTouchMove = (e: TouchEvent) => {
      if (pinchRef.current?.active && e.touches.length >= 2) {
        e.preventDefault()
        const dist = getTouchDist(e.touches)
        if (pinchRef.current.startDist > 0) {
          const ratio = dist / pinchRef.current.startDist
          const targetScale = clampScale(pinchRef.current.startScale * ratio)
          const delta = targetScale - cameraRef.current.scale
          const { w, h } = getViewSize()
          applyCamera(
            zoomAtPoint(cameraRef.current, delta, pinchRef.current.focalX, pinchRef.current.focalY, w, h),
          )
        }
        return
      }
      if (dragRef.current?.active && e.touches.length === 1) {
        const t = e.touches[0]
        const dx = t.clientX - dragRef.current.startX
        const dy = t.clientY - dragRef.current.startY
        applyCamera({
          x: dragRef.current.originX + dx,
          y: dragRef.current.originY + dy,
          scale: cameraRef.current.scale,
        })
      }
    }

    const onTouchEnd = (e: TouchEvent) => {
      if (e.touches.length < 2) pinchRef.current = null
      if (e.touches.length === 0) {
        if (dragRef.current?.active) {
          dragRef.current = null
          setIsDragging(false)
          startMomentum()
        }
      }
    }

    const onDblClick = (e: MouseEvent) => {
      const rect = el.getBoundingClientRect()
      const fx = e.clientX - rect.left
      const fy = e.clientY - rect.top
      const target = cameraRef.current.scale < 2 ? 2.5 : 1
      zoom(target - cameraRef.current.scale, fx, fy)
    }

  // Double-tap on mobile
    const onTouchEndTap = (e: TouchEvent) => {
      if (e.touches.length > 0 || pinchRef.current?.active) return
      const t = e.changedTouches[0]
      const now = performance.now()
      const last = lastTapRef.current
      if (last && now - last.time < 300 && Math.hypot(t.clientX - last.x, t.clientY - last.y) < 40) {
        const rect = el.getBoundingClientRect()
        const fx = t.clientX - rect.left
        const fy = t.clientY - rect.top
        const target = cameraRef.current.scale < 2 ? 2.5 : 1
        zoom(target - cameraRef.current.scale, fx, fy)
        lastTapRef.current = null
      } else {
        lastTapRef.current = { time: now, x: t.clientX, y: t.clientY }
      }
    }

    el.addEventListener('pointerdown', onPointerDown)
    el.addEventListener('pointermove', onPointerMove)
    el.addEventListener('pointerup', onPointerUp)
    el.addEventListener('pointercancel', onPointerUp)
    el.addEventListener('touchstart', onTouchStart, { passive: false })
    el.addEventListener('touchmove', onTouchMove, { passive: false })
    el.addEventListener('touchend', onTouchEnd)
    el.addEventListener('touchend', onTouchEndTap)
    el.addEventListener('dblclick', onDblClick)

    return () => {
      el.removeEventListener('pointerdown', onPointerDown)
      el.removeEventListener('pointermove', onPointerMove)
      el.removeEventListener('pointerup', onPointerUp)
      el.removeEventListener('pointercancel', onPointerUp)
      el.removeEventListener('touchstart', onTouchStart)
      el.removeEventListener('touchmove', onTouchMove)
      el.removeEventListener('touchend', onTouchEnd)
      el.removeEventListener('touchend', onTouchEndTap)
      el.removeEventListener('dblclick', onDblClick)
    }
  }, [disabled, applyCamera, getViewSize, startMomentum, stopMomentum, zoom])

  const handleWheel = useCallback((e: React.WheelEvent) => {
    if (disabled) return
    e.preventDefault()
    stopMomentum()
    const rect = containerRef.current?.getBoundingClientRect()
    const fx = rect ? e.clientX - rect.left : 0
    const fy = rect ? e.clientY - rect.top : 0
    const delta = e.deltaY > 0 ? -0.12 : 0.12
    zoom(delta, fx, fy)
  }, [disabled, zoom, stopMomentum])

  // Keyboard navigation
  useEffect(() => {
    if (disabled) return
    const onKey = (e: KeyboardEvent) => {
      const step = e.shiftKey ? 80 : 40
      const c = cameraRef.current
      switch (e.key) {
        case '+':
        case '=':
          zoom(0.25)
          break
        case '-':
        case '_':
          zoom(-0.25)
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
    camera,
    cameraRef,
    isDragging,
    applyCamera,
    setCameraDirect,
    zoom,
    resetView,
    zoomToScale,
    handleWheel,
    stopMomentum,
    transformCss: cameraTransformCss(camera),
    minScale: MIN_SCALE,
    maxScale: MAX_SCALE,
  }
}
