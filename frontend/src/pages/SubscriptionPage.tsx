import { useQuery } from '@tanstack/react-query'
import { CreditCard } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { formatPrice } from '@/lib/utils'

export function SubscriptionPage() {
  const { data: plans } = useQuery({
    queryKey: ['plans'],
    queryFn: async () => (await api.get('/plans')).data.data,
  })

  const subscribe = async (slug: string) => {
    try {
      const res = await api.post('/subscribe', { plan_slug: slug, gateway: 'wallet' })
      alert(res.data.message || 'اشتراک فعال شد')
    } catch {
      alert('خطا در فعال‌سازی — موجودی کیف پول را بررسی کنید')
    }
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold flex items-center gap-2"><CreditCard className="h-6 w-6 text-primary" />اشتراک</h1>
      <div className="grid md:grid-cols-3 gap-4">
        {plans?.map((p: { slug: string; name: string; monthly_price: number; description?: string; max_users: number }) => (
          <Card key={p.slug} className="p-6 space-y-4">
            <h2 className="font-bold text-lg">{p.name}</h2>
            <p className="text-2xl font-bold text-primary">{formatPrice(p.monthly_price)}<span className="text-sm text-muted">/ماه</span></p>
            <p className="text-sm text-muted">{p.description}</p>
            <p className="text-xs text-muted">{p.max_users} مشاور</p>
            <Button className="w-full" onClick={() => subscribe(p.slug)}>فعال‌سازی</Button>
          </Card>
        ))}
      </div>
    </div>
  )
}
