import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminListPage } from '@/components/admin/AdminListPage'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { formatPrice, formatNumber } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'

interface Row {
  id: number
  title?: string
  commission_amount?: number
  status?: string
  office?: { name: string }
  user?: { name: string }
}

interface KpiMonth {
  month: string
  label: string
  paid_total: number
  paid_count: number
  pending_total: number
  pending_count: number
}

export function AdminCommissionsPage() {
  const { data: kpi } = useQuery({
    queryKey: ['admin-commissions-kpi'],
    queryFn: async () => (await api.get('/admin/commissions/kpi')).data.data as {
      months: KpiMonth[]
      totals: { paid: number; pending: number }
    },
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="کمیسیون‌ها" description="گزارش KPI ماهانه و لیست کمیسیون‌ها" />

      {kpi && (
        <div className="grid sm:grid-cols-2 gap-4">
          <Card><CardContent className="pt-6"><p className="text-sm text-muted">پرداخت‌شده کل</p><p className="text-2xl font-bold text-success">{formatPrice(kpi.totals.paid)}</p></CardContent></Card>
          <Card><CardContent className="pt-6"><p className="text-sm text-muted">در انتظار</p><p className="text-2xl font-bold text-warning">{formatPrice(kpi.totals.pending)}</p></CardContent></Card>
        </div>
      )}

      {kpi?.months && (
        <Card>
          <CardHeader><CardTitle>KPI ماهانه (۶ ماه اخیر)</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {kpi.months.map((m) => (
              <div key={m.month} className="flex justify-between text-sm border-b border-card-border pb-2">
                <span>{m.label}</span>
                <span>
                  <Badge variant="outline" className="ml-2">{formatNumber(m.paid_count)} پرداخت</Badge>
                  {formatPrice(m.paid_total)}
                </span>
              </div>
            ))}
          </CardContent>
        </Card>
      )}

      <AdminListPage<Row>
        title="لیست کمیسیون‌ها"
        endpoint="/admin/commissions"
        queryKey="admin-commissions"
        renderRow={(c) => (
          <div className="flex justify-between border-b border-card-border pb-2 text-sm">
            <div>
              <span className="font-medium">{c.title ?? `کمیسیون #${c.id}`}</span>
              <Badge variant="outline" className="mr-2">{c.office?.name}</Badge>
            </div>
            <span>{formatPrice(c.commission_amount ?? 0)} · {c.status}</span>
          </div>
        )}
      />
    </div>
  )
}
