import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  Wallet, Plus, TrendingUp, TrendingDown, Landmark, Receipt,
  ArrowLeftRight, FileSpreadsheet, Bell, Calculator, Banknote,
} from 'lucide-react'
import { useMemo, useState } from 'react'
import api from '@/lib/api'
import { formatJalaliDate, formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { usePlanFeature } from '@/components/SubscriptionGuard'
import { cn } from '@/lib/utils'

type Tab = 'dashboard' | 'transactions' | 'cash' | 'cheques' | 'reports' | 'accounts' | 'consultants' | 'parties' | 'settlements'

interface AccountingTx {
  id: number
  type: string
  type_label: string
  title: string
  amount: number
  status?: string
  payment_method?: string
  transaction_date_jalali?: string
  category?: string
}

const TABS: { id: Tab; label: string; icon: typeof Wallet }[] = [
  { id: 'dashboard', label: 'داشبورد', icon: Calculator },
  { id: 'transactions', label: 'دریافت/پرداخت', icon: Receipt },
  { id: 'cash', label: 'صندوق و بانک', icon: Landmark },
  { id: 'cheques', label: 'چک‌ها', icon: FileSpreadsheet },
  { id: 'settlements', label: 'تسویه‌ها', icon: ArrowLeftRight },
  { id: 'consultants', label: 'مشاوران', icon: Banknote },
  { id: 'parties', label: 'بدهکار/بستانکار', icon: Bell },
  { id: 'reports', label: 'سود و زیان', icon: TrendingUp },
  { id: 'accounts', label: 'سرفصل‌ها', icon: Banknote },
]

export function AccountingPage() {
  const hasAccounting = usePlanFeature('accounting')
  const queryClient = useQueryClient()
  const [tab, setTab] = useState<Tab>('dashboard')
  const [form, setForm] = useState({
    type: 'income',
    title: '',
    amount: '',
    category: '',
    payment_method: 'cash',
    cash_account_id: '',
    transaction_date: new Date().toISOString().slice(0, 10),
  })
  const [transfer, setTransfer] = useState({
    from_cash_account_id: '',
    to_cash_account_id: '',
    amount: '',
    transaction_date: new Date().toISOString().slice(0, 10),
    description: '',
  })
  const [chequeForm, setChequeForm] = useState({
    direction: 'in',
    cheque_number: '',
    amount: '',
    bank_name: '',
    due_date: new Date().toISOString().slice(0, 10),
    issuer_name: '',
  })
  const [plRange, setPlRange] = useState({
    from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10),
    to: new Date().toISOString().slice(0, 10),
  })

  const { data: dashboard } = useQuery({
    queryKey: ['accounting-dashboard'],
    queryFn: async () => (await api.get('/accounting/dashboard')).data.data,
    enabled: hasAccounting,
  })

  const summary = dashboard?.summary

  const { data: txs } = useQuery({
    queryKey: ['accounting'],
    queryFn: async () => {
      const res = await api.get('/accounting')
      return (res.data.data ?? []) as AccountingTx[]
    },
    enabled: hasAccounting && (tab === 'transactions' || tab === 'dashboard'),
  })

  const { data: cashAccounts } = useQuery({
    queryKey: ['accounting-cash'],
    queryFn: async () => (await api.get('/accounting/cash-accounts')).data.data as Array<{
      id: number; name: string; kind: string; bank_name?: string
    }>,
    enabled: hasAccounting,
  })

  const { data: accounts } = useQuery({
    queryKey: ['accounting-accounts'],
    queryFn: async () => (await api.get('/accounting/accounts')).data.data as Array<{
      id: number; code: string; name: string; group_key: string; type: string
    }>,
    enabled: hasAccounting && tab === 'accounts',
  })

  const { data: chequesPage } = useQuery({
    queryKey: ['accounting-cheques'],
    queryFn: async () => (await api.get('/accounting/cheques')).data,
    enabled: hasAccounting && tab === 'cheques',
  })

  const { data: pl } = useQuery({
    queryKey: ['accounting-pl', plRange],
    queryFn: async () => (await api.get('/accounting/reports/profit-loss', { params: plRange })).data.data,
    enabled: hasAccounting && tab === 'reports',
  })

  const { data: consultantsReport } = useQuery({
    queryKey: ['accounting-consultants'],
    queryFn: async () => (await api.get('/accounting/reports/consultants')).data.data,
    enabled: hasAccounting && tab === 'consultants',
  })

  const { data: debtors } = useQuery({
    queryKey: ['accounting-debtors'],
    queryFn: async () => (await api.get('/accounting/reports/debtors')).data.data,
    enabled: hasAccounting && tab === 'parties',
  })

  const { data: creditors } = useQuery({
    queryKey: ['accounting-creditors'],
    queryFn: async () => (await api.get('/accounting/reports/creditors')).data.data,
    enabled: hasAccounting && tab === 'parties',
  })

  const { data: settlementsPage } = useQuery({
    queryKey: ['accounting-settlements'],
    queryFn: async () => (await api.get('/accounting/settlements')).data,
    enabled: hasAccounting && tab === 'settlements',
  })

  const invalidateAll = () => {
    queryClient.invalidateQueries({ queryKey: ['accounting'] })
    queryClient.invalidateQueries({ queryKey: ['accounting-dashboard'] })
    queryClient.invalidateQueries({ queryKey: ['accounting-summary'] })
    queryClient.invalidateQueries({ queryKey: ['accounting-cash'] })
    queryClient.invalidateQueries({ queryKey: ['accounting-cheques'] })
    queryClient.invalidateQueries({ queryKey: ['accounting-pl'] })
    queryClient.invalidateQueries({ queryKey: ['accounting-consultants'] })
    queryClient.invalidateQueries({ queryKey: ['accounting-debtors'] })
    queryClient.invalidateQueries({ queryKey: ['accounting-creditors'] })
    queryClient.invalidateQueries({ queryKey: ['accounting-settlements'] })
    queryClient.invalidateQueries({ queryKey: ['commissions'] })
  }

  const saveMutation = useMutation({
    mutationFn: () => api.post('/accounting', {
      ...form,
      amount: parseInt(form.amount, 10),
      cash_account_id: form.cash_account_id ? Number(form.cash_account_id) : undefined,
    }),
    onSuccess: () => {
      invalidateAll()
      setForm((f) => ({ ...f, title: '', amount: '', category: '' }))
    },
  })

  const transferMutation = useMutation({
    mutationFn: () => api.post('/accounting/transfer', {
      ...transfer,
      from_cash_account_id: Number(transfer.from_cash_account_id),
      to_cash_account_id: Number(transfer.to_cash_account_id),
      amount: parseInt(transfer.amount, 10),
    }),
    onSuccess: () => {
      invalidateAll()
      setTransfer((t) => ({ ...t, amount: '', description: '' }))
    },
  })

  const chequeMutation = useMutation({
    mutationFn: () => api.post('/accounting/cheques', {
      ...chequeForm,
      amount: parseInt(chequeForm.amount, 10),
    }),
    onSuccess: () => {
      invalidateAll()
      setChequeForm((f) => ({ ...f, cheque_number: '', amount: '', issuer_name: '' }))
    },
  })

  const chequeStatusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      api.post(`/accounting/cheques/${id}/status`, { status }),
    onSuccess: invalidateAll,
  })

  const voidMutation = useMutation({
    mutationFn: (id: number) => api.post(`/accounting/${id}/void`, { reason: 'ابطال از پنل' }),
    onSuccess: invalidateAll,
  })

  const bootstrapMutation = useMutation({
    mutationFn: () => api.post('/accounting/bootstrap'),
    onSuccess: invalidateAll,
  })

  const cashOptions = useMemo(() => cashAccounts ?? [], [cashAccounts])
  const cheques = chequesPage?.data ?? []
  const settlements = settlementsPage?.data ?? []
  const consultantRows = consultantsReport?.consultants ?? []
  const debtorItems = debtors?.items ?? []
  const creditorItems = creditors?.items ?? []

  if (!hasAccounting) {
    return <div className="p-8 text-center text-muted">حسابداری دفتر در پلن دفتر املاک و حرفه‌ای فعال است.</div>
  }

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <Wallet className="h-6 w-6 text-primary" /> حسابداری دفتر
        </h1>
        <Button size="sm" variant="outline" onClick={() => bootstrapMutation.mutate()} disabled={bootstrapMutation.isPending}>
          آماده‌سازی سرفصل‌ها
        </Button>
      </div>

      <div className="flex flex-wrap gap-2 border-b border-card-border pb-2">
        {TABS.map((t) => (
          <button
            key={t.id}
            type="button"
            onClick={() => setTab(t.id)}
            className={cn(
              'inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm transition-colors',
              tab === t.id ? 'bg-primary/15 text-primary' : 'text-muted hover:bg-white/5',
            )}
          >
            <t.icon className="h-4 w-4" />
            {t.label}
          </button>
        ))}
      </div>

      {tab === 'dashboard' && (
        <div className="space-y-4">
          <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <Card><CardContent className="pt-5"><p className="text-xs text-muted">موجودی نقد</p><p className="text-xl font-bold">{formatPrice(summary?.cash_balance ?? 0)}</p></CardContent></Card>
            <Card><CardContent className="pt-5"><p className="text-xs text-muted">موجودی بانک</p><p className="text-xl font-bold">{formatPrice(summary?.bank_balance ?? 0)}</p></CardContent></Card>
            <Card><CardContent className="pt-5"><p className="text-xs text-muted">درآمد این ماه</p><p className="text-xl font-bold text-success flex items-center gap-1"><TrendingUp className="h-4 w-4" />{formatPrice(summary?.month_income ?? 0)}</p></CardContent></Card>
            <Card><CardContent className="pt-5"><p className="text-xs text-muted">هزینه این ماه</p><p className="text-xl font-bold text-danger flex items-center gap-1"><TrendingDown className="h-4 w-4" />{formatPrice(summary?.month_expense ?? 0)}</p></CardContent></Card>
            <Card><CardContent className="pt-5"><p className="text-xs text-muted">سود ماه</p><p className="text-xl font-bold">{formatPrice(summary?.month_profit ?? 0)}</p></CardContent></Card>
            <Card><CardContent className="pt-5"><p className="text-xs text-muted">طلب مشاوران</p><p className="text-xl font-bold text-accent">{formatPrice(summary?.consultant_payables ?? 0)}</p></CardContent></Card>
            <Card><CardContent className="pt-5"><p className="text-xs text-muted">درآمد امروز</p><p className="text-xl font-bold">{formatPrice(summary?.today_income ?? 0)}</p></CardContent></Card>
            <Card><CardContent className="pt-5"><p className="text-xs text-muted">هزینه امروز</p><p className="text-xl font-bold">{formatPrice(summary?.today_expense ?? 0)}</p></CardContent></Card>
          </div>

          <div className="flex flex-wrap gap-2">
            <Button size="sm" onClick={() => { setTab('transactions'); setForm((f) => ({ ...f, type: 'income' })) }}><Plus className="h-4 w-4" /> دریافت / درآمد</Button>
            <Button size="sm" variant="outline" onClick={() => { setTab('transactions'); setForm((f) => ({ ...f, type: 'expense' })) }}>پرداخت / هزینه</Button>
            <Button size="sm" variant="outline" onClick={() => setTab('cash')}><ArrowLeftRight className="h-4 w-4" /> انتقال</Button>
            <Button size="sm" variant="outline" onClick={() => setTab('cheques')}>چک جدید</Button>
          </div>

          {(dashboard?.cheque_alerts?.length ?? 0) > 0 && (
            <Card className="border-warning/30">
              <CardHeader><CardTitle className="text-sm flex items-center gap-2"><Bell className="h-4 w-4 text-warning" /> چک‌های نزدیک سررسید</CardTitle></CardHeader>
              <CardContent className="space-y-2">
                {dashboard.cheque_alerts.map((c: { id: number; cheque_number: string; amount: number; due_date: string; direction: string }) => (
                  <div key={c.id} className="flex justify-between text-sm border-b border-card-border pb-2">
                    <span>چک {c.cheque_number} ({c.direction === 'in' ? 'دریافتی' : 'پرداختی'})</span>
                    <span>{formatPrice(c.amount)} · {formatJalaliDate(c.due_date)}</span>
                  </div>
                ))}
              </CardContent>
            </Card>
          )}

          <Card>
            <CardHeader><CardTitle className="text-sm">تراکنش‌های اخیر</CardTitle></CardHeader>
            <CardContent className="space-y-2">
              {(dashboard?.recent_transactions ?? txs?.slice(0, 8) ?? []).map((t: AccountingTx) => (
                <div key={t.id} className="flex justify-between text-sm border-b border-card-border pb-2">
                  <span>{t.title} <span className="text-muted text-xs">({t.type_label || t.type})</span></span>
                  <span className={t.type === 'income' || t.type === 'transfer_in' ? 'text-success' : 'text-danger'}>{formatPrice(t.amount)}</span>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      )}

      {tab === 'transactions' && (
        <div className="grid lg:grid-cols-2 gap-4">
          <Card>
            <CardHeader><CardTitle className="flex items-center gap-2"><Plus className="h-4 w-4" /> ثبت درآمد / هزینه</CardTitle></CardHeader>
            <CardContent className="grid gap-3">
              <select className="rounded-xl border border-card-border bg-background/50 p-2 text-sm" value={form.type} onChange={(e) => setForm((f) => ({ ...f, type: e.target.value }))}>
                <option value="income">درآمد / دریافت</option>
                <option value="expense">هزینه / پرداخت</option>
              </select>
              <Input placeholder="مبلغ (تومان)" value={form.amount} onChange={(e) => setForm((f) => ({ ...f, amount: e.target.value }))} dir="ltr" />
              <Input placeholder="عنوان" value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} />
              <Input placeholder="دسته‌بندی (اختیاری)" value={form.category} onChange={(e) => setForm((f) => ({ ...f, category: e.target.value }))} />
              <select className="rounded-xl border border-card-border bg-background/50 p-2 text-sm" value={form.payment_method} onChange={(e) => setForm((f) => ({ ...f, payment_method: e.target.value }))}>
                <option value="cash">نقد</option>
                <option value="bank">بانک</option>
                <option value="pos">کارت‌خوان</option>
                <option value="cheque">چک</option>
                <option value="transfer">انتقال</option>
              </select>
              <select className="rounded-xl border border-card-border bg-background/50 p-2 text-sm" value={form.cash_account_id} onChange={(e) => setForm((f) => ({ ...f, cash_account_id: e.target.value }))}>
                <option value="">صندوق/بانک پیش‌فرض</option>
                {cashOptions.map((c) => <option key={c.id} value={c.id}>{c.name} ({c.kind === 'cashbox' ? 'صندوق' : 'بانک'})</option>)}
              </select>
              <div>
                <Input type="date" value={form.transaction_date} onChange={(e) => setForm((f) => ({ ...f, transaction_date: e.target.value }))} dir="ltr" />
                <p className="text-xs text-muted mt-1">شمسی: {formatJalaliDate(form.transaction_date)}</p>
              </div>
              <Button onClick={() => saveMutation.mutate()} disabled={saveMutation.isPending || !form.title || !form.amount}>ثبت در دفتر کل</Button>
              {saveMutation.isError && <p className="text-xs text-danger">ثبت ناموفق بود. دسترسی پلن/مبالغ را بررسی کنید.</p>}
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle>لیست تراکنش‌ها</CardTitle></CardHeader>
            <CardContent className="space-y-2 max-h-[32rem] overflow-y-auto">
              {txs?.map((t) => (
                <div key={t.id} className="flex justify-between gap-3 text-sm border-b border-card-border pb-2">
                  <div>
                    <span>{t.title}</span>
                    <span className="text-xs text-muted mr-2">({t.type_label})</span>
                    {t.transaction_date_jalali && <p className="text-xs text-muted mt-0.5">{t.transaction_date_jalali}</p>}
                  </div>
                  <div className="text-left shrink-0 space-y-1">
                    <p className={t.type === 'income' ? 'text-success' : 'text-danger'}>{formatPrice(t.amount)}</p>
                    {t.status !== 'void' && (
                      <button type="button" className="text-[10px] text-muted hover:text-danger" onClick={() => voidMutation.mutate(t.id)}>ابطال</button>
                    )}
                  </div>
                </div>
              ))}
              {!txs?.length && <p className="text-sm text-muted text-center py-4">تراکنشی ثبت نشده</p>}
            </CardContent>
          </Card>
        </div>
      )}

      {tab === 'cash' && (
        <div className="grid lg:grid-cols-2 gap-4">
          <Card>
            <CardHeader><CardTitle>صندوق و بانک‌ها</CardTitle></CardHeader>
            <CardContent className="space-y-2">
              {(summary?.cash_accounts ?? []).map((c: { id: number; name: string; kind: string; balance: number }) => (
                <div key={c.id} className="flex justify-between text-sm border-b border-card-border pb-2">
                  <span>{c.name} <Badge variant="outline" className="text-[10px] mr-1">{c.kind === 'cashbox' ? 'صندوق' : 'بانک'}</Badge></span>
                  <span className="font-medium">{formatPrice(c.balance)}</span>
                </div>
              ))}
              {!summary?.cash_accounts?.length && <p className="text-sm text-muted">ابتدا «آماده‌سازی سرفصل‌ها» را بزنید.</p>}
            </CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle className="flex items-center gap-2"><ArrowLeftRight className="h-4 w-4" /> انتقال بین حساب‌ها</CardTitle></CardHeader>
            <CardContent className="grid gap-3">
              <select className="rounded-xl border border-card-border bg-background/50 p-2 text-sm" value={transfer.from_cash_account_id} onChange={(e) => setTransfer((t) => ({ ...t, from_cash_account_id: e.target.value }))}>
                <option value="">از حساب</option>
                {cashOptions.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
              <select className="rounded-xl border border-card-border bg-background/50 p-2 text-sm" value={transfer.to_cash_account_id} onChange={(e) => setTransfer((t) => ({ ...t, to_cash_account_id: e.target.value }))}>
                <option value="">به حساب</option>
                {cashOptions.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
              <Input placeholder="مبلغ" value={transfer.amount} onChange={(e) => setTransfer((t) => ({ ...t, amount: e.target.value }))} dir="ltr" />
              <Input placeholder="توضیح" value={transfer.description} onChange={(e) => setTransfer((t) => ({ ...t, description: e.target.value }))} />
              <Button onClick={() => transferMutation.mutate()} disabled={transferMutation.isPending}>ثبت انتقال (بدون درآمد/هزینه)</Button>
            </CardContent>
          </Card>
        </div>
      )}

      {tab === 'cheques' && (
        <div className="grid lg:grid-cols-2 gap-4">
          <Card>
            <CardHeader><CardTitle>ثبت چک</CardTitle></CardHeader>
            <CardContent className="grid gap-3">
              <select className="rounded-xl border border-card-border bg-background/50 p-2 text-sm" value={chequeForm.direction} onChange={(e) => setChequeForm((f) => ({ ...f, direction: e.target.value }))}>
                <option value="in">چک دریافتی</option>
                <option value="out">چک پرداختی</option>
              </select>
              <Input placeholder="شماره چک" value={chequeForm.cheque_number} onChange={(e) => setChequeForm((f) => ({ ...f, cheque_number: e.target.value }))} dir="ltr" />
              <Input placeholder="مبلغ" value={chequeForm.amount} onChange={(e) => setChequeForm((f) => ({ ...f, amount: e.target.value }))} dir="ltr" />
              <Input placeholder="بانک" value={chequeForm.bank_name} onChange={(e) => setChequeForm((f) => ({ ...f, bank_name: e.target.value }))} />
              <Input placeholder="صادرکننده" value={chequeForm.issuer_name} onChange={(e) => setChequeForm((f) => ({ ...f, issuer_name: e.target.value }))} />
              <div>
                <Input type="date" value={chequeForm.due_date} onChange={(e) => setChequeForm((f) => ({ ...f, due_date: e.target.value }))} dir="ltr" />
                <p className="text-xs text-muted mt-1">سررسید شمسی: {formatJalaliDate(chequeForm.due_date)}</p>
              </div>
              <Button onClick={() => chequeMutation.mutate()} disabled={chequeMutation.isPending}>ثبت چک</Button>
            </CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle>لیست چک‌ها</CardTitle></CardHeader>
            <CardContent className="space-y-2 max-h-[32rem] overflow-y-auto">
              {cheques.map((c: { id: number; cheque_number: string; amount: number; due_date: string; status: string; direction: string }) => (
                <div key={c.id} className="border-b border-card-border pb-2 text-sm space-y-1">
                  <div className="flex justify-between">
                    <span>چک {c.cheque_number} · {c.direction === 'in' ? 'دریافتی' : 'پرداختی'}</span>
                    <span>{formatPrice(c.amount)}</span>
                  </div>
                  <div className="flex justify-between items-center text-xs text-muted">
                    <span>{formatJalaliDate(c.due_date)} · {c.status}</span>
                    <div className="flex gap-1">
                      {c.direction === 'in' && c.status !== 'cleared' && (
                        <button type="button" className="text-success" onClick={() => chequeStatusMutation.mutate({ id: c.id, status: 'cleared' })}>وصول</button>
                      )}
                      {c.status !== 'bounced' && (
                        <button type="button" className="text-danger" onClick={() => chequeStatusMutation.mutate({ id: c.id, status: 'bounced' })}>برگشت</button>
                      )}
                    </div>
                  </div>
                </div>
              ))}
              {!cheques.length && <p className="text-sm text-muted text-center py-4">چکی ثبت نشده</p>}
            </CardContent>
          </Card>
        </div>
      )}

      {tab === 'settlements' && (
        <Card>
          <CardHeader><CardTitle>تسویه‌های ثبت‌شده</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {settlements.map((s: { id: number; settlement_number: string; amount: number; kind: string; settlement_date: string; description?: string }) => (
              <div key={s.id} className="flex justify-between text-sm border-b border-card-border pb-2">
                <div>
                  <p>{s.settlement_number} · {s.kind === 'consultant' ? 'مشاور' : s.kind}</p>
                  <p className="text-xs text-muted">{s.description} · {formatJalaliDate(s.settlement_date)}</p>
                </div>
                <span className="font-medium">{formatPrice(s.amount)}</span>
              </div>
            ))}
            {!settlements.length && <p className="text-sm text-muted text-center py-4">تسویه‌ای ثبت نشده — از صفحه کمیسیون‌ها تسویه کنید.</p>}
          </CardContent>
        </Card>
      )}

      {tab === 'consultants' && (
        <Card>
          <CardHeader><CardTitle>گزارش حساب مشاوران</CardTitle></CardHeader>
          <CardContent className="space-y-2 overflow-x-auto">
            <div className="min-w-[640px] space-y-2">
              <div className="grid grid-cols-7 gap-2 text-[11px] text-muted border-b border-card-border pb-2">
                <span>مشاور</span><span>معاملات</span><span>کمیسیون</span><span>سهم دفتر</span><span>سهم مشاور</span><span>پرداختی</span><span>مانده</span>
              </div>
              {consultantRows.map((r: { consultant_id: number; name?: string; deals_count: number; total_commission: number; office_share: number; consultant_share: number; paid_amount: number; balance: number }) => (
                <div key={r.consultant_id} className="grid grid-cols-7 gap-2 text-sm border-b border-card-border pb-2">
                  <span>{r.name || `#${r.consultant_id}`}</span>
                  <span>{r.deals_count}</span>
                  <span>{formatPrice(r.total_commission)}</span>
                  <span>{formatPrice(r.office_share)}</span>
                  <span>{formatPrice(r.consultant_share)}</span>
                  <span>{formatPrice(r.paid_amount)}</span>
                  <span className="text-accent">{formatPrice(r.balance)}</span>
                </div>
              ))}
              {!consultantRows.length && <p className="text-sm text-muted text-center py-4">هنوز کمیسیونی ثبت نشده</p>}
            </div>
          </CardContent>
        </Card>
      )}

      {tab === 'parties' && (
        <div className="grid lg:grid-cols-2 gap-4">
          <Card>
            <CardHeader><CardTitle>بدهکاران</CardTitle></CardHeader>
            <CardContent className="space-y-2">
              {debtorItems.map((p: { party_id: number; name?: string; mobile?: string; amount: number }) => (
                <div key={`d-${p.party_id}`} className="flex justify-between text-sm border-b border-card-border pb-2">
                  <div>
                    <p>{p.name || `شخص #${p.party_id}`}</p>
                    {p.mobile && <p className="text-xs text-muted" dir="ltr">{p.mobile}</p>}
                  </div>
                  <span className="text-danger">{formatPrice(p.amount)}</span>
                </div>
              ))}
              {!debtorItems.length && <p className="text-sm text-muted text-center py-4">بدهکاری ثبت نشده</p>}
            </CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle>بستانکاران</CardTitle></CardHeader>
            <CardContent className="space-y-2">
              {creditorItems.map((p: { party_id: number; name?: string; mobile?: string; amount: number }) => (
                <div key={`c-${p.party_id}`} className="flex justify-between text-sm border-b border-card-border pb-2">
                  <div>
                    <p>{p.name || `شخص #${p.party_id}`}</p>
                    {p.mobile && <p className="text-xs text-muted" dir="ltr">{p.mobile}</p>}
                  </div>
                  <span className="text-success">{formatPrice(p.amount)}</span>
                </div>
              ))}
              {!creditorItems.length && <p className="text-sm text-muted text-center py-4">بستانکاری ثبت نشده</p>}
            </CardContent>
          </Card>
        </div>
      )}

      {tab === 'reports' && (
        <Card>
          <CardHeader>
            <CardTitle>گزارش سود و زیان</CardTitle>
            <div className="flex flex-wrap gap-2 mt-2">
              <Input type="date" value={plRange.from} onChange={(e) => setPlRange((r) => ({ ...r, from: e.target.value }))} dir="ltr" />
              <Input type="date" value={plRange.to} onChange={(e) => setPlRange((r) => ({ ...r, to: e.target.value }))} dir="ltr" />
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid sm:grid-cols-3 gap-3">
              <div className="rounded-xl border border-card-border p-3"><p className="text-xs text-muted">جمع درآمد</p><p className="text-lg font-bold text-success">{formatPrice(pl?.total_income ?? 0)}</p></div>
              <div className="rounded-xl border border-card-border p-3"><p className="text-xs text-muted">جمع هزینه</p><p className="text-lg font-bold text-danger">{formatPrice(pl?.total_expenses ?? 0)}</p></div>
              <div className="rounded-xl border border-card-border p-3"><p className="text-xs text-muted">سود خالص</p><p className="text-lg font-bold">{formatPrice(pl?.net_profit ?? 0)}</p></div>
            </div>
            <div className="grid md:grid-cols-2 gap-4">
              <div>
                <p className="font-medium mb-2">درآمدها</p>
                {(pl?.income ?? []).map((r: { code: string; account: string; amount: number }) => (
                  <div key={r.code} className="flex justify-between text-sm border-b border-card-border py-1"><span>{r.account}</span><span>{formatPrice(r.amount)}</span></div>
                ))}
              </div>
              <div>
                <p className="font-medium mb-2">هزینه‌ها</p>
                {(pl?.expenses ?? []).map((r: { code: string; account: string; amount: number }) => (
                  <div key={r.code} className="flex justify-between text-sm border-b border-card-border py-1"><span>{r.account}</span><span>{formatPrice(r.amount)}</span></div>
                ))}
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {tab === 'accounts' && (
        <Card>
          <CardHeader><CardTitle>سرفصل حساب‌ها (Chart of Accounts)</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {accounts?.map((a) => (
              <div key={a.id} className="flex justify-between text-sm border-b border-card-border pb-2">
                <span><span className="text-muted dir-ltr ml-2" dir="ltr">{a.code}</span>{a.name}</span>
                <Badge variant="outline">{a.group_key}</Badge>
              </div>
            ))}
            {!accounts?.length && <p className="text-sm text-muted">سرفصلی نیست — آماده‌سازی را اجرا کنید.</p>}
          </CardContent>
        </Card>
      )}
    </div>
  )
}
