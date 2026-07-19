import { useQuery } from '@tanstack/react-query'
import { BarChart3, Building2, Kanban, Wallet, TrendingUp, Users, Percent, Calendar } from 'lucide-react'
import api from '@/lib/api'
import { formatNumber, formatPrice } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

function BarChart({ items, valueKey, labelKey, maxValue }: {
  items: { [key: string]: string | number }[]
  valueKey: string
  labelKey: string
  maxValue?: number
}) {
  const max = maxValue ?? Math.max(...items.map((i) => Number(i[valueKey]) || 0), 1)

  return (
    <div className="space-y-3">
      {items.map((item) => {
        const val = Number(item[valueKey]) || 0
        const pct = max > 0 ? (val / max) * 100 : 0
        return (
          <div key={String(item[labelKey])}>
            <div className="flex justify-between text-sm mb-1">
              <span>{item[labelKey]}</span>
              <span className="text-muted">{typeof val === 'number' && val > 1000 ? formatPrice(val) : formatNumber(val)}</span>
            </div>
            <div className="h-2.5 rounded-full bg-white/5 overflow-hidden">
              <div className="h-full rounded-full bg-gradient-to-l from-primary to-accent transition-all duration-500" style={{ width: `${pct}%` }} />
            </div>
          </div>
        )
      })}
    </div>
  )
}

export function ReportsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['reports'],
    queryFn: async () => (await api.get('/reports/dashboard')).data.data,
  })

  if (isLoading || !data) {
    return (
      <div className="flex justify-center py-20">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  const maxTrend = Math.max(...(data.accounting.monthly_trend?.map((m: { income: number; expense: number }) => Math.max(m.income, m.expense)) ?? [1]))

  return (
    <div className="space-y-6 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <BarChart3 className="h-6 w-6 text-primary" /> گزارش‌های KPI
        </h1>
        <p className="text-sm text-muted mt-1">عملکرد دفتر، مشاوران و قیف فروش</p>
      </div>

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card><CardContent className="pt-6">
          <Building2 className="h-5 w-5 text-primary mb-2" />
          <p className="text-2xl font-bold">{formatNumber(data.properties.active)}</p>
          <p className="text-sm text-muted">ملک فعال از {formatNumber(data.properties.total)}</p>
        </CardContent></Card>
        <Card><CardContent className="pt-6">
          <Kanban className="h-5 w-5 text-primary mb-2" />
          <p className="text-2xl font-bold">{formatNumber(data.crm.open_deals)}</p>
          <p className="text-sm text-muted">معامله باز</p>
        </CardContent></Card>
        <Card><CardContent className="pt-6">
          <Percent className="h-5 w-5 text-accent mb-2" />
          <p className="text-2xl font-bold text-accent">{data.crm.conversion_rate}%</p>
          <p className="text-sm text-muted">نرخ تبدیل</p>
        </CardContent></Card>
        <Card><CardContent className="pt-6">
          <Wallet className="h-5 w-5 text-primary mb-2" />
          <p className="text-2xl font-bold">{formatPrice(data.accounting.month_income)}</p>
          <p className="text-sm text-muted">درآمد ماه جاری</p>
        </CardContent></Card>
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader><CardTitle className="text-base flex items-center gap-2"><TrendingUp className="h-4 w-4" /> روند مالی ۶ ماه</CardTitle></CardHeader>
          <CardContent>
            <BarChart
              items={(data.accounting.monthly_trend ?? []).map((m: { label: string; income: number }) => ({
                label: m.label,
                value: m.income,
              }))}
              labelKey="label"
              valueKey="value"
              maxValue={maxTrend}
            />
            <div className="flex gap-4 mt-4 text-xs text-muted">
              <span className="flex items-center gap-1"><span className="w-3 h-3 rounded bg-primary" /> درآمد</span>
              <span>هزینه ماه: {formatPrice(data.accounting.month_expense)}</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle className="text-base flex items-center gap-2"><Kanban className="h-4 w-4" /> پایپ‌لاین فروش</CardTitle></CardHeader>
          <CardContent>
            <BarChart
              items={(data.crm.pipeline ?? []).map((p: { label: string; count: number }) => ({
                label: p.label,
                value: p.count,
              }))}
              labelKey="label"
              valueKey="value"
            />
            <p className="text-sm text-muted mt-4">ارزش معاملات موفق: {formatPrice(data.crm.won_value)}</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-base flex items-center gap-2"><Users className="h-4 w-4" /> عملکرد مشاوران</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-muted border-b border-card-border">
                  <th className="text-right py-2 font-medium">مشاور</th>
                  <th className="text-center py-2 font-medium">املاک</th>
                  <th className="text-center py-2 font-medium">موفق</th>
                  <th className="text-center py-2 font-medium">باز</th>
                  <th className="text-left py-2 font-medium">کمیسیون معوق</th>
                </tr>
              </thead>
              <tbody>
                {(data.consultants ?? []).map((c: {
                  id: number; name: string; properties: number; deals_won: number; open_deals: number; commission_pending: number
                }) => (
                  <tr key={c.id} className="border-b border-card-border/50">
                    <td className="py-3 font-medium">{c.name}</td>
                    <td className="text-center">{formatNumber(c.properties)}</td>
                    <td className="text-center text-success">{formatNumber(c.deals_won)}</td>
                    <td className="text-center">{formatNumber(c.open_deals)}</td>
                    <td className="text-left text-accent">{formatPrice(c.commission_pending)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <div className="grid sm:grid-cols-3 gap-4">
        <Card className="p-4">
          <p className="text-sm text-muted">کمیسیون معوق</p>
          <p className="text-xl font-bold text-accent mt-1">{formatPrice(data.commissions.pending)}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-muted">کمیسیون پرداخت‌شده (ماه)</p>
          <p className="text-xl font-bold text-success mt-1">{formatPrice(data.commissions.paid_month)}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-muted flex items-center gap-1"><Calendar className="h-3.5 w-3.5" /> بازدید این هفته</p>
          <p className="text-xl font-bold mt-1">{formatNumber(data.visits.this_week)}</p>
        </Card>
      </div>

      <Card>
        <CardHeader><CardTitle className="text-base">توزیع نوع ملک</CardTitle></CardHeader>
        <CardContent>
          <BarChart
            items={Object.entries(data.by_type || {}).map(([type, count]) => ({
              label: type,
              value: count as number,
            }))}
            labelKey="label"
            valueKey="value"
          />
        </CardContent>
      </Card>

      {data.demand_heatmap && (
        <div className="grid lg:grid-cols-2 gap-6">
          <Card>
            <CardHeader><CardTitle className="text-base">نقشه عرضه (محله)</CardTitle></CardHeader>
            <CardContent>
              <BarChart
                items={(data.demand_heatmap.supply_by_district ?? []).map((r: { area: string; listings: number }) => ({
                  label: r.area,
                  value: r.listings,
                }))}
                labelKey="label"
                valueKey="value"
              />
            </CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle className="text-base">نقشه تقاضا (شهر مشتری)</CardTitle></CardHeader>
            <CardContent>
              <BarChart
                items={(data.demand_heatmap.demand_by_city ?? []).map((r: { city: string; seekers: number }) => ({
                  label: r.city,
                  value: r.seekers,
                }))}
                labelKey="label"
                valueKey="value"
              />
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  )
}
