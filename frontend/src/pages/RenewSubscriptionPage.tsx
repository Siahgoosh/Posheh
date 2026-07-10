import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { AlertTriangle, CreditCard, CheckCircle2 } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { useAuthStore } from '@/stores/auth'
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
  description?: string
}

export function RenewSubscriptionPage() {
  const { user, refreshUser } = useAuthStore()
  const queryClient = useQueryClient()
  const office = user?.office

  const { data: plans } = useQuery({
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
      return res.data.data
    },
  })

  const subscribeMutation = useMutation({
    mutationFn: ({ planId, gateway }: { planId: number; gateway: string }) =>
      api.post('/subscribe', { plan_id: planId, gateway }),
    onSuccess: async (res) => {
      if (res.data.redirect_url) window.open(res.data.redirect_url, '_blank')
      await refreshUser()
      queryClient.invalidateQueries({ queryKey: ['subscription-current'] })
    },
  })

  const currentPlanSlug = office?.plan?.slug
  const filteredPlans = plans?.filter((p) => !currentPlanSlug || p.slug === currentPlanSlug || ['solo', 'office', 'premium'].includes(p.slug))

  return (
    <div className="min-h-[80vh] flex items-center justify-center p-4">
      <div className="w-full max-w-3xl space-y-6 animate-fade-in">
        <Card className="border-warning/40 bg-warning/5">
          <CardContent className="p-6 flex gap-4 items-start">
            <AlertTriangle className="h-8 w-8 text-warning shrink-0 mt-1" />
            <div>
              <h1 className="text-xl font-bold">دوره آزمایشی یا اشتراک شما به پایان رسیده</h1>
              <p className="text-muted mt-2 leading-relaxed">
                برای ادامه استفاده از پوشه، لطفاً حساب خود را شارژ و تمدید کنید.
                تا زمان تمدید، دسترسی به امکانات محدود است.
              </p>
              {office?.trial_days_remaining === 0 && office?.on_trial === false && (
                <Badge variant="outline" className="mt-3">نیاز به پرداخت</Badge>
              )}
            </div>
          </CardContent>
        </Card>

        {current && (
          <Card className="border-success/30">
            <CardContent className="p-4 flex items-center gap-3">
              <CheckCircle2 className="h-5 w-5 text-success" />
              <span className="text-sm">آخرین اشتراک: {current.plan?.name}</span>
            </CardContent>
          </Card>
        )}

        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          {filteredPlans?.map((plan) => (
            <Card key={plan.id}>
              <CardHeader>
                <CardTitle className="text-lg">{plan.name}</CardTitle>
                <p className="text-xl font-bold text-primary">{formatPrice(plan.monthly_price)}</p>
                <p className="text-xs text-muted">ماهانه</p>
              </CardHeader>
              <CardContent className="space-y-3">
                <p className="text-sm text-muted">{plan.description}</p>
                <Button
                  className="w-full"
                  disabled={subscribeMutation.isPending}
                  onClick={() => subscribeMutation.mutate({ planId: plan.id, gateway: 'zarinpal' })}
                >
                  <CreditCard className="h-4 w-4" />
                  پرداخت با زرین‌پال
                </Button>
                <Button
                  variant="outline"
                  className="w-full"
                  disabled={subscribeMutation.isPending}
                  onClick={() => subscribeMutation.mutate({ planId: plan.id, gateway: 'wallet' })}
                >
                  خرید با کیف پول
                </Button>
              </CardContent>
            </Card>
          ))}
        </div>

        <div className="text-center">
          <Link to="/subscription" className="text-sm text-primary hover:underline">جزئیات اشتراک</Link>
        </div>
      </div>
    </div>
  )
}
