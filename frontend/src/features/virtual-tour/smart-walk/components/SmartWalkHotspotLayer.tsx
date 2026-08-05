import { useCallback, useRef, useState, type RefObject } from 'react'
import type { TourHotspot } from '../../types'
import { getHotspotTypeDef } from '../../hotspots/constants'

const SNAP_GRID = 5

interface Props {
  hotspots: TourHotspot[]
  brandColor: string
  layerRef?: RefObject<HTMLDivElement | null>
  editorMode?: boolean
  selectedId?: number | string | null
  onHotspotClick: (h: TourHotspot) => void
  onHotspotMove?: (h: TourHotspot, x: number, y: number) => void
  onHotspotResize?: (h: TourHotspot, size: number) => void
  onHotspotRotate?: (h: TourHotspot, rotation: number) => void
  showGuides?: boolean
  placementPreview?: { x: number; y: number } | null
}

export function SmartWalkHotspotLayer({
  hotspots,
  brandColor,
  layerRef,
  editorMode = false,
  selectedId,
  onHotspotClick,
  onHotspotMove,
  onHotspotResize,
  onHotspotRotate,
  showGuides = true,
  placementPreview,
}: Props) {
  const dragRef = useRef<{
    id: number | string
    startX: number
    startY: number
    originX: number
    originY: number
  } | null>(null)
  const [guideLines, setGuideLines] = useState<{ x?: number; y?: number }>({})
  const [hoverId, setHoverId] = useState<number | string | null>(null)

  const sorted = [...hotspots].sort(
    (a, b) => (a.style?.zIndex ?? a.sort_order ?? 0) - (b.style?.zIndex ?? b.sort_order ?? 0),
  )

  const snap = (v: number) => {
    const snapped = Math.round(v / SNAP_GRID) * SNAP_GRID
    return Math.max(0, Math.min(100, snapped))
  }

  const checkGuides = useCallback((x: number, y: number, excludeId: number | string) => {
    if (!showGuides) return { x, y, guides: {} }
    const others = hotspots.filter((h) => h.id !== excludeId)
    let gx: number | undefined
    let gy: number | undefined
    let sx = x
    let sy = y
    for (const o of others) {
      const ox = o.position_x ?? 50
      const oy = o.position_y ?? 50
      if (Math.abs(x - ox) < 2) {
        sx = ox
        gx = ox
      }
      if (Math.abs(y - oy) < 2) {
        sy = oy
        gy = oy
      }
    }
    return { x: snap(sx), y: snap(sy), guides: { x: gx, y: gy } }
  }, [hotspots, showGuides])

  const endDrag = useCallback(() => {
    dragRef.current = null
    setGuideLines({})
  }, [])

  const onPointerDown = (e: React.PointerEvent, h: TourHotspot) => {
    if (!editorMode || h.style?.locked || !onHotspotMove) return
    e.stopPropagation()
    e.preventDefault()

    const layer = layerRef?.current
    if (!layer) return

    dragRef.current = {
      id: h.id,
      startX: e.clientX,
      startY: e.clientY,
      originX: h.position_x ?? 50,
      originY: h.position_y ?? 50,
    }

    const onMove = (ev: PointerEvent) => {
      if (!dragRef.current || dragRef.current.id !== h.id) return
      const rect = layer.getBoundingClientRect()
      if (rect.width <= 0 || rect.height <= 0) return
      const dx = ((ev.clientX - dragRef.current.startX) / rect.width) * 100
      const dy = ((ev.clientY - dragRef.current.startY) / rect.height) * 100
      const rawX = dragRef.current.originX + dx
      const rawY = dragRef.current.originY + dy
      const { x, y, guides } = checkGuides(rawX, rawY, h.id)
      setGuideLines(guides)
      onHotspotMove(h, x, y)
    }

    const onUp = () => {
      window.removeEventListener('pointermove', onMove)
      window.removeEventListener('pointerup', onUp)
      window.removeEventListener('pointercancel', onUp)
      endDrag()
    }

    window.addEventListener('pointermove', onMove)
    window.addEventListener('pointerup', onUp)
    window.addEventListener('pointercancel', onUp)
  }

  return (
    <>
      {showGuides && guideLines.x !== undefined && (
        <div className="absolute top-0 bottom-0 w-px bg-primary/60 z-20 pointer-events-none" style={{ left: `${guideLines.x}%` }} />
      )}
      {showGuides && guideLines.y !== undefined && (
        <div className="absolute left-0 right-0 h-px bg-primary/60 z-20 pointer-events-none" style={{ top: `${guideLines.y}%` }} />
      )}

      {placementPreview && (
        <div
          className="absolute z-20 pointer-events-none"
          style={{
            left: `${placementPreview.x}%`,
            top: `${placementPreview.y}%`,
            transform: 'translate(-50%, -50%)',
          }}
        >
          <div
            className="rounded-full border-2 border-dashed border-white/90 flex items-center justify-center animate-pulse"
            style={{
              width: 36,
              height: 36,
              background: `${brandColor}55`,
              boxShadow: `0 0 12px ${brandColor}`,
            }}
          >
            <span className="text-sm text-white">+</span>
          </div>
        </div>
      )}

      {sorted.map((h) => {
        const px = h.position_x ?? 50
        const py = h.position_y ?? 50
        const size = h.style?.size ?? 36
        const rotation = h.style?.rotation ?? 0
        const color = h.style?.color || brandColor
        const def = getHotspotTypeDef(h.type)
        const selected = selectedId === h.id
        const hovered = hoverId === h.id

        return (
          <div
            key={h.id}
            className="absolute z-10 pointer-events-auto"
            style={{
              left: `${px}%`,
              top: `${py}%`,
              transform: `translate(-50%, -50%) rotate(${rotation}deg)`,
              zIndex: h.style?.zIndex ?? h.sort_order ?? 10,
            }}
            onMouseEnter={() => setHoverId(h.id)}
            onMouseLeave={() => setHoverId((id) => (id === h.id ? null : id))}
          >
            <button
              type="button"
              className={`relative rounded-full border-2 flex items-center justify-center transition-all duration-150 ${
                h.style?.pulse ? 'animate-pulse' : ''
              } ${editorMode && !h.style?.locked ? 'cursor-move hover:ring-2 hover:ring-white/80 hover:scale-110' : 'cursor-pointer hover:scale-110'} ${
                selected ? 'ring-2 ring-white scale-110 shadow-lg' : hovered ? 'scale-110 shadow-md' : ''
              }`}
              style={{
                width: size,
                height: size,
                borderColor: color,
                background: `${color}40`,
                boxShadow: h.style?.glow ? `0 0 14px ${color}` : undefined,
                opacity: h.style?.opacity ?? 1,
              }}
              title={h.tooltip || h.label || h.title || ''}
              onClick={(e) => {
                e.stopPropagation()
                onHotspotClick(h)
              }}
              onPointerDown={(e) => onPointerDown(e, h)}
            >
              <span className="text-sm">{h.type === 'scene' ? '➡' : def.emoji}</span>
            </button>

            {editorMode && selected && !h.style?.locked && onHotspotResize && (
              <button
                type="button"
                className="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-white border border-primary text-[8px] flex items-center justify-center cursor-nwse-resize"
                onPointerDown={(e) => {
                  e.stopPropagation()
                  const start = size
                  const startY = e.clientY
                  const move = (ev: PointerEvent) => {
                    const delta = (ev.clientY - startY) * 0.5
                    onHotspotResize(h, Math.max(20, Math.min(80, start + delta)))
                  }
                  const up = () => {
                    window.removeEventListener('pointermove', move)
                    window.removeEventListener('pointerup', up)
                  }
                  window.addEventListener('pointermove', move)
                  window.addEventListener('pointerup', up)
                }}
              >
                ◢
              </button>
            )}

            {editorMode && selected && !h.style?.locked && onHotspotRotate && (
              <button
                type="button"
                className="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-white border border-primary text-[8px] cursor-grab"
                onPointerDown={(e) => {
                  e.stopPropagation()
                  const cx = e.clientX
                  const startRot = rotation
                  const move = (ev: PointerEvent) => {
                    onHotspotRotate(h, startRot + (ev.clientX - cx) * 0.8)
                  }
                  const up = () => {
                    window.removeEventListener('pointermove', move)
                    window.removeEventListener('pointerup', up)
                  }
                  window.addEventListener('pointermove', move)
                  window.addEventListener('pointerup', up)
                }}
              >
                ↻
              </button>
            )}
          </div>
        )
      })}
    </>
  )
}
