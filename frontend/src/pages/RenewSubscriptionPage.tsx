import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { AlertTriangle, CreditCard, CheckCircle2, Tag } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { useAuthStore } from '@/stores/auth'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'

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
  const { user } = useAuthStore()
  const queryClient = useQueryClient()
  const office = user?.office
  const [discountCode, setDiscountCode] = useState('')

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
      api.post('/subscribe', {
        plan_id: planId,
        gateway,
        discount_code: discountCode.trim() || undefined,
      }),
    onSuccess: (res) => {
      if (res.data.redirect_url) {
        window.open(res.data.redirect_url, '_blank')
      } else {
        queryClient.invalidateQueries({ queryKey: ['subscription-current'] })
      }
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
              {!office?.on_trial && !office?.has_access && (
                <Badge variant="outline" className="mt-3">نیاز به پرداخت</Badge>
              )}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <label className="text-sm text-muted flex items-center gap-1 mb-1"><Tag className="h-3 w-3" /> کد تخفیف (اختیاری)</label>
            <Input value={discountCode} onChange={(e) => setDiscountCode(e.target.value)} placeholder="کد تخفیف" />
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
                  onClick={() => subscribeMutation.mutate({ planId: plan.id, gateway: 'zibal' })}
                >
                  <CreditCard className="h-4 w-4" />
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
              </CardContent>
            </Card>
          ))}
        </div>

        <p className="text-center text-xs text-muted">
          پس از پرداخت در درگاه زیبال، به صفحه تأیید هدایت می‌شوید. سپس داشبورد را رفرش کنید.
        </p>

        <div className="text-center">
          <Link to="/subscription" className="text-sm text-primary hover:underline">جزئیات اشتراک</Link>
        </div>
      </div>
    </div>
  )
}
