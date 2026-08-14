interface Props {
  text?: string | null
  enabled?: boolean
}

export function TourWatermark({ text, enabled = false }: Props) {
  if (!enabled || !text) return null

  return (
    <div
      className="pointer-events-none absolute inset-0 z-25 overflow-hidden opacity-[0.12]"
      aria-hidden
    >
      <div
        className="absolute inset-0 flex flex-wrap content-center justify-center gap-16 rotate-[-24deg] scale-150"
        style={{ fontSize: 'clamp(14px, 3vw, 28px)' }}
      >
        {Array.from({ length: 12 }).map((_, i) => (
          <span key={i} className="text-white font-bold whitespace-nowrap select-none">
            {text}
          </span>
        ))}
      </div>
    </div>
  )
}
