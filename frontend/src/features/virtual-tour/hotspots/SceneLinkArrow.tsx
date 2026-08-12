import type { SceneLinkArrowOptions } from './sceneLinkArrow'

interface Props extends SceneLinkArrowOptions {
  className?: string
  onClick?: (e: React.MouseEvent) => void
  onPointerDown?: (e: React.PointerEvent) => void
  selected?: boolean
  hovered?: boolean
  editorMode?: boolean
  locked?: boolean
}

function ArrowIcon({ icon, size }: { icon: string; size: number }) {
  const s = Math.round(size * 0.55)
  if (icon === 'chevron') {
    return (
      <svg viewBox="0 0 24 24" width={s} height={s} aria-hidden="true">
        <path d="M8 6l6 6-6 6" fill="none" stroke="#fff" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
    )
  }
  if (icon === 'door') {
    return (
      <svg viewBox="0 0 24 24" width={s} height={s} aria-hidden="true">
        <path d="M7 5h8v14H7z" fill="none" stroke="#fff" strokeWidth="2" />
        <path d="M11 12h2" stroke="#fff" strokeWidth="2" strokeLinecap="round" />
      </svg>
    )
  }
  return (
    <svg viewBox="0 0 24 24" width={s} height={s} aria-hidden="true">
      <path d="M6 12h10M12 6l6 6-6 6" fill="none" stroke="#fff" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  )
}

export function SceneLinkArrow({
  color = '#2dd4bf',
  size = 48,
  label,
  tooltip,
  rotation = 0,
  pulse = true,
  glow = true,
  icon = 'arrow',
  className = '',
  onClick,
  onPointerDown,
  selected,
  hovered,
  editorMode,
  locked,
}: Props) {
  const inner = Math.round(size * 0.55)
  const title = tooltip || label || 'رفتن به صحنه بعدی'

  return (
    <div
      className={`flex flex-col items-center ${pulse ? 'vt-scene-link-pulse' : ''} ${className}`}
      style={{
        transform: `rotate(${rotation}deg)`,
        filter: glow
          ? `drop-shadow(0 0 8px ${color}aa) drop-shadow(0 4px 12px rgba(0,0,0,0.45))`
          : 'drop-shadow(0 4px 8px rgba(0,0,0,0.4))',
      }}
    >
      <button
        type="button"
        title={title}
        onClick={onClick}
        onPointerDown={onPointerDown}
        className={`relative rounded-full border-2 border-white/85 flex items-center justify-center bg-black/25 backdrop-blur-sm transition-all duration-150 ${
          editorMode && !locked ? 'cursor-move hover:scale-110' : 'cursor-pointer hover:scale-110'
        } ${selected ? 'ring-2 ring-white scale-110' : hovered ? 'scale-110' : ''}`}
        style={{
          width: size,
          height: size,
          borderColor: color,
          color: color,
        }}
      >
        <span
          className="rounded-full flex items-center justify-center shadow-inner"
          style={{
            width: inner,
            height: inner,
            background: `linear-gradient(145deg, ${color}, ${color}cc)`,
          }}
        >
          <ArrowIcon icon={icon} size={inner} />
        </span>
      </button>
      {label?.trim() && (
        <span className="mt-1.5 px-2.5 py-0.5 text-[11px] font-semibold text-white bg-black/55 border border-white/15 rounded-full whitespace-nowrap max-w-[140px] truncate backdrop-blur-sm">
          {label}
        </span>
      )}
    </div>
  )
}
