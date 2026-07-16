import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { CreditCard, CheckCircle2 } from 'lucide-react'
import api from '@/lib/api'
import { formatJalaliDate, formatPrice } from '@/lib/utils'
import { PLAN_FEATURE_LABELS, subscriptionStatusLabel } from '@/constants/plans'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface Plan {
  id: number
  name: string
  slug: string
  monthly_price: number
  max_properties: number
  max_users: number
  trial_days: number
  description?: string
  features?: string[]
}

interface CurrentSub {
  status: string
  ends_at: string
  plan: Plan
}

export function SubscriptionPage() {
  const queryClient = useQueryClient()

  const { data: plans, isLoading: plansLoading } = useQuery({
    queryKey: ['plans'],
    queryFn: async () => {
      const res = await api.get('/plans')
      return res.data.data as Plan[]
    },
  })

  const { data: current } = useQuery({
    queryKey: ['subscription-current'],
    queryFn: async () => {
      const res = await api.get('/subscription/current')
      return res.data.data as CurrentSub | null
    },
  })

  const subscribeMutation = useMutation({
    mutationFn: ({ planId, gateway }: { planId: number; gateway: string }) =>
      api.post('/subscribe', { plan_id: planId, gateway }),
    onSuccess: (res) => {
      if (res.data.redirect_url) {
        window.open(res.data.redirect_url, '_blank')
      }
      queryClient.invalidateQueries({ queryKey: ['subscription-current'] })
    },
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <CreditCard className="h-6 w-6 text-primary" />
          اشتراک
        </h1>
        <p className="text-muted mt-1">مدیریت پلن و پرداخت</p>
      </div>

      {current && (
        <Card className="border-success/30 bg-success/5">
          <CardContent className="p-5 flex items-center gap-3">
            <CheckCircle2 className="h-6 w-6 text-success" />
            <div>
              <p className="font-medium">اشتراک فعال: {current.plan?.name}</p>
              <p className="text-sm text-muted">تا {formatJalaliDate(current.ends_at)}</p>
            </div>
            <Badge className="mr-auto">{subscriptionStatusLabel(current.status)}</Badge>
          </CardContent>
        </Card>
      )}

      {!current && (
        <Card className="border-warning/30 bg-warning/5">
          <CardContent className="p-4 text-sm text-muted">
            در دوره آزمایشی یا بدون اشتراک فعال هستید. برای ادامه استفاده پس از پایان آزمایشی، یکی از پلن‌ها را انتخاب کنید.
          </CardContent>
        </Card>
      )}

      {plansLoading ? (
        <div className="flex justify-center py-12">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : (
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
          {plans?.map((plan) => (
            <Card key={plan.id} className="flex flex-col">
              <CardHeader>
                <CardTitle>{plan.name}</CardTitle>
                <p className="text-2xl font-bold text-primary">{formatPrice(plan.monthly_price)}</p>
                <p className="text-xs text-muted">ماهانه</p>
              </CardHeader>
              <CardContent className="flex-1 flex flex-col">
                <ul className="text-sm text-muted space-y-1 mb-6 flex-1">
                  <li>تا {plan.max_properties} ملک</li>
                  <li>تا {plan.max_users} کاربر</li>
                  <li>{plan.trial_days} روز آزمایشی رایگان</li>
                  {plan.description && <li>{plan.description}</li>}
                  {plan.features?.slice(0, 5).map((f) => (
                    <li key={f}>{PLAN_FEATURE_LABELS[f] || f}</li>
                  ))}
                </ul>
                <div className="space-y-2">
                  <Button
                    className="w-full"
                    disabled={subscribeMutation.isPending}
                    onClick={() => subscribeMutation.mutate({ planId: plan.id, gateway: 'zibal' })}
                  >
                    پرداخت با زیبال
                  </Button>
                  <Button
                    variant="outline"
                    className="w-full"
                    disabled={subscribeMutation.isPending}
                    onClick={() => subscribeMutation.mutate({ planId: plan.id, gateway: 'wallet' })}
                  >
                    خرید با کیف پول
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
