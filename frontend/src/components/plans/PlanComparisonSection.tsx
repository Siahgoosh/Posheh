import { Check, Minus, Sparkles } from 'lucide-react'
import { formatPrice } from '@/lib/utils'
import { PLAN_COMPARISON_FEATURE_KEYS, PLAN_HIGHLIGHTS, featureDef, planHasFeature } from '@/constants/planFeatures'
import { PLAN_FEATURE_LABELS, trialBadgeForPlan } from '@/constants/plans'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Link } from 'react-router-dom'

export interface PlanForComparison {
  id: number
  slug: string
  name: string
  description?: string
  monthly_price: number
  max_users: number
  max_properties: number
  features?: string[]
}

interface Props {
  plans: PlanForComparison[]
  mode?: 'marketing' | 'subscription'
  currentPlanSlug?: string
  onSelectPlan?: (planId: number, gateway: 'zibal' | 'wallet') => void
  selecting?: boolean
}

export function PlanComparisonSection({ plans, mode = 'marketing', currentPlanSlug, onSelectPlan, selecting }: Props) {
  const sorted = [...plans].sort((a, b) => a.monthly_price - b.monthly_price)
  const popularSlug = 'office'

  return (
    <div className="space-y-10">
      <div className="grid md:grid-cols-3 gap-6">
        {sorted.map((plan) => {
          const isPopular = plan.slug === popularSlug
          const isCurrent = currentPlanSlug === plan.slug
          const highlights = PLAN_HIGHLIGHTS[plan.slug] ?? []

          return (
            <Card
              key={plan.id}
              className={`p-6 flex flex-col relative ${isPopular ? 'border-primary/50 ring-2 ring-primary/20' : ''} ${isCurrent ? 'border-success/40' : ''}`}
            >
              {isPopular && (
                <Badge className="absolute -top-3 right-4 bg-primary">محبوب‌ترین</Badge>
              )}
              {isCurrent && (
                <Badge variant="outline" className="absolute -top-3 left-4 border-success text-success">پلن فعلی</Badge>
              )}

              <h3 className="text-xl font-bold">{plan.name}</h3>
              <p className="text-3xl font-black text-primary mt-2">{formatPrice(plan.monthly_price)}</p>
              <p className="text-xs text-muted mb-1">ماهانه · تومان</p>
              {trialBadgeForPlan(plan.slug) && (
                <p className="text-xs text-warning font-medium mb-2">{trialBadgeForPlan(plan.slug)}</p>
              )}
              <p className="text-sm text-muted leading-relaxed mb-4">{plan.description}</p>

              <div className="rounded-xl bg-muted/10 p-3 text-sm space-y-1 mb-4">
                <p>تا <strong>{plan.max_users}</strong> کاربر</p>
                <p>تا <strong>{plan.max_properties.toLocaleString('fa-IR')}</strong> فایل ملک</p>
              </div>

              <ul className="space-y-2 flex-1 text-sm mb-6">
                {highlights.map((h) => (
                  <li key={h} className="flex gap-2 items-start">
                    <Sparkles className="h-4 w-4 text-primary shrink-0 mt-0.5" />
                    <span>{h}</span>
                  </li>
                ))}
              </ul>

              {mode === 'marketing' ? (
                <Link to="/register">
                  <Button className="w-full" variant={isPopular ? 'default' : 'outline'}>
                    {plan.slug === 'solo' ? 'شروع ۴۸ ساعت رایگان' : 'انتخاب پلن'}
                  </Button>
                </Link>
              ) : onSelectPlan ? (
                <div className="space-y-2">
                  <Button className="w-full" disabled={selecting || isCurrent} onClick={() => onSelectPlan(plan.id, 'zibal')}>
                    {isCurrent ? 'پلن فعال شما' : 'پرداخت با زیبال'}
                  </Button>
                  {!isCurrent && (
                    <Button variant="outline" className="w-full" disabled={selecting} onClick={() => onSelectPlan(plan.id, 'wallet')}>
                      خرید با کیف پول
                    </Button>
                  )}
                </div>
              ) : null}
            </Card>
          )
        })}
      </div>

      <div className="overflow-x-auto rounded-2xl border border-card-border">
        <table className="w-full text-sm min-w-[720px]">
          <thead>
            <tr className="bg-muted/10 border-b border-card-border">
              <th className="text-right p-4 font-semibold w-2/5">امکانات</th>
              {sorted.map((p) => (
                <th key={p.id} className="p-4 text-center font-semibold">{p.name}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {PLAN_COMPARISON_FEATURE_KEYS.map((key) => {
              const def = featureDef(key)
              return (
                <tr key={key} className="border-b border-card-border/50 hover:bg-muted/5">
                  <td className="p-4">
                    <p className="font-medium">{def?.label || PLAN_FEATURE_LABELS[key] || key}</p>
                    {def?.description && <p className="text-xs text-muted mt-0.5">{def.description}</p>}
                  </td>
                  {sorted.map((p) => (
                    <td key={p.id} className="p-4 text-center">
                      {planHasFeature(p.features, key) ? (
                        <Check className={`h-5 w-5 mx-auto ${def?.highlight && p.slug === 'premium' ? 'text-warning' : 'text-success'}`} />
                      ) : (
                        <Minus className="h-4 w-4 mx-auto text-muted/40" />
                      )}
                    </td>
                  ))}
                </tr>
              )
            })}
          </tbody>
        </table>
      </div>
    </div>
  )
}
