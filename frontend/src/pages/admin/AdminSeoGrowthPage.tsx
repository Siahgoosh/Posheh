import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, RefreshCw, ShieldAlert } from 'lucide-react'
import api from '@/lib/api'
import { adminPath } from '@/lib/adminPaths'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { PORTFOLIO_FA, SEARCH_INTENT_FA, labelFa, unknownFa } from '@/lib/blogLabelsFa'

type Priority = {
  id: number
  problem: string
  evidence: string
  recommendation: string
  expected_benefit?: string
  risk: string
  effort: string
  priority: string
  priority_score: number
  status: string
  action_type: string
  automation_level: string
}

type Executive = {
  data_quality: { gsc_status: string; message: string }
  kpis: Record<string, number | null>
  content_health?: {
    healthy: number
    needs_update: number
    critical: number
    orphan_risk: number
    portfolio: Record<string, number>
  } | null
  top_priorities: Priority[]
  alerts: { id: number; severity: string; title: string; detail?: string }[]
  topics: { topic: string; topic_score: number; article_count: number; has_pillar: boolean }[]
  top_queries: { query_raw: string; impressions_28d: number; clicks_28d: number; position_28d?: number; intent?: string }[]
  forecast: { note: string; value: null }
}

function fmt(v: number | null | undefined) {
  return unknownFa(v)
}

