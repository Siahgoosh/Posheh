import { useQuery } from '@tanstack/react-query'
import { DollarSign } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { AdminNav } from '@/components/admin/AdminNav'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

export function AdminFinancePage() {
  const { data: analytics } = useQuery({
    queryKey: ['admin-analytics'],
    queryFn: async () => (await api.get('/admin/analytics')).data,
  })

  const { data: payments, isLoading } = useQuery({
    queryKey: ['admin-payments'],
    queryFn: async () => (await api.get('/admin/payments')).data,
  })

  return (
    <div className="space-y-4 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <DollarSign className="h-6 w-6 text-primary" />
          گزارش مالی
        </h1>
        <p className="text-muted mt-1">درآمد و پرداخت‌های پلتفرم</p>
      </div>

      <AdminNav />

      <div className="grid sm:grid-cols-2 gap-4">
        <Card className="glass">
          <CardContent className="p-4">
            <p className="text-sm text-muted">درآمد کل</p>
            <p className="text-2xl font-bold">{formatPrice(analytics?.total_revenue ?? 0)}</p>
          </CardContent>
        </Card>
        <Card className="glass">
          <CardContent className="p-4">
            <p className="text-sm text-muted">درآمد این ماه</p>
            <p className="text-2xl font-bold">{formatPrice(analytics?.monthly_revenue ?? 0)}</p>
          </CardContent>
        </Card>
      </div>

      <h2 className="text-lg font-semibold">تراکنش‌ها</h2>
      {isLoading ? (
        <div className="flex justify-center py-12"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
      ) : (
        <div className="space-y-2">
          {payments?.data?.map((p: {
            id: number
            amount: number
            status: string
            gateway: string
            paid_at?: string
            office?: { name: string }
          }) => (
            <Card key={p.id} className="glass">
              <CardContent className="p-4 flex items-center justify-between gap-3">
                <div>
                  <p className="font-medium">{p.office?.name || '—'}</p>
                  <p className="text-sm text-muted">{p.gateway} · {p.paid_at ? new Date(p.paid_at).toLocaleDateString('fa-IR') : '—'}</p>
                </div>
                <div className="text-left">
                  <p className="font-bold">{formatPrice(p.amount)}</p>
                  <Badge variant={p.status === 'paid' ? 'success' : 'warning'}>{p.status}</Badge>
                </div>
              </CardContent>
            </Card>
          ))}
          {!payments?.data?.length && <p className="text-muted text-center py-8">تراکنشی ثبت نشده</p>}
        </div>
      )}
    </div>
  )
}
