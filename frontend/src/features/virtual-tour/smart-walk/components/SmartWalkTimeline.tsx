import { useRef, useState } from 'react'
import type { TourScene } from '../../types'

interface Props {
  scenes: TourScene[]
  activeSceneId: number | null
  onSelectScene: (id: number) => void
}

export function SmartWalkTimeline({ scenes, activeSceneId, onSelectScene }: Props) {
  const scrollRef = useRef<HTMLDivElement>(null)
  const [isDragging, setIsDragging] = useState(false)
  const dragStart = useRef({ x: 0, scrollLeft: 0 })

  const onMouseDown = (e: React.MouseEvent) => {
    if (!scrollRef.current) return
    setIsDragging(true)
    dragStart.current = { x: e.pageX, scrollLeft: scrollRef.current.scrollLeft }
  }

  const onMouseMove = (e: React.MouseEvent) => {
    if (!isDragging || !scrollRef.current) return
    const dx = e.pageX - dragStart.current.x
    scrollRef.current.scrollLeft = dragStart.current.scrollLeft - dx
  }

  const endDrag = () => setIsDragging(false)

  if (scenes.length <= 1) return null

  return (
    <div className="sw-timeline absolute bottom-0 left-0 right-0 z-30 border-t border-[var(--sw-border)] bg-[var(--sw-panel-bg)] backdrop-blur-md">
      <div
        ref={scrollRef}
        className={`flex gap-2 px-3 py-2 overflow-x-auto scrollbar-thin ${isDragging ? 'cursor-grabbing' : 'cursor-grab'}`}
        onMouseDown={onMouseDown}
        onMouseMove={onMouseMove}
        onMouseUp={endDrag}
        onMouseLeave={endDrag}
      >
        {scenes.map((s) => (
          <button
            key={s.id}
            type="button"
            onClick={() => onSelectScene(s.id)}
            className={`shrink-0 group relative rounded-lg overflow-hidden border-2 transition-all duration-300 ${
              s.id === activeSceneId
                ? 'border-primary ring-2 ring-primary/40 scale-105'
                : 'border-transparent opacity-75 hover:opacity-100'
            }`}
            style={{ width: 72, height: 48 }}
          >
            {s.thumbnail_url ? (
              <img src={s.thumbnail_url} alt="" className="w-full h-full object-cover" loading="lazy" draggable={false} />
            ) : (
              <div className="w-full h-full bg-white/10 flex items-center justify-center text-[10px] px-1 truncate">
                {s.name}
              </div>
            )}
            <span className="absolute bottom-0 inset-x-0 bg-black/60 text-[9px] py-0.5 truncate text-center">
              {s.name}
            </span>
          </button>
        ))}
      </div>
    </div>
  )
}
