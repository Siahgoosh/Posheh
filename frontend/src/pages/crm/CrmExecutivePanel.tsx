import { useQuery } from '@tanstack/react-query'
import {
  AlertTriangle, BarChart3, Flame, Gauge, Sparkles, TrendingDown, TrendingUp, Users, Wallet,
} from 'lucide-react'
import { useState } from 'react'
import api from '@/lib/api'
import { formatNumber, formatPrice } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { usePlanFeature } from '@/components/SubscriptionGuard'

type Period = 'today' | 'this_week' | 'this_month' | 'this_year'

interface ExecData {
  period: string
  kpis: Record<string, number>
  comparison: Record<string, { value: number; previous: number; delta_percent: number }>
  funnel: Array<{
    stage: string; label: string; count: number
    conversion_percent: number; drop_off_percent: number; average_duration_days: number
  }>
  bottlenecks: Array<{ severity: string; message: string; conversion_percent: number }>
  forecast: {
    pipeline_value: number
    weighted_pipeline: number
    expected_commission: number
    open_deals: number
    near_close: Array<{ id: number; title: string; value?: number; probability?: number }>
  }
  briefing?: { message?: string }
  opportunities_summary: Record<string, number>
  agents: Array<{
    agent_id: number; name: string; leads: number; won_deals: number
    revenue: number; conversion_rate: number; performance_score: number
    avg_response_minutes?: number | null
  }>
  sources: Array<{ source: string; leads: number; deals: number; revenue: number; conversion_rate: number }>
  top_properties: Array<{
    id: number; code: string; demand_score: number; health_status: string; days_on_market: number; price?: number
  }>
}

const PERIODS: { key: Period; label: string }[] = [
  { key: 'today', label: 'امروز' },
  { key: 'this_week', label: 'این هفته' },
  { key: 'this_month', label: 'این ماه' },
  { key: 'this_year', label: 'امسال' },
]

const KPI_LABELS: Record<string, string> = {
  new_leads: 'سرنخ جدید',
  hot_leads: 'سرنخ داغ',
  viewings: 'بازدید',
  offers: 'پیشنهاد',
  won_deals: 'معاملات موفق',
  revenue: 'درآمد معاملات',
  conversion_rate: 'نرخ تبدیل',
  average_deal_value: 'میانگین معامله',
  commission_collected: 'کمیسیون وصول',
  open_deals: 'معاملات باز',
}

function Delta({ pct }: { pct: number }) {
  if (pct === 0) return <span className="text-[10px] text-muted">۰٪</span>
  const up = pct > 0
  return (
    <span className={`text-[10px] flex items-center gap-0.5 ${up ? 'text-emerald-600' : 'text-danger'}`}>
      {up ? <TrendingUp className="h-3 w-3" /> : <TrendingDown className="h-3 w-3" />}
      {up ? '+' : ''}{pct}%
    </span>
  )
}

const healthLabel: Record<string, string> = {
  high_demand: '🔥 تقاضای بالا',
  healthy: '🟢 سالم',
  low_interest: '🟡 کم‌علاقه',
  stale: '🟠 خوابیده',
  critical: '🔴 بحرانی',
}