export function AdminSeoGrowthPage() {
  const qc = useQueryClient()
  const { data, isLoading } = useQuery({
    queryKey: ['seo-growth'],
    queryFn: async () => (await api.get('/admin/seo/growth')).data.data as Executive,
  })

  const collectMutation = useMutation({
    mutationFn: () => api.post('/admin/seo/collect-gsc'),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['seo-growth'] }),
  })
  const analyzeMutation = useMutation({
    mutationFn: () => api.post('/admin/seo/analyze'),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['seo-growth'] }),
  })
  const approveMutation = useMutation({
    mutationFn: (id: number) => api.post(`/admin/seo/recommendations/${id}/approve`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['seo-growth'] }),
  })
  const executeMutation = useMutation({
    mutationFn: (id: number) => api.post(`/admin/seo/recommendations/${id}/execute`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['seo-growth'] }),
  })
  const rejectMutation = useMutation({
    mutationFn: (id: number) => api.post(`/admin/seo/recommendations/${id}/reject`, { notes: 'رد از داشبورد' }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['seo-growth'] }),
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="icon" onClick={() => window.history.back()}>
            <ArrowRight className="h-5 w-5" />
          </Button>
          <div>
            <h1 className="text-2xl font-bold">موتور رشد سئو</h1>
            <p className="text-sm text-muted">اولویت‌های واقعی — بدون داده جعلی و بدون تغییر خودکار خطرناک</p>
          </div>
        </div>
        <div className="flex gap-2">
          <Link to={adminPath('blog')}><Button variant="outline">وبلاگ</Button></Link>
          <Button variant="outline" onClick={() => collectMutation.mutate()} disabled={collectMutation.isPending}>
            <RefreshCw className="h-4 w-4" /> جمع‌آوری کنسول جستجو
          </Button>
          <Button onClick={() => analyzeMutation.mutate()} disabled={analyzeMutation.isPending}>تحلیل فرصت‌ها</Button>
        </div>
      </div>

      {isLoading && <p className="text-muted">در حال بارگذاری…</p>}

      {data && (
        <>
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <ShieldAlert className="h-4 w-4" /> کیفیت داده
              </CardTitle>
            </CardHeader>
            <CardContent className="text-sm space-y-1">
              <p>وضعیت کنسول جستجو: <strong>{data.data_quality.gsc_status}</strong></p>
              <p className="text-muted">{data.data_quality.message}</p>
              <p className="text-muted">{data.forecast.note}</p>
            </CardContent>
          </Card>

          <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {[
              ['کلیک ۲۸ روز', data.kpis.organic_clicks_28d],
              ['نمایش ۲۸ روز', data.kpis.impressions_28d],
              ['نرخ کلیک', data.kpis.ctr_28d],
              ['میانگین جایگاه', data.kpis.avg_position_28d],
              ['منتشر شده', data.kpis.indexed_published],
              ['پیش‌نویس', data.kpis.drafts],
            ].map(([label, value]) => (
              <Card key={String(label)}>
                <CardContent className="pt-4">
                  <p className="text-xs text-muted">{label}</p>
                  <p className="text-xl font-bold mt-1">{fmt(value as number | null)}</p>
                </CardContent>
              </Card>
            ))}
          </div>

          {data.content_health && (
            <div className="grid sm:grid-cols-2 lg:grid-cols-5 gap-3">
              {Object.entries(data.content_health.portfolio).map(([k, v]) => (
                <Card key={k}>
                  <CardContent className="pt-4">
                    <p className="text-xs text-muted">{labelFa(PORTFOLIO_FA, k)}</p>
                    <p className="text-xl font-bold mt-1">{v}</p>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}

          <Card>
            <CardHeader>
              <CardTitle className="text-base">اولویت‌های برتر (حداکثر ۱۰)</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {!data.top_priorities?.length && (
                <p className="text-sm text-muted">پیشنهادی نیست — یا داده کنسول نیست یا هنوز تحلیل اجرا نشده.</p>
              )}
              {data.top_priorities?.map((p) => (
                <div key={p.id} className="rounded-xl border border-card-border p-4 space-y-2">
                  <div className="flex flex-wrap items-center gap-2 text-xs">
                    <span className="px-2 py-0.5 rounded bg-muted">{p.priority}</span>
                    <span className="px-2 py-0.5 rounded bg-muted">{p.status}</span>
                    <span className="px-2 py-0.5 rounded bg-muted">{p.action_type}</span>
                    <span className="text-muted">امتیاز {p.priority_score}</span>
                  </div>
                  <p className="font-medium">{p.problem}</p>
                  <p className="text-sm"><span className="text-muted">شواهد:</span> {p.evidence}</p>
                  <p className="text-sm"><span className="text-muted">پیشنهاد:</span> {p.recommendation}</p>
                  <p className="text-sm text-muted">سود مورد انتظار: {p.expected_benefit || '—'} · ریسک: {p.risk} · تلاش: {p.effort} · خودکارسازی: {p.automation_level}</p>
                  <div className="flex flex-wrap gap-2 pt-1">
                    {(p.status === 'NEW' || p.status === 'REVIEWED') && (
                      <>
                        <Button size="sm" onClick={() => approveMutation.mutate(p.id)}>تأیید</Button>
                        <Button size="sm" variant="outline" onClick={() => rejectMutation.mutate(p.id)}>رد</Button>
                      </>
                    )}
                    {p.status === 'APPROVED' && (
                      <Button size="sm" onClick={() => executeMutation.mutate(p.id)}>اجرای امن</Button>
                    )}
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>

          <div className="grid lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader><CardTitle className="text-base">هشدارها</CardTitle></CardHeader>
              <CardContent className="space-y-2 text-sm">
                {!data.alerts?.length && <p className="text-muted">هشدار بازی نیست.</p>}
                {data.alerts?.map((a) => (
                  <div key={a.id} className="border-b border-card-border pb-2">
                    <p className="font-medium">[{a.severity}] {a.title}</p>
                    {a.detail && <p className="text-muted">{a.detail}</p>}
                  </div>
                ))}
              </CardContent>
            </Card>
            <Card>
              <CardHeader><CardTitle className="text-base">موضوعات</CardTitle></CardHeader>
              <CardContent className="space-y-2 text-sm">
                {!data.topics?.length && <p className="text-muted">بعد از تحلیل پر می‌شود.</p>}
                {data.topics?.map((t) => (
                  <div key={t.topic} className="flex justify-between gap-2">
                    <span>{t.topic} {t.has_pillar ? '· دارای ستون' : '· شکاف'}</span>
                    <span className="text-muted">{t.article_count} مقاله · امتیاز {t.topic_score}</span>
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader><CardTitle className="text-base">برترین پرس‌وجوها (داده واقعی)</CardTitle></CardHeader>
            <CardContent className="space-y-2 text-sm">
              {!data.top_queries?.length && <p className="text-muted">نامشخص تا وقتی همگام‌سازی کنسول جستجو انجام شود.</p>}
              {data.top_queries?.map((q) => (
                <div key={q.query_raw} className="flex flex-wrap justify-between gap-2 border-b border-card-border pb-2">
                  <span>{q.query_raw}</span>
                  <span className="text-muted">
                    نمایش {q.impressions_28d} · کلیک {q.clicks_28d} · جایگاه {fmt(q.position_28d)} · {labelFa(SEARCH_INTENT_FA, q.intent)}
                  </span>
                </div>
              ))}
            </CardContent>
          </Card>
        </>
      )}
    </div>
  )
}
