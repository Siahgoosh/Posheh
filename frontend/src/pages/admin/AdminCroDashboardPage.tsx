import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight } from 'lucide-react'
import api from '@/lib/api'
import { adminPath } from '@/lib/adminPaths'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

type Dash = {
  funnel: Record<string, number | null>
  rates: Record<string, number | null>
  lead_quality?: Record<string, unknown> | null
  top_converting_articles: { article_slug: string; leads: number; qualified: number; avg_score: number | null }[]
  high_traffic_low_conversion: { slug: string; impressions_28d: number; leads_28d: number; recommendation: string }[]
  low_traffic_high_conversion: { slug: string; leads_28d: number; impressions_28d: number; recommendation: string }[]
  weekly_actions: { rank: number; action: string }[]
  data_quality: { revenue: string; note: string }
}

type Lead = {
  id: number
  name?: string
  mobile: string
  request_type?: string
  status: string
  lead_score: number
  source: string
  article_slug?: string
  is_duplicate?: boolean
  quality_feedback?: string
}

function fmt(v: number | null | undefined) {
  if (v === null || v === undefined) return 'UNKNOWN'
  return String(v)
}

export function AdminCroDashboardPage() {
  const qc = useQueryClient()
  const { data, isLoading } = useQuery({
    queryKey: ['cro-dashboard'],
    queryFn: async () => (await api.get('/admin/cro/dashboard')).data.data as Dash,
  })
  const { data: leads } = useQuery({
    queryKey: ['cro-leads'],
    queryFn: async () => (await api.get('/admin/cro/leads')).data.data as Lead[],
  })

  const bootstrap = useMutation({
    mutationFn: () => api.post('/admin/cro/bootstrap'),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['cro-dashboard'] }),
  })
  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => api.post(`/admin/cro/leads/${id}/status`, { status }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['cro-leads'] })
      qc.invalidateQueries({ queryKey: ['cro-dashboard'] })
    },
  })
  const feedbackMutation = useMutation({
    mutationFn: ({ id, quality_feedback }: { id: number; quality_feedback: string }) =>
      api.post(`/admin/cro/leads/${id}/feedback`, { quality_feedback }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['cro-leads'] }),
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="icon" onClick={() => window.history.back()}><ArrowRight className="h-5 w-5" /></Button>
          <div>
            <h1 className="text-2xl font-bold">CRO & Lead Funnel</h1>
            <p className="text-sm text-muted">ترافیک → CTA → فرم → Lead → CRM (بدون داده جعلی)</p>
          </div>
        </div>
        <div className="flex gap-2">
          <Link to={adminPath('seo-growth')}><Button variant="outline">SEO Growth</Button></Link>
          <Button variant="outline" onClick={() => bootstrap.mutate()}>Bootstrap CTA</Button>
        </div>
      </div>

      {isLoading && <p className="text-muted">در حال بارگذاری…</p>}
      {data && (
        <>
          <p className="text-xs text-muted">{data.data_quality.note} · Revenue: {data.data_quality.revenue}</p>
          <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {Object.entries(data.funnel).map(([k, v]) => (
              <Card key={k}><CardContent className="pt-4"><p className="text-xs text-muted">{k}</p><p className="text-xl font-bold mt-1">{fmt(v)}</p></CardContent></Card>
            ))}
          </div>
          <div className="grid sm:grid-cols-3 gap-3">
            {Object.entries(data.rates).map(([k, v]) => (
              <Card key={k}><CardContent className="pt-4"><p className="text-xs text-muted">{k}</p><p className="text-lg font-bold mt-1">{v === null ? 'UNKNOWN' : `${(v * 100).toFixed(2)}%`}</p></CardContent></Card>
            ))}
          </div>

          <Card>
            <CardHeader><CardTitle className="text-base">Weekly Growth Actions (max 10)</CardTitle></CardHeader>
            <CardContent className="space-y-2 text-sm">
              {data.weekly_actions.map((a) => <p key={a.rank}>{a.rank}. {a.action}</p>)}
            </CardContent>
          </Card>

          <div className="grid lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader><CardTitle className="text-base">High traffic / low conversion</CardTitle></CardHeader>
              <CardContent className="space-y-2 text-sm">
                {!data.high_traffic_low_conversion.length && <p className="text-muted">داده‌ای نیست یا GSC خالی است.</p>}
                {data.high_traffic_low_conversion.map((i) => (
                  <div key={i.slug} className="border-b border-card-border pb-2">
                    <p className="font-medium">{i.slug}</p>
                    <p className="text-muted">imp {i.impressions_28d} · leads {i.leads_28d}</p>
                    <p>{i.recommendation}</p>
                  </div>
                ))}
              </CardContent>
            </Card>
            <Card>
              <CardHeader><CardTitle className="text-base">Low traffic / high conversion</CardTitle></CardHeader>
              <CardContent className="space-y-2 text-sm">
                {!data.low_traffic_high_conversion.length && <p className="text-muted">هنوز سیگنال کافی نیست.</p>}
                {data.low_traffic_high_conversion.map((i) => (
                  <div key={i.slug} className="border-b border-card-border pb-2">
                    <p className="font-medium">{i.slug}</p>
                    <p className="text-muted">leads {i.leads_28d} · imp {i.impressions_28d}</p>
                    <p>{i.recommendation}</p>
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader><CardTitle className="text-base">Top converting articles</CardTitle></CardHeader>
            <CardContent className="space-y-2 text-sm">
              {!data.top_converting_articles.length && <p className="text-muted">هنوز Lead مقاله‌ای ثبت نشده.</p>}
              {data.top_converting_articles.map((a) => (
                <div key={a.article_slug} className="flex justify-between gap-2">
                  <span>{a.article_slug}</span>
                  <span className="text-muted">leads {a.leads} · qualified {a.qualified} · score {fmt(a.avg_score)}</span>
                </div>
              ))}
            </CardContent>
          </Card>
        </>
      )}

      <Card>
        <CardHeader><CardTitle className="text-base">Leads</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {!leads?.length && <p className="text-sm text-muted">Leadی نیست.</p>}
          {leads?.map((l) => (
            <div key={l.id} className="rounded-xl border border-card-border p-3 space-y-2 text-sm">
              <div className="flex flex-wrap gap-2 text-xs">
                <span className="px-2 py-0.5 rounded bg-muted">{l.status}</span>
                <span className="px-2 py-0.5 rounded bg-muted">{l.source}</span>
                <span className="px-2 py-0.5 rounded bg-muted">score {l.lead_score}</span>
                {l.is_duplicate && <span className="px-2 py-0.5 rounded bg-muted">duplicate</span>}
              </div>
              <p className="font-medium">{l.name || 'بدون نام'} · {l.mobile}</p>
              <p className="text-muted">{l.request_type} · {l.article_slug || '—'}</p>
              <div className="flex flex-wrap gap-2">
                {['CONTACTED', 'QUALIFIED', 'WON', 'LOST'].map((s) => (
                  <Button key={s} size="sm" variant="outline" onClick={() => statusMutation.mutate({ id: l.id, status: s })}>{s}</Button>
                ))}
                {['good', 'bad', 'wrong_intent', 'converted'].map((f) => (
                  <Button key={f} size="sm" variant="ghost" onClick={() => feedbackMutation.mutate({ id: l.id, quality_feedback: f })}>{f}</Button>
                ))}
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
