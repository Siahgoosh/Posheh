import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { CreditCard, CheckCircle2, Sparkles } from 'lucide-react'
import api from '@/lib/api'
import { formatJalaliDate } from '@/lib/utils'
import { subscriptionStatusLabel } from '@/constants/plans'
import { PlanComparisonSection } from '@/components/plans/PlanComparisonSection'
import { Card, CardContent } from '@/components/ui/card'
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
    queryFn: async () => (await api.get('/plans')).data.data as Plan[],
  })

  const { data: current } = useQuery({
    queryKey: ['subscription-current'],
    queryFn: async () => (await api.get('/subscription/current')).data.data as CurrentSub | null,
  })

  const subscribeMutation = useMutation({
    mutationFn: ({ planId, gateway }: { planId: number; gateway: string }) =>
      api.post('/subscribe', { plan_id: planId, gateway }),
    onSuccess: (res) => {
      if (res.data.redirect_url) window.open(res.data.redirect_url, '_blank')
      queryClient.invalidateQueries({ queryKey: ['subscription-current'] })
    },
  })

  return (
    <div className="space-y-8 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <CreditCard className="h-6 w-6 text-primary" />
          اشتراک و تعرفه
        </h1>
        <p className="text-muted mt-1">مقایسه پلن‌ها و انتخاب مناسب‌ترین گزینه برای دفتر شما</p>
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

      <Card className="border-primary/20 bg-primary/5">
        <CardContent className="p-4 flex gap-3 items-start text-sm">
          <Sparkles className="h-5 w-5 text-primary shrink-0 mt-0.5" />
          <div>
            <p className="font-medium">پلن حرفه‌ای = دستیار هوشمند AI</p>
            <p className="text-muted mt-1">سناریوی ریلز، تقویم محتوا، کپشن، تحلیل بازار و ۱۷+ ابزار بازاریابی — همه بر اساس داده‌های واقعی دفتر شما.</p>
          </div>
        </CardContent>
      </Card>

      {plansLoading ? (
        <div className="flex justify-center py-12">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : plans ? (
        <PlanComparisonSection
          plans={plans}
          mode="subscription"
          currentPlanSlug={current?.plan?.slug}
          onSelectPlan={(planId, gateway) => subscribeMutation.mutate({ planId, gateway })}
          selecting={subscribeMutation.isPending}
        />
      ) : null}
    </div>
  )
}
