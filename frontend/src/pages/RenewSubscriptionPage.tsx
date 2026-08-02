import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { AlertTriangle, CheckCircle2 } from 'lucide-react'
import api from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { PlanComparisonSection } from '@/components/plans/PlanComparisonSection'

interface Plan {
  id: number
  name: string
  slug: string
  monthly_price: number
  max_properties: number
  max_users: number
  description?: string
  features?: string[]
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
      else {
        await refreshUser()
        queryClient.invalidateQueries({ queryKey: ['subscription-current'] })
        queryClient.invalidateQueries({ queryKey: ['wallet'] })
      }
    },
  })

  const currentPlanSlug = office?.plan?.slug
  const filteredPlans = plans?.filter((p) => !currentPlanSlug || p.slug === currentPlanSlug || ['solo', 'office', 'premium'].includes(p.slug))

  return (
    <div className="min-h-[80vh] p-4 animate-fade-in">
      <div className="w-full max-w-6xl mx-auto space-y-6">
        <Card className="border-warning/40 bg-warning/5">
          <CardContent className="p-6 flex gap-4 items-start">
            <AlertTriangle className="h-8 w-8 text-warning shrink-0 mt-1" />
            <div>
              <h1 className="text-xl font-bold">دوره آزمایشی یا اشتراک شما به پایان رسیده</h1>
              <p className="text-muted mt-2 leading-relaxed">
                برای ادامه استفاده از پوشه، لطفاً یکی از پلن‌ها را انتخاب و تمدید کنید.
              </p>
              {!office?.on_trial && !office?.has_access && (
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

        {filteredPlans && (
          <PlanComparisonSection
            plans={filteredPlans}
            mode="subscription"
            currentPlanSlug={currentPlanSlug}
            onSelectPlan={(planId, gateway) => subscribeMutation.mutate({ planId, gateway })}
            selecting={subscribeMutation.isPending}
          />
        )}

        <div className="text-center">
          <Link to="/subscription" className="text-sm text-primary hover:underline">جزئیات اشتراک</Link>
        </div>
      </div>
    </div>
  )
}
