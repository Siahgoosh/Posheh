import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { CreditCard, CheckCircle2, Wallet } from 'lucide-react'
import api from '@/lib/api'
import { formatJalaliDate, formatPrice } from '@/lib/utils'
import { PLAN_FEATURE_LABELS, subscriptionStatusLabel, trialBadgeForPlan } from '@/constants/plans'
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

interface WalletInfo {
  balance: number
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

  const { data: wallet } = useQuery({
    queryKey: ['wallet'],
    queryFn: async () => {
      const res = await api.get('/wallet')
      return res.data.data as WalletInfo
    },
  })

  const subscribeMutation = useMutation({
    mutationFn: ({ planId, gateway }: { planId: number; gateway: string }) =>
      api.post('/subscribe', { plan_id: planId, gateway }),
    onSuccess: (res) => {
      if (res.data.redirect_url) {
        window.location.href = res.data.redirect_url
        return
      }
      queryClient.invalidateQueries({ queryKey: ['subscription-current'] })
      queryClient.invalidateQueries({ queryKey: ['wallet'] })
    },
  })

  return (
    <div className="space-y-6 animate-fade-in max-w-full overflow-x-hidden">
      <div>
        <h1 className="text-xl sm:text-2xl font-bold flex items-center gap-2">
          <CreditCard className="h-6 w-6 text-primary shrink-0" />
          اشتراک
        </h1>
        <p className="text-muted mt-1 text-sm">مدیریت پلن و پرداخت</p>
      </div>

      {wallet && (
        <Card className="border-primary/20 bg-primary/5">
          <CardContent className="p-4 sm:p-5 flex flex-wrap items-center gap-3">
            <Wallet className="h-5 w-5 text-primary shrink-0" />
            <div className="min-w-0">
              <p className="text-sm text-muted">موجودی کیف پول</p>
              <p className="text-lg font-bold text-primary">{formatPrice(wallet.balance)}</p>
            </div>
            <p className="text-xs text-muted mr-auto">برای «خرید با کیف پول» از همین موجودی کسر می‌شود.</p>
          </CardContent>
        </Card>
      )}

      {current && (
        <Card className="border-success/30 bg-success/5">
          <CardContent className="p-4 sm:p-5 flex flex-wrap items-center gap-3">
            <CheckCircle2 className="h-6 w-6 text-success shrink-0" />
            <div className="min-w-0">
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
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
          {plans?.map((plan) => {
            const canWallet = wallet && wallet.balance >= plan.monthly_price
            return (
              <Card key={plan.id} className="flex flex-col min-w-0">
                <CardHeader className="pb-2">
                  <CardTitle className="text-lg">{plan.name}</CardTitle>
                  <p className="text-2xl font-bold text-primary">{formatPrice(plan.monthly_price)}</p>
                  <p className="text-xs text-muted">ماهانه</p>
                </CardHeader>
                <CardContent className="flex-1 flex flex-col min-w-0">
                  <ul className="text-sm text-muted space-y-1 mb-6 flex-1">
                    <li>تا {plan.max_properties} ملک</li>
                    <li>تا {plan.max_users} کاربر</li>
                    {trialBadgeForPlan(plan.slug) && <li>{trialBadgeForPlan(plan.slug)}</li>}
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
                      disabled={subscribeMutation.isPending || !canWallet}
                      onClick={() => subscribeMutation.mutate({ planId: plan.id, gateway: 'wallet' })}
                    >
                      خرید با کیف پول
                      {!canWallet && wallet ? ` (کمبود ${formatPrice(plan.monthly_price - wallet.balance)})` : ''}
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
