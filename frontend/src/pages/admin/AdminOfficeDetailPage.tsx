import { useParams, Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { formatJalaliDate, formatNumber, formatPrice, toEnglishDigits } from '@/lib/utils'
import { extractApiError } from '@/lib/apiError'
import { useMemo, useState } from 'react'

type PlanOption = {
  id: number
  name: string
  panel_type?: string
  monthly_price?: number
  is_active?: boolean
  trial_days?: number
}

function parseAmount(raw: string): number {
  const cleaned = toEnglishDigits(raw).replace(/[^\d]/g, '')
  if (!cleaned) return 0
  return parseInt(cleaned, 10) || 0
}

export function AdminOfficeDetailPage() {
  const { id } = useParams<{ id: string }>()
  const queryClient = useQueryClient()
  const [planId, setPlanId] = useState('')
  const [planDays, setPlanDays] = useState('30')
  const [trialDate, setTrialDate] = useState('')
  const [walletAmount, setWalletAmount] = useState('')
  const [walletDesc, setWalletDesc] = useState('')
  const [walletType, setWalletType] = useState<'credit' | 'debit'>('credit')
  const [feedback, setFeedback] = useState<{ type: 'ok' | 'err'; text: string } | null>(null)

  const { data: office, isLoading } = useQuery({
    queryKey: ['admin-office', id],
    queryFn: async () => (await api.get(`/admin/offices/${id}`)).data.data,
    enabled: !!id,
  })

  const { data: plans } = useQuery({
    queryKey: ['admin-plans'],
    queryFn: async () => (await api.get('/admin/plans')).data.data as PlanOption[],
  })

  const activePlans = useMemo(
    () => (plans ?? []).filter((p) => p.is_active !== false),
    [plans],
  )

  const amountValue = parseAmount(walletAmount)
  const daysValue = parseAmount(planDays) || 30

  const statusMutation = useMutation({
    mutationFn: (payload: Record<string, unknown>) => api.put(`/admin/offices/${id}/status`, payload),
    onSuccess: () => {
      setFeedback({ type: 'ok', text: 'وضعیت دفتر به‌روز شد.' })
      queryClient.invalidateQueries({ queryKey: ['admin-office', id] })
    },
    onError: (err) => setFeedback({ type: 'err', text: extractApiError(err) }),
  })

  const assignPlan = useMutation({
    mutationFn: () => api.post(`/admin/offices/${id}/assign-plan`, {
      plan_id: Number(planId),
      days: daysValue,
    }),
    onSuccess: (res) => {
      setFeedback({ type: 'ok', text: res.data?.message || 'پلن با موفقیت تخصیص داده شد.' })
      queryClient.invalidateQueries({ queryKey: ['admin-office', id] })
      queryClient.invalidateQueries({ queryKey: ['admin-subscriptions'] })
    },
    onError: (err) => setFeedback({ type: 'err', text: extractApiError(err) }),
  })

  const walletMutation = useMutation({
    mutationFn: () => api.post(`/admin/offices/${id}/wallet/adjust`, {
      amount: amountValue,
      type: walletType,
      description: walletDesc.trim() || (walletType === 'credit' ? 'شارژ دستی' : 'برداشت دستی'),
    }),
    onSuccess: (res) => {
      setWalletAmount('')
      setWalletDesc('')
      setFeedback({ type: 'ok', text: res.data?.message || 'عملیات کیف پول انجام شد.' })
      queryClient.invalidateQueries({ queryKey: ['admin-office', id] })
      queryClient.invalidateQueries({ queryKey: ['admin-wallets'] })
    },
    onError: (err) => setFeedback({ type: 'err', text: extractApiError(err) }),
  })

  if (isLoading || !office) return <p className="text-muted p-6">بارگذاری…</p>

  return (
    <div className="space-y-6 animate-fade-in max-w-3xl">
      <AdminPageHeader title={office.name} description={`دفتر #${office.id} · ${office.slug}`} backTo="/tenants" />

      {feedback && (
        <div
          className={`rounded-xl border px-3 py-2 text-sm ${
            feedback.type === 'ok'
              ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200'
              : 'border-red-500/40 bg-red-500/10 text-red-200'
          }`}
          role="status"
        >
          {feedback.text}
        </div>
      )}

      <div className="grid gap-4 sm:grid-cols-3">
        <Card className="glass"><CardContent className="pt-6 text-center">
          <p className="text-2xl font-bold">{formatNumber(office.properties_count ?? 0)}</p>
          <p className="text-xs text-muted">ملک</p>
        </CardContent></Card>
        <Card className="glass"><CardContent className="pt-6 text-center">
          <p className="text-2xl font-bold">{formatNumber(office.users_count ?? office.users?.length ?? 0)}</p>
          <p className="text-xs text-muted">کاربر</p>
        </CardContent></Card>
        <Card className="glass"><CardContent className="pt-6 text-center">
          <p className="text-2xl font-bold">{formatPrice(office.wallet?.balance ?? 0)}</p>
          <p className="text-xs text-muted">کیف پول</p>
        </CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>شارژ / برداشت کیف پول</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <div className="flex flex-wrap gap-2">
            <select
              value={walletType}
              onChange={(e) => setWalletType(e.target.value as 'credit' | 'debit')}
              className="rounded-xl border border-card-border bg-background px-3 py-2 text-sm"
            >
              <option value="credit">شارژ (واریز)</option>
              <option value="debit">برداشت</option>
            </select>
            <Input
              placeholder="مبلغ (تومان)"
              value={walletAmount}
              onChange={(e) => setWalletAmount(e.target.value)}
              className="w-44"
              dir="ltr"
              inputMode="numeric"
            />
            <Input
              placeholder="توضیح (اختیاری)"
              value={walletDesc}
              onChange={(e) => setWalletDesc(e.target.value)}
              className="flex-1 min-w-[160px]"
            />
          </div>
          <Button
            type="button"
            onClick={() => walletMutation.mutate()}
            disabled={amountValue < 1 || walletMutation.isPending}
          >
            {walletMutation.isPending ? 'در حال اعمال…' : walletType === 'credit' ? 'شارژ کیف پول' : 'برداشت از کیف پول'}
          </Button>
          {amountValue > 0 && (
            <p className="text-xs text-muted">مبلغ نهایی: {formatPrice(amountValue)}</p>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>وضعیت و پلن</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="flex flex-wrap gap-2">
            <Badge>{office.is_active ? 'فعال' : 'غیرفعال'}</Badge>
            <Badge variant="outline">{office.subscription?.plan?.name ?? 'بدون پلن'}</Badge>
            {office.trial_ends_at && <Badge variant="outline">trial تا {formatJalaliDate(office.trial_ends_at)}</Badge>}
            {office.custom_domain && <Badge variant="outline" dir="ltr">{office.custom_domain}</Badge>}
          </div>

          <div className="flex flex-wrap gap-2">
            <Button
              type="button"
              size="sm"
              variant="outline"
              onClick={() => statusMutation.mutate({ is_active: !office.is_active })}
              disabled={statusMutation.isPending}
            >
              {office.is_active ? 'تعلیق دفتر' : 'فعال‌سازی'}
            </Button>
            <Input type="date" value={trialDate} onChange={(e) => setTrialDate(e.target.value)} className="w-40" />
            <Button
              type="button"
              size="sm"
              variant="outline"
              disabled={!trialDate || statusMutation.isPending}
              onClick={() => statusMutation.mutate({ trial_ends_at: trialDate })}
            >
              تنظیم trial
            </Button>
          </div>

          <div className="rounded-xl border border-card-border p-3 space-y-3">
            <p className="text-sm font-medium">تخصیص پلن</p>
            <div className="flex flex-wrap gap-2 items-end">
              <label className="text-xs text-muted space-y-1">
                <span className="block">پلن</span>
                <select
                  value={planId}
                  onChange={(e) => {
                    setPlanId(e.target.value)
                    const selected = activePlans.find((p) => String(p.id) === e.target.value)
                    if (selected?.trial_days) setPlanDays(String(selected.trial_days > 0 ? Math.max(selected.trial_days, 30) : 30))
                  }}
                  className="block min-w-[220px] rounded-xl border border-card-border bg-background px-3 py-2 text-sm"
                >
                  <option value="">— انتخاب پلن —</option>
                  {activePlans.map((p) => (
                    <option key={p.id} value={p.id}>
                      #{p.id} · {p.name}{p.monthly_price != null ? ` · ${formatPrice(p.monthly_price)}` : ''}
                    </option>
                  ))}
                </select>
              </label>
              <label className="text-xs text-muted space-y-1">
                <span className="block">مدت (روز)</span>
                <Input
                  value={planDays}
                  onChange={(e) => setPlanDays(e.target.value)}
                  className="w-28"
                  dir="ltr"
                  inputMode="numeric"
                />
              </label>
              <Button
                type="button"
                size="sm"
                onClick={() => assignPlan.mutate()}
                disabled={!planId || daysValue < 1 || assignPlan.isPending}
              >
                {assignPlan.isPending ? 'در حال تخصیص…' : 'تخصیص پلن'}
              </Button>
            </div>
            {!activePlans.length && (
              <p className="text-xs text-amber-300">
                پلنی یافت نشد. ابتدا از بخش «پلن‌ها» یک پلن فعال بسازید.
              </p>
            )}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>کاربران دفتر</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {(office.users ?? []).map((u: { id: number; name: string; mobile: string; email?: string; username?: string; role: string }) => (
            <div key={u.id} className="flex justify-between text-sm border-b border-card-border pb-2">
              <Link to={`/users/${u.id}`} className="text-primary hover:underline">{u.name}</Link>
              <span className="text-muted text-xs" dir="ltr">
                {[u.email, u.username && `@${u.username}`, u.mobile].filter(Boolean).join(' · ')}
              </span>
            </div>
          ))}
          {!(office.users ?? []).length && <p className="text-sm text-muted">کاربری ثبت نشده.</p>}
        </CardContent>
      </Card>
    </div>
  )
}
