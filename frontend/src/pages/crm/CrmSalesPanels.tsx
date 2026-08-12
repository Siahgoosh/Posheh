import { useQuery } from '@tanstack/react-query'
import { AlertTriangle, Flame, CalendarClock, Handshake, Phone, Sparkles } from 'lucide-react'
import api from '@/lib/api'
import { formatJalaliDate, formatPrice } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { usePlanFeature } from '@/components/SubscriptionGuard'

interface QueueItem {
  kind: string
  priority: string
  at?: string
  title: string
  deal_id?: number
  customer_id?: number
  customer_name?: string
  score?: number
  id: number
}

interface QueueData {
  date: string
  summary: {
    hot_leads: number
    follow_ups: number
    visits: number
    negotiations: number
    overdue: number
  }
  hot_leads: Array<{ id: number; title: string; contact_name?: string; lead_score?: number }>
  queue: QueueItem[]
}

interface OpportunitiesData {
  hot_leads: Array<{ id: number; title: string; lead_score?: number; contact_name?: string; value?: number }>
  overdue_follow_ups: Array<{ id: number; title: string; follow_up_at?: string }>
  stalled_negotiations: Array<{ id: number; status: string; current_price?: number }>
  pending_offers: Array<{ id: number; amount: number; status: string }>
  reactivation: Array<{ id: number; name: string; mobile?: string; last_contacted_at?: string }>
}

const priorityStyle: Record<string, string> = {
  overdue: 'border-danger/40 bg-danger/5',
  hot: 'border-orange-500/40 bg-orange-500/5',
  normal: 'border-card-border bg-background/60',
}

export function CrmSalesQueuePanel() {
  const hasCrm = usePlanFeature('crm')

  const { data: briefing } = useQuery({
    queryKey: ['crm-briefing'],
    queryFn: async () => (await api.get('/crm/briefing')).data.data as { message: string },
    enabled: hasCrm,
  })

  const { data, isLoading } = useQuery({
    queryKey: ['crm-sales-queue'],
    queryFn: async () => (await api.get('/crm/sales-queue')).data.data as QueueData,
    enabled: hasCrm,
  })

  if (isLoading || !data) {
    return <div className="text-sm text-muted py-8 text-center">در حال بارگذاری صف فروش…</div>
  }

  return (
    <div className="space-y-4">
      <Card className="border-primary/20 bg-gradient-to-l from-primary/5 to-transparent">
        <CardContent className="pt-4 flex items-start gap-3">
          <Sparkles className="h-5 w-5 text-primary mt-0.5" />
          <div>
            <p className="font-semibold text-sm">صبح بخیر — خلاصه امروز</p>
            <p className="text-sm text-muted mt-1">{briefing?.message || data.summary && `🔥 ${data.summary.hot_leads} داغ · 📞 ${data.summary.follow_ups} پیگیری · 🏠 ${data.summary.visits} بازدید`}</p>
          </div>
        </CardContent>
      </Card>

      <div className="grid grid-cols-2 sm:grid-cols-5 gap-2">
        {[
          { label: 'سرنخ داغ', value: data.summary.hot_leads, icon: Flame },
          { label: 'پیگیری', value: data.summary.follow_ups, icon: Phone },
          { label: 'بازدید', value: data.summary.visits, icon: CalendarClock },
          { label: 'مذاکره', value: data.summary.negotiations, icon: Handshake },
          { label: 'معوق', value: data.summary.overdue, icon: AlertTriangle },
        ].map((s) => (
          <Card key={s.label} className="p-3">
            <p className="text-[10px] text-muted flex items-center gap-1"><s.icon className="h-3 w-3" />{s.label}</p>
            <p className="text-xl font-bold">{s.value}</p>
          </Card>
        ))}
      </div>

      <div className="space-y-2">
        <h3 className="text-sm font-semibold">صف کاری امروز</h3>
        {data.queue.length === 0 && (
          <p className="text-xs text-muted">موردی برای امروز نیست — عالی است!</p>
        )}
        {data.queue.map((item) => (
          <div
            key={`${item.kind}-${item.id}`}
            className={`rounded-xl border p-3 text-sm flex flex-wrap items-center justify-between gap-2 ${priorityStyle[item.priority] || priorityStyle.normal}`}
          >
            <div className="space-y-0.5">
              <div className="flex items-center gap-2">
                {item.priority === 'overdue' && <Badge variant="outline" className="text-danger border-danger text-[10px]">OVERDUE</Badge>}
                {item.priority === 'hot' && <Badge variant="outline" className="text-orange-600 border-orange-500/40 text-[10px]">HOT</Badge>}
                <span className="font-medium">{item.customer_name || '—'}</span>
                {item.score != null && <span className="text-[10px] text-warning">Score {item.score}</span>}
              </div>
              <p className="text-xs text-muted">{item.title}</p>
            </div>
            <div className="text-[11px] text-muted text-left" dir="ltr">
              {item.at ? formatJalaliDate(item.at) : ''}
            </div>
          </div>
        ))}
      </div>

      {data.hot_leads?.length > 0 && (
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><Flame className="h-4 w-4 text-orange-500" /> سرنخ‌های داغ</CardTitle></CardHeader>
          <CardContent className="flex flex-wrap gap-2">
            {data.hot_leads.map((d) => (
              <Badge key={d.id} variant="outline" className="text-xs">
                {d.contact_name || d.title} · {d.lead_score}
              </Badge>
            ))}
          </CardContent>
        </Card>
      )}
    </div>
  )
}

