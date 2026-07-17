import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Wallet, Plus, TrendingUp, TrendingDown } from 'lucide-react'
import { useState } from 'react'
import api from '@/lib/api'
import { formatJalaliDate, formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { JalaliDateInput } from '@/components/JalaliDateInput'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { usePlanFeature } from '@/components/SubscriptionGuard'

interface AccountingTx {
  id: number
  type: string
  type_label: string
  title: string
  amount: number
  transaction_date_jalali?: string
}

export function AccountingPage() {
  const hasAccounting = usePlanFeature('accounting')
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ type: 'income', title: '', amount: '', category: '', transaction_date: new Date().toISOString().slice(0, 10) })

  const { data: summary } = useQuery({
    queryKey: ['accounting-summary'],
    queryFn: async () => (await api.get('/accounting/summary')).data.data,
    enabled: hasAccounting,
  })

  const { data: txs } = useQuery({
    queryKey: ['accounting'],
    queryFn: async () => {
      const res = await api.get('/accounting')
      return (res.data.data ?? []) as AccountingTx[]
    },
    enabled: hasAccounting,
  })

  const saveMutation = useMutation({
    mutationFn: () => api.post('/accounting', { ...form, amount: parseInt(form.amount) }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['accounting'] })
      queryClient.invalidateQueries({ queryKey: ['accounting-summary'] })
      setForm((f) => ({ ...f, title: '', amount: '', category: '' }))
    },
  })

  if (!hasAccounting) {
    return <div className="p-8 text-center text-muted">حسابداری دفتر در پلن دفتر املاک و حرفه‌ای فعال است.</div>
  }

  return (
    <div className="space-y-6 animate-fade-in">
      <h1 className="text-2xl font-bold flex items-center gap-2"><Wallet className="h-6 w-6 text-primary" /> حسابداری دفتر</h1>

      <div className="grid sm:grid-cols-3 gap-4">
        <Card><CardContent className="pt-6"><p className="text-sm text-muted">درآمد ماه</p><p className="text-xl font-bold text-success flex items-center gap-1"><TrendingUp className="h-4 w-4" />{formatPrice(summary?.month_income ?? 0)}</p></CardContent></Card>
        <Card><CardContent className="pt-6"><p className="text-sm text-muted">هزینه ماه</p><p className="text-xl font-bold text-danger flex items-center gap-1"><TrendingDown className="h-4 w-4" />{formatPrice(summary?.month_expense ?? 0)}</p></CardContent></Card>
        <Card><CardContent className="pt-6"><p className="text-sm text-muted">مانده ماه</p><p className="text-xl font-bold">{formatPrice(summary?.month_balance ?? 0)}</p></CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle className="flex items-center gap-2"><Plus className="h-4 w-4" /> تراکنش جدید</CardTitle></CardHeader>
        <CardContent className="grid sm:grid-cols-2 gap-3">
          <select className="rounded-xl border border-card-border bg-background/50 p-2 text-sm" value={form.type} onChange={(e) => setForm((f) => ({ ...f, type: e.target.value }))}>
            <option value="income">درآمد</option>
            <option value="expense">هزینه</option>
          </select>
          <Input placeholder="مبلغ (تومان)" value={form.amount} onChange={(e) => setForm((f) => ({ ...f, amount: e.target.value }))} dir="ltr" />
          <Input placeholder="عنوان" value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} />
          <div>
            <JalaliDateInput
              value={form.transaction_date}
              onChange={(transaction_date) => setForm((f) => ({ ...f, transaction_date }))}
            />
            <p className="text-xs text-muted mt-1">تاریخ شمسی: {formatJalaliDate(form.transaction_date)}</p>
          </div>
          <Button className="sm:col-span-2" onClick={() => saveMutation.mutate()} disabled={saveMutation.isPending}>ثبت</Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>تراکنش‌ها</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {txs?.map((t) => (
            <div key={t.id} className="flex justify-between gap-3 text-sm border-b border-card-border pb-2">
              <div>
                <span>{t.title}</span>
                <span className="text-xs text-muted mr-2">({t.type_label})</span>
                {t.transaction_date_jalali && (
                  <p className="text-xs text-muted mt-0.5">{t.transaction_date_jalali}</p>
                )}
              </div>
              <span className={t.type === 'income' ? 'text-success shrink-0' : 'text-danger shrink-0'}>{formatPrice(t.amount)}</span>
            </div>
          ))}
          {!txs?.length && <p className="text-sm text-muted text-center py-4">تراکنشی ثبت نشده</p>}
        </CardContent>
      </Card>
    </div>
  )
}
