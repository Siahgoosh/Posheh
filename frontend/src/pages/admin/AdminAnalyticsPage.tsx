import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { formatNumber, formatPrice } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'

export function AdminAnalyticsPage() {
  const overview = useQuery({
    queryKey: ['admin-platform-overview'],
    queryFn: async () => (await api.get('/admin/platform/overview')).data.data,
  })
  const analytics = useQuery({
    queryKey: ['admin-analytics'],
    queryFn: async () => (await api.get('/admin/analytics')).data,
  })

  const o = overview.data
  const a = analytics.data

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="تحلیل‌های پلتفرم" description="آمار cross-tenant" />
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {[
          ['دفاتر', o?.counts?.offices],
          ['کاربران', o?.counts?.users],
          ['مشتریان', o?.counts?.customers],
          ['املاک', o?.counts?.properties],
          ['CRM', o?.counts?.crm_deals],
          ['قرارداد', o?.counts?.contracts],
          ['دستگاه', o?.counts?.devices],
          ['درآمد کل', o?.revenue?.total ? formatPrice(o.revenue.total) : '—'],
        ].map(([label, val]) => (
          <Card key={String(label)} className="glass">
            <CardContent className="pt-6">
              <p className="text-sm text-muted">{label}</p>
              <p className="text-xl font-bold">{typeof val === 'number' ? formatNumber(val) : val}</p>
            </CardContent>
          </Card>
        ))}
      </div>
      {a && (
        <Card>
          <CardHeader><CardTitle>خلاصه سریع</CardTitle></CardHeader>
          <CardContent className="text-sm space-y-1">
            <p>دفاتر فعال: {formatNumber(a.active_offices ?? 0)} / {formatNumber(a.total_offices ?? 0)}</p>
            <p>درآمد ماه: {formatPrice(a.monthly_revenue ?? 0)}</p>
          </CardContent>
        </Card>
      )}
      {o?.revenue?.by_gateway && (
        <Card>
          <CardHeader><CardTitle>درآمد به تفکیک درگاه</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {o.revenue.by_gateway.map((g: { gateway: string; total: number; count: number }) => (
              <div key={g.gateway} className="flex justify-between text-sm">
                <Badge variant="outline">{g.gateway}</Badge>
                <span>{formatPrice(g.total)} · {g.count}</span>
              </div>
            ))}
          </CardContent>
        </Card>
      )}
    </div>
  )
}