export function CrmOpportunitiesPanel() {
  const hasCrm = usePlanFeature('crm')
  const { data, isLoading } = useQuery({
    queryKey: ['crm-opportunities'],
    queryFn: async () => (await api.get('/crm/opportunities')).data.data as OpportunitiesData,
    enabled: hasCrm,
  })

  if (isLoading || !data) {
    return <div className="text-sm text-muted py-8 text-center">در حال بارگذاری فرصت‌ها…</div>
  }

  const Section = ({ title, children }: { title: string; children: React.ReactNode }) => (
    <Card>
      <CardHeader className="pb-2"><CardTitle className="text-sm">{title}</CardTitle></CardHeader>
      <CardContent className="space-y-2 text-sm">{children}</CardContent>
    </Card>
  )

  return (
    <div className="grid md:grid-cols-2 gap-4">
      <Section title="سرنخ‌های داغ">
        {data.hot_leads.length === 0 && <p className="text-xs text-muted">موردی نیست</p>}
        {data.hot_leads.map((d) => (
          <div key={d.id} className="flex justify-between border-b border-card-border pb-2">
            <span>{d.contact_name || d.title}</span>
            <span className="text-warning text-xs">Score {d.lead_score}{d.value ? ` · ${formatPrice(d.value)}` : ''}</span>
          </div>
        ))}
      </Section>
      <Section title="پیگیری‌های معوق">
        {data.overdue_follow_ups.length === 0 && <p className="text-xs text-muted">موردی نیست</p>}
        {data.overdue_follow_ups.map((d) => (
          <div key={d.id} className="flex justify-between border-b border-card-border pb-2 text-danger">
            <span>{d.title}</span>
            <span className="text-xs">{d.follow_up_at ? formatJalaliDate(d.follow_up_at) : ''}</span>
          </div>
        ))}
      </Section>
      <Section title="مذاکره‌های متوقف">
        {data.stalled_negotiations.length === 0 && <p className="text-xs text-muted">موردی نیست</p>}
        {data.stalled_negotiations.map((n) => (
          <div key={n.id} className="flex justify-between border-b border-card-border pb-2">
            <span>مذاکره #{n.id}</span>
            <span className="text-xs">{n.current_price ? formatPrice(n.current_price) : n.status}</span>
          </div>
        ))}
      </Section>
      <Section title="پیشنهادهای در انتظار">
        {data.pending_offers.length === 0 && <p className="text-xs text-muted">موردی نیست</p>}
        {data.pending_offers.map((o) => (
          <div key={o.id} className="flex justify-between border-b border-card-border pb-2">
            <span>Offer #{o.id}</span>
            <span className="text-xs">{formatPrice(o.amount)} · {o.status}</span>
          </div>
        ))}
      </Section>
      <Section title="فرصت‌های فعال‌سازی مجدد">
        {data.reactivation.length === 0 && <p className="text-xs text-muted">موردی نیست</p>}
        {data.reactivation.map((c) => (
          <div key={c.id} className="flex justify-between border-b border-card-border pb-2">
            <span>{c.name}</span>
            <span className="text-xs text-muted" dir="ltr">{c.mobile}</span>
          </div>
        ))}
      </Section>
    </div>
  )
}

export function CrmOffersPanel() {
  const hasCrm = usePlanFeature('crm')
  const { data: offers } = useQuery({
    queryKey: ['crm-offers'],
    queryFn: async () => (await api.get('/crm/offers')).data.data as Array<{
      id: number; amount: number; status: string; side: string; property?: { code?: string }; customer?: { name?: string }
    }>,
    enabled: hasCrm,
  })
  const { data: negotiations } = useQuery({
    queryKey: ['crm-negotiations'],
    queryFn: async () => (await api.get('/crm/negotiations')).data.data as Array<{
      id: number; status: string; current_price?: number; property?: { code?: string }; customer?: { name?: string }; offers?: unknown[]
    }>,
    enabled: hasCrm,
  })

  return (
    <div className="grid md:grid-cols-2 gap-4">
      <Card>
        <CardHeader className="pb-2"><CardTitle className="text-sm">مذاکره‌ها</CardTitle></CardHeader>
        <CardContent className="space-y-2 text-sm">
          {(negotiations ?? []).map((n) => (
            <div key={n.id} className="flex justify-between border-b border-card-border pb-2">
              <span>{n.customer?.name || '—'} · {n.property?.code}</span>
              <span className="text-xs">{n.status}{n.current_price ? ` · ${formatPrice(n.current_price)}` : ''}</span>
            </div>
          ))}
          {!negotiations?.length && <p className="text-xs text-muted">مذاکره‌ای ثبت نشده</p>}
        </CardContent>
      </Card>
      <Card>
        <CardHeader className="pb-2"><CardTitle className="text-sm">پیشنهادها (Offers)</CardTitle></CardHeader>
        <CardContent className="space-y-2 text-sm">
          {(offers ?? []).map((o) => (
            <div key={o.id} className="flex justify-between border-b border-card-border pb-2">
              <span>#{o.id} · {o.customer?.name || o.side} · {o.property?.code}</span>
              <span className="text-xs">{formatPrice(o.amount)} · {o.status}</span>
            </div>
          ))}
          {!offers?.length && <p className="text-xs text-muted">پیشنهادی ثبت نشده</p>}
        </CardContent>
      </Card>
    </div>
  )
}
