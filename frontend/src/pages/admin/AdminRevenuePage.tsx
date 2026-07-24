import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { formatNumber, formatPrice } from '@/lib/utils'

export function AdminRevenuePage() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-revenue'],
    queryFn: async () => (await api.get('/admin/platform/revenue')).data.data,
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="درآمدها" description="MRR تقریبی و روند ماهانه" />
      {isLoading ? <p className="text-muted">بارگذاری…</p> : (
        <>
          <div className="grid gap-4 sm:grid-cols-2">
            <Card className="glass"><CardContent className="pt-6">
              <p className="text-sm text-muted">MRR (۳۰ روز)</p>
              <p className="text-2xl font-bold">{formatPrice(data?.mrr_estimate ?? 0)}</p>
            </CardContent></Card>
            <Card className="glass"><CardContent className="pt-6">
              <p className="text-sm text-muted">میانگین پرداخت</p>
              <p className="text-2xl font-bold">{formatPrice(data?.avg_payment ?? 0)}</p>
            </CardContent></Card>
          </div>
          <Card>
            <CardHeader><CardTitle>درآمد ماهانه</CardTitle></CardHeader>
            <CardContent className="space-y-2">
              {(data?.monthly ?? []).map((m: { month: string; label: string; revenue: number; count: number }) => (
                <div key={m.month} className="flex justify-between text-sm border-b border-card-border pb-2">
                  <span>{m.label ?? m.month}</span>
                  <span>{formatPrice(m.revenue)} · {formatNumber(m.count)} پرداخت</span>
                </div>
              ))}
            </CardContent>
          </Card>
        </>
      )}
    </div>
  )
}
