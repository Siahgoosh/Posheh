import { useQuery } from '@tanstack/react-query'
import { Calculator, TrendingUp, Clock, CheckCircle } from 'lucide-react'
import api from '@/lib/api'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { formatPrice } from '@/lib/utils'

export function CommissionsPage() {
  const { data: summary } = useQuery({
    queryKey: ['commission-summary'],
    queryFn: async () => (await api.get('/commissions/summary')).data.data,
  })

  const { data: list } = useQuery({
    queryKey: ['commissions'],
    queryFn: async () => (await api.get('/commissions')).data.data,
  })

  const commissions = list?.data || []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2"><Calculator className="h-6 w-6 text-primary" />حسابداری املاک — کمیسیون</h1>
        <p className="text-muted text-sm">تسهیم خودکار کمیسیون بین مدیر و مشاوران</p>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {[
          { label: 'کل درآمد', value: summary?.total, icon: TrendingUp },
          { label: 'پرداخت شده', value: summary?.paid, icon: CheckCircle },
          { label: 'در انتظار', value: summary?.pending, icon: Clock },
          { label: 'این ماه', value: summary?.monthly, icon: Calculator },
        ].map((s) => (
          <Card key={s.label} className="p-4">
            <s.icon className="h-5 w-5 text-primary mb-2" />
            <p className="text-xs text-muted">{s.label}</p>
            <p className="text-lg font-bold">{formatPrice(s.value || 0)}</p>
          </Card>
        ))}
      </div>

      <div className="space-y-3">
        {commissions.map((c: { id: number; total_amount: number; status: string; property?: { code: string }; splits?: { user: { name: string }; amount: number; role: string }[] }) => (
          <Card key={c.id} className="p-4">
            <div className="flex justify-between items-center mb-2">
              <span className="font-medium">{c.property?.code || 'معامله'}</span>
              <Badge variant={c.status === 'paid' ? 'default' : 'outline'}>{c.status === 'paid' ? 'پرداخت شده' : 'در انتظار'}</Badge>
            </div>
            <p className="text-primary font-bold">{formatPrice(c.total_amount)}</p>
            <div className="flex flex-wrap gap-2 mt-2">
              {c.splits?.map((s, i) => (
                <span key={i} className="text-xs bg-muted/20 px-2 py-1 rounded">{s.user?.name} ({s.role}): {formatPrice(s.amount)}</span>
              ))}
            </div>
          </Card>
        ))}
        {!commissions.length && <p className="text-center text-muted py-8">کمیسیونی ثبت نشده</p>}
      </div>
    </div>
  )
}