export function CrmExecutivePanel() {
  const hasCrm = usePlanFeature('crm')
  const [period, setPeriod] = useState<Period>('this_month')

  const { data, isLoading, isError } = useQuery({
    queryKey: ['crm-executive', period],
    queryFn: async () => (await api.get('/crm/executive', { params: { period } })).data.data as ExecData,
    enabled: hasCrm,
  })

  const { data: quality } = useQuery({
    queryKey: ['crm-data-quality'],
    queryFn: async () => (await api.get('/crm/data-quality')).data.data as {
      health_score: number
      issues: Array<{ key: string; label: string; count: number }>
    },
    enabled: hasCrm,
  })

  if (isLoading) {
    return <div className="text-sm text-muted py-10 text-center">در حال بارگذاری داشبورد مدیریتی…</div>
  }
  if (isError || !data) {
    return <div className="text-sm text-danger py-10 text-center">خطا در بارگذاری Executive Dashboard</div>
  }

  const maxFunnel = Math.max(...data.funnel.map((f) => f.count), 1)
  const highlightKeys = ['new_leads', 'hot_leads', 'won_deals', 'revenue', 'conversion_rate', 'viewings', 'offers', 'open_deals']

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-bold flex items-center gap-2">
            <Gauge className="h-5 w-5 text-primary" /> Executive Dashboard
          </h2>
          <p className="text-xs text-muted mt-0.5">{data.briefing?.message}</p>
        </div>
        <div className="flex flex-wrap gap-1">
          {PERIODS.map((p) => (
            <Button key={p.key} size="sm" variant={period === p.key ? 'default' : 'outline'} onClick={() => setPeriod(p.key)}>
              {p.label}
            </Button>
          ))}
        </div>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
        {highlightKeys.map((key) => {
          const cmp = data.comparison[key]
          const raw = data.kpis[key] ?? cmp?.value ?? 0
          const isMoney = ['revenue', 'average_deal_value', 'commission_collected', 'commission_pending'].includes(key)
          const isPct = key === 'conversion_rate'
          return (
            <Card key={key} className="p-3">
              <p className="text-[10px] text-muted">{KPI_LABELS[key] || key}</p>
              <p className="text-lg font-bold">
                {isMoney ? formatPrice(raw) : isPct ? `${raw}%` : formatNumber(raw)}
              </p>
              {cmp && <Delta pct={cmp.delta_percent} />}
            </Card>
          )
        })}
      </div>

      <div className="grid lg:grid-cols-3 gap-4">
        <Card className="lg:col-span-2">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm flex items-center gap-2"><BarChart3 className="h-4 w-4" /> قیف فروش</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {data.funnel.map((f) => (
              <div key={f.stage}>
                <div className="flex justify-between text-xs mb-1">
                  <span className="font-medium">{f.label}</span>
                  <span className="text-muted">
                    {formatNumber(f.count)} · تبدیل {f.conversion_percent}% · افت {f.drop_off_percent}% · {f.average_duration_days} روز
                  </span>
                </div>
                <div className="h-2.5 rounded-full bg-white/5 overflow-hidden">
                  <div className="h-full rounded-full bg-gradient-to-l from-primary to-accent" style={{ width: `${(f.count / maxFunnel) * 100}%` }} />
                </div>
              </div>
            ))}
            {data.bottlenecks?.length > 0 && (
              <div className="mt-3 space-y-2">
                {data.bottlenecks.map((b, i) => (
                  <div key={i} className="text-xs rounded-lg border border-warning/40 bg-warning/5 p-2 flex gap-2">
                    <AlertTriangle className="h-4 w-4 text-warning shrink-0" />
                    <span>{b.message}</span>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm flex items-center gap-2"><Wallet className="h-4 w-4" /> پیش‌بینی Pipeline</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3 text-sm">
            <div className="flex justify-between"><span className="text-muted">Pipeline</span><span className="font-bold">{formatPrice(data.forecast.pipeline_value)}</span></div>
            <div className="flex justify-between"><span className="text-muted">Weighted</span><span className="font-bold text-primary">{formatPrice(data.forecast.weighted_pipeline)}</span></div>
            <div className="flex justify-between"><span className="text-muted">کمیسیون انتظاری</span><span className="font-bold">{formatPrice(data.forecast.expected_commission)}</span></div>
            <div className="flex justify-between"><span className="text-muted">معاملات باز</span><span>{formatNumber(data.forecast.open_deals)}</span></div>
            <div className="border-t border-card-border pt-2 space-y-1">
              <p className="text-[10px] text-muted">نزدیک به بسته‌شدن</p>
              {(data.forecast.near_close ?? []).slice(0, 5).map((d) => (
                <div key={d.id} className="flex justify-between text-xs">
                  <span className="truncate">{d.title}</span>
                  <span>{d.probability ?? '—'}%{d.value ? ` · ${formatPrice(d.value)}` : ''}</span>
                </div>
              ))}
              {!data.forecast.near_close?.length && <p className="text-xs text-muted">موردی نیست</p>}
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid md:grid-cols-3 gap-4">
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><Users className="h-4 w-4" /> عملکرد مشاوران</CardTitle></CardHeader>
          <CardContent className="space-y-2 text-sm">
            {(data.agents ?? []).slice(0, 8).map((a) => (
              <div key={a.agent_id} className="flex justify-between border-b border-card-border pb-1.5">
                <div>
                  <p className="font-medium text-xs">{a.name}</p>
                  <p className="text-[10px] text-muted">تبدیل {a.conversion_rate}% · {a.won_deals} معامله</p>
                </div>
                <Badge variant="outline" className="text-[10px]">Score {a.performance_score}</Badge>
              </div>
            ))}
            {!data.agents?.length && <p className="text-xs text-muted">فقط مدیر دفتر مقایسه تیم را می‌بیند</p>}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm">هوش منبع Lead</CardTitle></CardHeader>
          <CardContent className="space-y-2 text-sm">
            {(data.sources ?? []).slice(0, 8).map((s) => (
              <div key={s.source} className="flex justify-between border-b border-card-border pb-1.5 text-xs">
                <span>{s.source}</span>
                <span className="text-muted">{s.leads} Lead · {s.deals} Deal · {formatPrice(s.revenue)}</span>
              </div>
            ))}
            {!data.sources?.length && <p className="text-xs text-muted">داده‌ای نیست</p>}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><Flame className="h-4 w-4" /> تقاضای فایل‌ها</CardTitle></CardHeader>
          <CardContent className="space-y-2 text-sm">
            {(data.top_properties ?? []).map((p) => (
              <div key={p.id} className="flex justify-between border-b border-card-border pb-1.5 text-xs">
                <div>
                  <p className="font-medium">{p.code}</p>
                  <p className="text-[10px] text-muted">{healthLabel[p.health_status] || p.health_status} · {p.days_on_market} روز</p>
                </div>
                <span className="text-primary font-bold">{p.demand_score}</span>
              </div>
            ))}
            {!data.top_properties?.length && <p className="text-xs text-muted">فایل فعالی نیست</p>}
          </CardContent>
        </Card>
      </div>

      <div className="grid md:grid-cols-2 gap-4">
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><Sparkles className="h-4 w-4" /> فرصت‌های فعال</CardTitle></CardHeader>
          <CardContent className="grid grid-cols-2 gap-2 text-xs">
            {Object.entries(data.opportunities_summary || {}).map(([k, v]) => (
              <div key={k} className="rounded-lg border border-card-border p-2">
                <p className="text-muted">{k}</p>
                <p className="text-lg font-bold">{v}</p>
              </div>
            ))}
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm">سلامت داده CRM</CardTitle></CardHeader>
          <CardContent className="space-y-2 text-sm">
            <p className="text-2xl font-bold text-primary">{quality?.health_score ?? '—'}%</p>
            {(quality?.issues ?? []).map((i) => (
              <div key={i.key} className="flex justify-between text-xs border-b border-card-border pb-1">
                <span>{i.label}</span>
                <span className={i.count > 0 ? 'text-warning' : 'text-muted'}>{i.count}</span>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}

export function CrmAiAssistantPanel() {
  const hasCrm = usePlanFeature('crm')
  const [purpose, setPurpose] = useState('followup')
  const [name, setName] = useState('آقای احمدی')
  const [message, setMessage] = useState<string | null>(null)

  const { data: usage } = useQuery({
    queryKey: ['crm-ai-usage'],
    queryFn: async () => (await api.get('/crm/ai/usage')).data.data,
    enabled: hasCrm,
  })

  const generate = async () => {
    const res = await api.post('/crm/ai/message', { purpose, customer_name: name })
    setMessage(res.data.data.message)
  }

  return (
    <div className="space-y-4 max-w-2xl">
      <Card>
        <CardHeader className="pb-2">
          <CardTitle className="text-sm flex items-center gap-2"><Sparkles className="h-4 w-4 text-primary" /> دستیار پیام (Rule-Based / AI-ready)</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3 text-sm">
          <p className="text-xs text-muted">بدون ارسال داده به سرویس خارجی. فقط از حقایق ورودی استفاده می‌شود.</p>
          <select className="w-full rounded-xl border border-card-border bg-background/50 p-2" value={purpose} onChange={(e) => setPurpose(e.target.value)}>
            <option value="followup">پیگیری</option>
            <option value="intro">معرفی فایل</option>
            <option value="viewing">دعوت بازدید</option>
            <option value="after_visit">بعد از بازدید</option>
            <option value="negotiation">مذاکره</option>
          </select>
          <input className="w-full rounded-xl border border-card-border bg-background/50 p-2" value={name} onChange={(e) => setName(e.target.value)} placeholder="نام مشتری" />
          <Button onClick={generate}>تولید پیام</Button>
          {message && <div className="rounded-xl border border-card-border p-3 text-sm whitespace-pre-wrap">{message}</div>}
          {usage && (
            <p className="text-[10px] text-muted">مصرف AI این ماه: {usage.total_requests ?? 0} درخواست (local/rule-based)</p>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
