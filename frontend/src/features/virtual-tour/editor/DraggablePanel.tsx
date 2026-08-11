import type { ReactNode } from 'react'
import { useState, useRef } from 'react'
import { GripHorizontal } from 'lucide-react'

interface Props {
  children: ReactNode
  title?: string
  defaultPosition?: { x: number; y: number }
  className?: string
}

export function DraggablePanel({ children, title, defaultPosition = { x: 0, y: 0 }, className = '' }: Props) {
  const [pos, setPos] = useState(defaultPosition)
  const dragRef = useRef<{ startX: number; startY: number; origX: number; origY: number } | null>(null)

  const onMouseDown = (e: React.MouseEvent) => {
    dragRef.current = { startX: e.clientX, startY: e.clientY, origX: pos.x, origY: pos.y }
    const onMove = (ev: MouseEvent) => {
      if (!dragRef.current) return
      setPos({
        x: dragRef.current.origX + ev.clientX - dragRef.current.startX,
        y: dragRef.current.origY + ev.clientY - dragRef.current.startY,
      })
    }
    const onUp = () => {
      dragRef.current = null
      window.removeEventListener('mousemove', onMove)
      window.removeEventListener('mouseup', onUp)
    }
    window.addEventListener('mousemove', onMove)
    window.addEventListener('mouseup', onUp)
  }

  return (
    <div
      className={`relative ${className}`}
      style={{ transform: `translate(${pos.x}px, ${pos.y}px)` }}
    >
      {title && (
        <div
          className="flex items-center gap-1 px-2 py-1 cursor-grab active:cursor-grabbing text-muted hover:text-foreground"
          onMouseDown={onMouseDown}
        >
          <GripHorizontal className="h-3 w-3" />
          <span className="text-[10px]">{title}</span>
        </div>
      )}
      {children}
    </div>
  )
}
