import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useQuery, useMutation } from '@tanstack/react-query'
import { CreditCard, Check, Crown, Zap } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

interface Plan {
  id: number
  name: string
  slug: string
  monthly_price: number
  max_properties: number
  max_users: number
  features: string[]
}

export function SubscriptionPage() {
  const [searchParams] = useSearchParams()
  const [statusMsg, setStatusMsg] = useState('')

  useEffect(() => {
    const status = searchParams.get('status')
    if (status === 'success') setStatusMsg('پرداخت با موفقیت انجام شد! اشتراک شما فعال شد.')
    if (status === 'failed') setStatusMsg('پرداخت ناموفق بود. لطفاً دوباره تلاش کنید.')
  }, [searchParams])

  const { data: plans, isLoading } = useQuery({
    queryKey: ['plans'],
    queryFn: async () => (await api.get('/plans')).data.data as Plan[],
  })

  const { data: current } = useQuery({
    queryKey: ['subscription-current'],
    queryFn: async () => (await api.get('/subscription/current')).data.data,
  })

  const subscribeMutation = useMutation({
    mutationFn: async ({ planId, gateway }: { planId: number; gateway: string }) => {
      const res = await api.post('/subscribe', { plan_id: planId, gateway })
      return res.data
    },
    onSuccess: (data) => {
      if (data.redirect_url) {
        window.location.href = data.redirect_url
      } else {
        setStatusMsg(data.message || 'اشتراک فعال شد')
      }
    },
    onError: () => setStatusMsg('خطا در پردازش اشتراک'),
  })

  const planIcons: Record<string, typeof Crown> = {
    basic: Zap,
    pro: Crown,
    enterprise: Crown,
  }

  return (
    <div className="space-y-6 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <CreditCard className="h-6 w-6 text-primary" />
          اشتراک و پرداخت
        </h1>
        <p className="text-muted mt-1">پلن مناسب دفتر خود را انتخاب کنید</p>
      </div>

      {statusMsg && (
        <Card className={`glass border ${statusMsg.includes('موفق') ? 'border-success/50' : 'border-danger/50'}`}>
          <CardContent className="p-4 text-sm">{statusMsg}</CardContent>
        </Card>
      )}

      {current && (
        <Card className="glass border-primary/30">
          <CardContent className="p-4 flex items-center justify-between">
            <div>
              <p className="text-sm text-muted">اشتراک فعلی</p>
              <p className="font-semibold">{current.plan?.name || 'فعال'}</p>
            </div>
            <Badge>تا {current.ends_at?.slice(0, 10)}</Badge>
          </CardContent>
        </Card>
      )}

      {isLoading ? (
        <div className="flex justify-center py-20">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : (
        <div className="grid md:grid-cols-3 gap-6">
          {plans?.map((plan, i) => {
            const Icon = planIcons[plan.slug] || Crown
            const isPopular = i === 1
            return (
              <Card key={plan.id} className={`glass-hover relative ${isPopular ? 'border-primary ring-2 ring-primary/20' : ''}`}>
                {isPopular && (
                  <Badge className="absolute -top-3 left-1/2 -translate-x-1/2">پیشنهادی</Badge>
                )}
                <CardHeader>
                  <div className="flex items-center gap-3">
                    <div className="h-10 w-10 rounded-xl bg-primary/20 flex items-center justify-center">
                      <Icon className="h-5 w-5 text-primary" />
                    </div>
                    <CardTitle>{plan.name}</CardTitle>
                  </div>
                  <p className="text-2xl font-bold text-primary mt-2">
                    {formatPrice(plan.monthly_price)}
                    <span className="text-sm text-muted font-normal"> / ماهانه</span>
                  </p>
                </CardHeader>
                <CardContent className="space-y-4">
                  <p className="text-sm text-muted">
                    تا {plan.max_properties} ملک و {plan.max_users} کاربر
                  </p>
                  <ul className="space-y-2">
                    {plan.features?.map((f) => (
                      <li key={f} className="flex items-center gap-2 text-sm">
                        <Check className="h-4 w-4 text-success" />
                        {f.replace(/_/g, ' ')}
                      </li>
                    ))}
                  </ul>
                  <div className="space-y-2 pt-2">
                    <Button
                      className="w-full"
                      variant={isPopular ? 'default' : 'secondary'}
                      onClick={() => subscribeMutation.mutate({ planId: plan.id, gateway: 'aqayepardakht' })}
                      disabled={subscribeMutation.isPending}
                    >
                      پرداخت با آقای پرداخت
                    </Button>
                    <Button
                      className="w-full"
                      variant="outline"
                      onClick={() => subscribeMutation.mutate({ planId: plan.id, gateway: 'wallet' })}
                      disabled={subscribeMutation.isPending}
                    >
                      پرداخت از کیف پول
                    </Button>
                  </div>
                </CardContent>
              </Card>
            )
          })}
        </div>
      )}
    </div>
  )
}
