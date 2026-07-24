import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { formatNumber, formatPrice } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

export function AdminPaymentsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-payments'],
    queryFn: async () => {
      const res = await api.get('/admin/payments')
      return {
        payments: res.data.data as { id: number; amount: number; status: string; gateway: string; paid_at?: string; office?: { name: string }; ref_id?: string }[],
        total_revenue: res.data.total_revenue as number,
        monthly_revenue: res.data.monthly_revenue as number,
        paid_count: res.data.paid_count as number,
        failed_count: res.data.failed_count as number,
      }
    },
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="پرداخت‌ها و درآمد" description="تراکنش‌های پلتفرم" />

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card className="glass"><CardContent className="pt-6"><p className="text-sm text-muted">درآمد کل</p><p className="text-xl font-bold">{formatPrice(data?.total_revenue ?? 0)}</p></CardContent></Card>
        <Card className="glass"><CardContent className="pt-6"><p className="text-sm text-muted">این ماه</p><p className="text-xl font-bold">{formatPrice(data?.monthly_revenue ?? 0)}</p></CardContent></Card>
        <Card className="glass"><CardContent className="pt-6"><p className="text-sm text-muted">موفق</p><p className="text-xl font-bold">{formatNumber(data?.paid_count ?? 0)}</p></CardContent></Card>
        <Card className="glass"><CardContent className="pt-6"><p className="text-sm text-muted">ناموفق</p><p className="text-xl font-bold">{formatNumber(data?.failed_count ?? 0)}</p></CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>تراکنش‌ها</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : data?.payments?.map((p) => (
            <div key={p.id} className="flex justify-between gap-2 text-sm border-b border-card-border pb-2">
              <span>{p.office?.name ?? '—'} · {formatPrice(p.amount)}</span>
              <span className="text-muted">{p.gateway} · <Badge variant="outline">{p.status}</Badge> · {p.ref_id ?? '—'}</span>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
