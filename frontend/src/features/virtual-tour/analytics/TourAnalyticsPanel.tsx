import { useQuery } from '@tanstack/react-query'
import { tourApi } from '../api/tourApi'

export function TourAnalyticsPanel({ tourId }: { tourId: number | string }) {
  const { data, isLoading } = useQuery({
    queryKey: ['virtual-tour-analytics', tourId],
    queryFn: async () => (await tourApi.analytics(tourId)).data.data as AnalyticsPayload,
  })

  if (isLoading) {
    return (
      <div className="flex justify-center py-12">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  if (!data) return null

  const e = data.engagement

  return (
    <div className="p-4 space-y-4 overflow-y-auto h-full">
      <h2 className="font-semibold text-sm">تحلیل تور</h2>

      <div className="grid grid-cols-2 gap-2">
        <Stat label="بازدید" value={data.view_count} />
        <Stat label="سرنخ" value={data.leads_count} />
        <Stat label="نشست‌ها" value={e?.unique_sessions ?? 0} />
        <Stat label="تکمیل تور" value={`${e?.completion_rate ?? 0}%`} />
        <Stat label="میانگین زمان" value={`${e?.avg_viewing_seconds ?? 0}s`} />
        <Stat label="پربازدیدترین اتاق" value={e?.most_viewed_scene_views ?? 0} />
      </div>

      {(data.scene_views?.length ?? 0) > 0 && (
        <div>
          <p className="text-xs text-muted mb-2">بازدید صحنه‌ها</p>
          <div className="space-y-1">
            {(data.scene_views ?? []).slice(0, 8).map((s) => (
              <div key={s.scene_id} className="flex justify-between text-xs py-1 border-b border-card-border/30">
                <span>صحنه #{s.scene_id}</span>
                <span className="font-mono">{s.views}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {(data.device_breakdown?.length ?? 0) > 0 && (
        <div>
          <p className="text-xs text-muted mb-2">دستگاه</p>
          <div className="flex flex-wrap gap-2">
            {(data.device_breakdown ?? []).map((d) => (
              <span key={d.device_type || 'unknown'} className="text-[10px] px-2 py-1 rounded bg-black/20 border border-card-border/40">
                {d.device_type || 'نامشخص'}: {d.count}
              </span>
            ))}
          </div>
        </div>
      )}

      {(data.hotspot_clicks?.length ?? 0) > 0 && (
        <div>
          <p className="text-xs text-muted mb-2">کلیک هات‌اسپات</p>
          <div className="space-y-1 max-h-32 overflow-y-auto">
            {(data.hotspot_clicks ?? []).slice(0, 10).map((h) => (
              <div key={h.hotspot_id} className="flex justify-between text-xs py-1">
                <span>#{h.hotspot_id}</span>
                <span>{h.clicks} کلیک</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {(data.recent_leads?.length ?? 0) > 0 && (
        <div>
          <p className="text-xs text-muted mb-2">آخرین درخواست‌های بازدید</p>
          <div className="space-y-2 max-h-40 overflow-y-auto">
            {(data.recent_leads ?? []).map((lead) => (
              <div key={lead.id} className="text-xs py-2 border-b border-card-border/30">
                <p className="font-medium">{lead.name}</p>
                <p className="text-muted" dir="ltr">{lead.mobile}</p>
                {lead.message && <p className="text-muted mt-0.5 truncate">{lead.message}</p>}
                {lead.created_at && (
                  <p className="text-[10px] text-muted mt-0.5">{lead.created_at.slice(0, 16).replace('T', ' ')}</p>
                )}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}

function Stat({ label, value }: { label: string; value: number | string }) {
  return (
    <div className="rounded-lg p-3 bg-black/20 border border-card-border/40">
      <p className="text-[10px] text-muted">{label}</p>
      <p className="text-lg font-bold mt-0.5">{value}</p>
    </div>
  )
}

interface AnalyticsPayload {
  view_count: number
  leads_count: number
  engagement?: {
    unique_sessions: number
    tour_completions: number
    completion_rate: number
    avg_viewing_seconds: number
    most_viewed_scene_id?: number
    most_viewed_scene_views: number
  }
  scene_views?: { scene_id: number; views: number }[]
  hotspot_clicks?: { hotspot_id: number; clicks: number }[]
  device_breakdown?: { device_type: string | null; count: number }[]
  recent_leads?: { id: number; name: string; mobile: string; message?: string | null; created_at?: string }[]
}
