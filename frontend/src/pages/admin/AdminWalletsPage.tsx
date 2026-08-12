import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { formatPrice, toEnglishDigits } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { extractApiError } from '@/lib/apiError'

type WalletRow = {
  id: number
  balance: number
  office?: { id: number; name: string }
}

type OfficeRow = { id: number; name: string }

function parseAmount(raw: string): number {
  const cleaned = toEnglishDigits(raw).replace(/[^\d]/g, '')
  if (!cleaned) return 0
  return parseInt(cleaned, 10) || 0
}

export function AdminWalletsPage() {
  const [officeId, setOfficeId] = useState('')
  const [amount, setAmount] = useState('')
  const [desc, setDesc] = useState('')
  const [type, setType] = useState<'credit' | 'debit'>('credit')
  const [feedback, setFeedback] = useState<{ type: 'ok' | 'err'; text: string } | null>(null)
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-wallets'],
    queryFn: async () => {
      const res = await api.get('/admin/wallets')
      return res.data.data as WalletRow[]
    },
  })

  const { data: offices } = useQuery({
    queryKey: ['admin-offices-lite'],
    queryFn: async () => {
      const res = await api.get('/admin/offices', { params: { per_page: 100 } })
      return (res.data.data as OfficeRow[]) ?? []
    },
  })

  const amountValue = parseAmount(amount)
  const officeOptions = useMemo(() => {
    const fromOffices = offices ?? []
    if (fromOffices.length) return fromOffices
    return (data ?? [])
      .map((w) => w.office)
      .filter((o): o is OfficeRow => !!o?.id)
  }, [offices, data])

  const adjustMutation = useMutation({
    mutationFn: () => api.post(`/admin/offices/${officeId}/wallet/adjust`, {
      amount: amountValue,
      type,
      description: desc.trim() || (type === 'credit' ? 'شارژ دستی توسط مدیر' : 'برداشت دستی'),
    }),
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: ['admin-wallets'] })
      setAmount('')
      setDesc('')
      setFeedback({ type: 'ok', text: res.data?.message || 'عملیات کیف پول انجام شد.' })
    },
    onError: (err) => setFeedback({ type: 'err', text: extractApiError(err) }),
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="کیف پول دفاتر" />

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

      <Card>
        <CardHeader><CardTitle>شارژ / برداشت دستی</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <div className="flex flex-wrap gap-2 items-end">
            <label className="text-xs text-muted space-y-1">
              <span className="block">دفتر</span>
              <select
                value={officeId}
                onChange={(e) => setOfficeId(e.target.value)}
                className="block min-w-[220px] rounded-xl border border-card-border bg-background px-3 py-2 text-sm"
              >
                <option value="">— انتخاب دفتر —</option>
                {officeOptions.map((o) => (
                  <option key={o.id} value={o.id}>#{o.id} · {o.name}</option>
                ))}
              </select>
            </label>
            <label className="text-xs text-muted space-y-1">
              <span className="block">نوع</span>
              <select
                value={type}
                onChange={(e) => setType(e.target.value as 'credit' | 'debit')}
                className="block rounded-xl border border-card-border bg-background px-3 py-2 text-sm"
              >
                <option value="credit">شارژ</option>
                <option value="debit">برداشت</option>
              </select>
            </label>
            <label className="text-xs text-muted space-y-1">
              <span className="block">مبلغ (تومان)</span>
              <Input
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
                className="w-40"
                dir="ltr"
                inputMode="numeric"
                placeholder="مثلاً 500000"
              />
            </label>
            <label className="text-xs text-muted space-y-1 flex-1 min-w-[180px]">
              <span className="block">توضیح</span>
              <Input value={desc} onChange={(e) => setDesc(e.target.value)} placeholder="اختیاری" />
            </label>
          </div>
          <Button
            type="button"
            onClick={() => adjustMutation.mutate()}
            disabled={!officeId || amountValue < 1 || adjustMutation.isPending}
          >
            {adjustMutation.isPending
              ? 'در حال اعمال…'
              : type === 'credit' ? 'شارژ کیف پول' : 'برداشت از کیف پول'}
          </Button>
          {amountValue > 0 && <p className="text-xs text-muted">مبلغ نهایی: {formatPrice(amountValue)}</p>}
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>موجودی دفاتر</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : data?.map((w) => (
            <div key={w.id} className="flex justify-between items-center gap-2 text-sm border-b border-card-border pb-2">
              <div>
                <span>{w.office?.name}</span>
                <span className="text-muted text-xs mr-2">#{w.office?.id}</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="font-medium">{formatPrice(w.balance)}</span>
                {w.office?.id && (
                  <>
                    <Button
                      type="button"
                      size="sm"
                      variant="outline"
                      onClick={() => {
                        setOfficeId(String(w.office!.id))
                        setType('credit')
                      }}
                    >
                      شارژ
                    </Button>
                    <Link to={`/tenants/${w.office.id}`}>
                      <Button type="button" size="sm" variant="ghost">جزئیات</Button>
                    </Link>
                  </>
                )}
              </div>
            </div>
          ))}
          {!isLoading && !data?.length && <p className="text-sm text-muted">هنوز کیف پولی ثبت نشده — با اولین شارژ ساخته می‌شود.</p>}
        </CardContent>
      </Card>
    </div>
  )
}
