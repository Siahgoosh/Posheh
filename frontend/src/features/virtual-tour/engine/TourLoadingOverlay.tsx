interface Props {
  progress: number
  isLoading: boolean
  error?: string | null
}

export function TourLoadingOverlay({ progress, isLoading, error }: Props) {
  if (error) {
    return (
      <div className="absolute inset-0 z-30 flex flex-col items-center justify-center bg-black/90 p-6 text-center">
        <div className="w-16 h-16 rounded-2xl bg-danger/20 border border-danger/30 flex items-center justify-center mb-4">
          <span className="text-2xl">⚠</span>
        </div>
        <p className="text-danger font-medium mb-2">{error}</p>
        <p className="text-xs text-white/50 max-w-sm">
          فرمت equirectangular (نسبت ۲:۱) با حداقل ۴۰۰۰×۲۰۰۰ پیکسل توصیه می‌شود.
        </p>
      </div>
    )
  }

  if (!isLoading) return null

  return (
    <div className="absolute inset-0 z-30 flex flex-col items-center justify-center bg-black/80">
      {/* Skeleton panorama placeholder */}
      <div className="absolute inset-0 overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-b from-teal-900/20 via-black/40 to-purple-900/20 animate-pulse" />
        <div className="absolute inset-0 opacity-20"
          style={{
            backgroundImage: 'repeating-linear-gradient(90deg, transparent, transparent 40px, rgba(45,212,191,0.05) 40px, rgba(45,212,191,0.05) 41px)',
          }}
        />
      </div>

      <div className="relative z-10 flex flex-col items-center gap-4 w-full max-w-xs px-6">
        <div className="relative w-20 h-20">
          <div className="absolute inset-0 rounded-full border-2 border-primary/20" />
          <div
            className="absolute inset-0 rounded-full border-2 border-primary border-t-transparent animate-spin"
          />
          <div className="absolute inset-3 rounded-full bg-primary/10 flex items-center justify-center text-lg font-bold text-primary">
            ۳۶۰
          </div>
        </div>

        <div className="text-center">
          <p className="text-sm text-white/80 mb-1">در حال بارگذاری پانوراما...</p>
          <p className="text-xs text-white/40">{progress}%</p>
        </div>

        <div className="w-full h-1.5 rounded-full bg-white/10 overflow-hidden">
          <div
            className="h-full rounded-full bg-gradient-to-l from-primary to-teal-300 transition-all duration-300"
            style={{ width: `${Math.max(progress, 5)}%` }}
          />
        </div>
      </div>
    </div>
  )
}
