import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { CreditCard, CheckCircle2, Tag, Wallet, History, FileText } from 'lucide-react'
import api from '@/lib/api'
import { formatJalaliDate, formatPrice } from '@/lib/utils'
import { PLAN_FEATURE_LABELS, subscriptionStatusLabel, trialBadgeForPlan } from '@/constants/plans'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { PaymentInvoiceCard, type InvoiceData } from '@/components/PaymentInvoiceCard'

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

interface DiscountPreview {
  original_amount: number
  discount_amount: number
  final_amount: number
}

interface PaymentRow {
  id: number
  invoice_number?: string
  purpose_label: string
  status_label: string
  status: string
  gateway_label: string
  amount: number
  ref_id?: string
  plan_name?: string
  paid_at?: string
  created_at?: string
}

export function SubscriptionPage() {
  const queryClient = useQueryClient()
  const [discountCode, setDiscountCode] = useState('')
  const [selectedPlanId, setSelectedPlanId] = useState<number | null>(null)
  const [preview, setPreview] = useState<DiscountPreview | null>(null)
  const [topUpAmount, setTopUpAmount] = useState('100000')
  const [pendingInvoice, setPendingInvoice] = useState<{ invoice: InvoiceData; redirectUrl: string; paymentId: number } | null>(null)
  const [viewInvoiceId, setViewInvoiceId] = useState<number | null>(null)

  const params = new URLSearchParams(window.location.search)
  const walletStatus = params.get('wallet')

  const { data: wallet } = useQuery({
    queryKey: ['wallet-balance'],
    queryFn: async () => (await api.get('/wallet')).data.data as { balance: number },
  })

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

  const { data: paymentHistory } = useQuery({
    queryKey: ['payment-history'],
    queryFn: async () => {
      const res = await api.get('/payments')
      return res.data.data as PaymentRow[]
    },
  })

  const { data: viewedInvoice } = useQuery({
    queryKey: ['payment-invoice', viewInvoiceId],
    queryFn: async () => (await api.get(`/payments/${viewInvoiceId}/invoice`)).data.data as InvoiceData,
    enabled: !!viewInvoiceId,
  })

  const previewMutation = useMutation({
    mutationFn: async (planId: number) => {
      const res = await api.post('/discount-codes/preview', {
        plan_id: planId,
        discount_code: discountCode.trim(),
      })
      return res.data.data as DiscountPreview
    },
    onSuccess: (data) => setPreview(data),
  })

  const subscribeMutation = useMutation({
    mutationFn: ({ planId, gateway }: { planId: number; gateway: string }) =>
      api.post('/subscribe', {
        plan_id: planId,
        gateway,
        discount_code: discountCode.trim() || undefined,
      }),
    onSuccess: (res) => {
      if (res.data.invoice && res.data.redirect_url) {
        setPendingInvoice({
          invoice: res.data.invoice,
          redirectUrl: res.data.redirect_url,
          paymentId: res.data.payment_id,
        })
      } else if (res.data.redirect_url) {
        window.open(res.data.redirect_url, '_blank')
      } else {
        queryClient.invalidateQueries({ queryKey: ['subscription-current'] })
        queryClient.invalidateQueries({ queryKey: ['wallet-balance'] })
        queryClient.invalidateQueries({ queryKey: ['payment-history'] })
      }
    },
  })

  const topUpMutation = useMutation({
    mutationFn: () => api.post('/wallet/top-up', { amount: Number(topUpAmount) }),
    onSuccess: (res) => {
      if (res.data.invoice && res.data.redirect_url) {
        setPendingInvoice({
          invoice: res.data.invoice,
          redirectUrl: res.data.redirect_url,
          paymentId: res.data.payment_id,
        })
      } else if (res.data.redirect_url) {
        window.open(res.data.redirect_url, '_blank')
      }
    },
  })

  useEffect(() => {
    if (walletStatus === 'success') {
      queryClient.invalidateQueries({ queryKey: ['wallet-balance'] })
    }
  }, [walletStatus, queryClient])

  return (
    <div className="space-y-6 animate-fade-in">
      {(pendingInvoice || viewedInvoice) && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" onClick={() => { setPendingInvoice(null); setViewInvoiceId(null) }}>
          <div onClick={(e) => e.stopPropagation()}>
            <PaymentInvoiceCard
              invoice={pendingInvoice?.invoice ?? viewedInvoice!}
              paymentId={pendingInvoice?.paymentId ?? viewInvoiceId ?? undefined}
              onConfirm={pendingInvoice ? () => { window.open(pendingInvoice.redirectUrl, '_blank'); setPendingInvoice(null) } : undefined}
              onClose={() => { setPendingInvoice(null); setViewInvoiceId(null) }}
              showActions
            />
          </div>
        </div>
      )}

      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <CreditCard className="h-6 w-6 text-primary" />
          اشتراک
        </h1>
        <p className="text-muted mt-1">مدیریت پلن، کیف پول و پرداخت</p>
      </div>

      <Card>
        <CardContent className="p-5 flex flex-wrap items-center gap-4">
          <div className="flex items-center gap-2">
            <Wallet className="h-5 w-5 text-primary" />
            <div>
              <p className="text-sm text-muted">موجودی کیف پول</p>
              <p className="text-xl font-bold">{formatPrice(wallet?.balance ?? 0)}</p>
            </div>
          </div>
          <Input className="max-w-[160px]" dir="ltr" value={topUpAmount} onChange={(e) => setTopUpAmount(e.target.value)} placeholder="مبلغ شارژ" />
          <Button variant="outline" onClick={() => topUpMutation.mutate()} disabled={topUpMutation.isPending}>شارژ با زیبال</Button>
          {walletStatus === 'success' && <Badge className="bg-success/20 text-success">شارژ موفق</Badge>}
        </CardContent>
      </Card>

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

      <Card>
        <CardContent className="p-4 flex flex-wrap gap-2 items-end">
          <div className="flex-1 min-w-[200px]">
            <label className="text-sm text-muted flex items-center gap-1 mb-1"><Tag className="h-3 w-3" /> کد تخفیف</label>
            <Input value={discountCode} onChange={(e) => { setDiscountCode(e.target.value); setPreview(null) }} placeholder="کد تخفیف را وارد کنید" />
          </div>
          <Button
            variant="outline"
            disabled={!discountCode.trim() || !selectedPlanId || previewMutation.isPending}
            onClick={() => selectedPlanId && previewMutation.mutate(selectedPlanId)}
          >
            اعمال
          </Button>
          {preview && (
            <p className="text-sm text-success w-full">
              تخفیف: {formatPrice(preview.discount_amount)} — مبلغ نهایی: {formatPrice(preview.final_amount)}
            </p>
          )}
        </CardContent>
      </Card>

      {plansLoading ? (
        <div className="flex justify-center py-12">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : (
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
          {plans?.map((plan) => (
            <Card key={plan.id} className="flex flex-col" onMouseEnter={() => setSelectedPlanId(plan.id)}>
              <CardHeader>
                <CardTitle>{plan.name}</CardTitle>
                <p className="text-2xl font-bold text-primary">
                  {preview && selectedPlanId === plan.id ? formatPrice(preview.final_amount) : formatPrice(plan.monthly_price)}
                </p>
                <p className="text-xs text-muted">ماهانه</p>
              </CardHeader>
              <CardContent className="flex-1 flex flex-col">
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
                    onClick={() => {
                      setSelectedPlanId(plan.id)
                      subscribeMutation.mutate({ planId: plan.id, gateway: 'zibal' })
                    }}
                  >
                    پرداخت با زیبال
                  </Button>
                  <Button
                    variant="outline"
                    className="w-full"
                    disabled={subscribeMutation.isPending}
                    onClick={() => subscribeMutation.mutate({ planId: plan.id, gateway: 'aqayepardakht' })}
                  >
                    پرداخت با آقای پرداخت
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

      <Card>
        <CardHeader>
          <CardTitle className="text-base flex items-center gap-2"><History className="h-4 w-4" /> تاریخچه پرداخت‌ها</CardTitle>
        </CardHeader>
        <CardContent className="space-y-2">
          {!paymentHistory?.length ? (
            <p className="text-sm text-muted">هنوز پرداختی ثبت نشده است.</p>
          ) : paymentHistory.map((p) => (
            <div key={p.id} className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-card-border p-3 text-sm">
              <div>
                <p className="font-medium">{p.purpose_label}{p.plan_name ? ` — ${p.plan_name}` : ''}</p>
                <p className="text-xs text-muted">{p.invoice_number ?? `#${p.id}`} · {p.gateway_label} · {p.created_at ? formatJalaliDate(p.created_at) : ''}</p>
              </div>
              <div className="flex items-center gap-2">
                <span className="font-bold text-primary">{formatPrice(p.amount)}</span>
                <Badge variant="outline" className={p.status === 'paid' ? 'text-success' : ''}>{p.status_label}</Badge>
                <Button size="sm" variant="ghost" onClick={() => setViewInvoiceId(p.id)}>
                  <FileText className="h-4 w-4" />
                </Button>
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
