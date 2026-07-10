import { cn } from '@/lib/utils'

export interface SeoCheck {
  id: string
  label: string
  score: number
  max: number
  status: 'pass' | 'warn' | 'fail' | string
  message: string
}

export interface SeoAnalysis {
  score: number
  grade: string
  summary: string
  checks: SeoCheck[]
}

interface SeoScorePanelProps {
  analysis: SeoAnalysis | null
  loading?: boolean
}

export function SeoScorePanel({ analysis, loading }: SeoScorePanelProps) {
  const score = analysis?.score ?? 0
  const color =
    score >= 75 ? 'text-emerald-400' : score >= 50 ? 'text-amber-400' : 'text-red-400'
  const ring =
    score >= 75 ? 'from-emerald-500 to-emerald-600' : score >= 50 ? 'from-amber-500 to-amber-600' : 'from-red-500 to-red-600'

  return (
    <div className="rounded-xl border border-card-border bg-card/40 p-4 space-y-4 sticky top-4">
      <div className="text-center">
        <p className="text-sm text-muted mb-2">نمره سئو گوگل</p>
        <div
          className={cn(
            'mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br text-2xl font-bold text-white shadow-lg',
            ring
          )}
        >
          {loading ? '…' : score}
        </div>
        <p className={cn('mt-2 font-medium', color)}>{analysis?.grade ?? '—'}</p>
        <p className="text-xs text-muted mt-1">{analysis?.summary ?? 'در حال تحلیل…'}</p>
      </div>

      {analysis?.checks && (
        <ul className="space-y-2 max-h-[420px] overflow-y-auto text-sm">
          {analysis.checks.map((check) => (
            <li
              key={check.id}
              className={cn(
                'rounded-lg border px-3 py-2',
                check.status === 'pass' && 'border-emerald-500/30 bg-emerald-500/5',
                check.status === 'warn' && 'border-amber-500/30 bg-amber-500/5',
                check.status === 'fail' && 'border-red-500/30 bg-red-500/5'
              )}
            >
              <div className="flex justify-between gap-2 font-medium">
                <span>{check.label}</span>
                <span className="text-muted text-xs">{Math.round(check.score)}/{Math.round(check.max)}</span>
              </div>
              <p className="text-xs text-muted mt-1 leading-relaxed">{check.message}</p>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
