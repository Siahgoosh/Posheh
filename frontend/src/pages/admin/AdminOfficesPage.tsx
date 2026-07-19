import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, Building2, BadgeCheck, Globe, Power, CheckCircle, XCircle, Wallet } from 'lucide-react'
import { useState } from 'react'
import api from '@/lib/api'
import { formatJalaliDate, formatNumber, formatPrice } from '@/lib/utils'
import { panelTypeLabel, subscriptionStatusLabel } from '@/constants/plans'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'

interface OfficeRow {
  id: number
  name: string
  slug: string
  subdomain?: string
  city?: string
  panel_type?: string
  is_active: boolean
  plan_active?: boolean
  is_verified?: boolean
  website_status?: string
  show_on_website?: boolean
  trial_ends_at?: string
  properties_count: number
  users?: { id: number; name: string; mobile: string; role: string }[]
  subscription?: { status: string; plan?: { name: string } }
}

const websiteStatusLabel: Record<string, string> = {
  none: 'ندارد', pending: 'در انتظار تأیید', approved: 'تأیید شده',
  published: 'منتشر شده', rejected: 'رد شده',
}

export function AdminOfficesPage() {
  const queryClient = useQueryClient()
  const [walletOfficeId, setWalletOfficeId] = useState<number | null>(null)
  const [walletAmount, setWalletAmount] = useState('100000')
  const [walletDesc, setWalletDesc] = useState('شارژ هدیه توسط مدیر سیستم')
  const { data, isLoading } = useQuery({
    queryKey: ['admin-offices'],
    queryFn: async () => {
      const res = await api.get('/admin/offices')
      return { data: res.data.data as OfficeRow[], total: res.data.total as number }
    },
  })

  const planMutation = useMutation({
    mutationFn: ({ id, active }: { id: number; active: boolean }) =>
      api.put(`/admin/offices/${id}/plan-status`, { plan_active: active }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-offices'] }),
  })

  const websiteMutation = useMutation({
    mutationFn: ({ id, action }: { id: number; action: string }) =>
      api.put(`/admin/offices/${id}/website-status`, { action }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-offices'] }),
  })

  const walletCreditMutation = useMutation({
    mutationFn: ({ id, amount, description }: { id: number; amount: number; description: string }) =>
      api.post(`/admin/offices/${id}/wallet/credit`, { amount, description }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-offices'] })
      queryClient.invalidateQueries({ queryKey: ['admin-wallet', walletOfficeId] })
      setWalletOfficeId(null)
    },
  })

  const { data: walletData } = useQuery({
    queryKey: ['admin-wallet', walletOfficeId],
    queryFn: async () => (await api.get(`/admin/offices/${walletOfficeId}/wallet`)).data.data as {
      balance: number
      transactions: { id: number; type_label: string; amount: number; description: string; created_at?: string }[]
    },
    enabled: !!walletOfficeId,
  })

  const offices = data?.data ?? []

  return (
    <div className="space-y-6 animate-fade-in max-w-5xl mx-auto">
      <div className="flex items-center gap-3">
        <Link to="/admin"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
        <div>
          <h1 className="text-2xl font-bold">مدیریت دفاتر و کاربران</h1>
          <p className="text-sm text-muted">{formatNumber(data?.total ?? offices.length)} دفتر — فعال/غیرفعال پلن و تأیید وبسایت</p>
        </div>
      </div>

      <Card>
        <CardHeader><CardTitle>لیست دفاتر</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          {isLoading ? <p className="text-sm text-muted">بارگذاری…</p> : offices.length === 0 ? (
            <p className="text-sm text-muted">دفتری یافت نشد.</p>
          ) : offices.map((office) => (
            <div key={office.id} className="rounded-xl border border-card-border p-4 space-y-3">
              <div className="flex flex-wrap items-start justify-between gap-2">
                <div className="flex flex-wrap items-center gap-2">
                  <Building2 className="h-4 w-4 text-primary" />
                  <span className="font-medium">{office.name}</span>
                  {office.is_verified && <BadgeCheck className="h-4 w-4 text-primary" />}
                  <Badge variant="outline">{panelTypeLabel(office.panel_type)}</Badge>
                  {office.plan_active === false && <Badge variant="outline" className="text-danger">پلن غیرفعال</Badge>}
                  {!office.is_active && <Badge variant="outline" className="text-danger">دفتر غیرفعال</Badge>}
                </div>
                <span className="text-xs text-muted">{office.city} · {formatNumber(office.properties_count)} ملک</span>
              </div>

              <p className="text-xs text-muted">
                اشتراک: {office.subscription?.plan?.name ?? '—'} ({subscriptionStatusLabel(office.subscription?.status) || 'بدون اشتراک'})
                {office.trial_ends_at && ` · آزمایشی تا ${formatJalaliDate(office.trial_ends_at)}`}
              </p>

              {office.subdomain && (
                <p className="text-xs flex items-center gap-1 text-primary">
                  <Globe className="h-3 w-3" />
                  {office.subdomain}.posheapp.ir — {websiteStatusLabel[office.website_status ?? 'none']}
                </p>
              )}

              <div className="flex flex-wrap gap-2">
                <Button
                  size="sm"
                  variant={office.plan_active !== false ? 'outline' : 'default'}
                  disabled={planMutation.isPending}
                  onClick={() => planMutation.mutate({ id: office.id, active: office.plan_active === false })}
                >
                  <Power className="h-3 w-3 ml-1" />
                  {office.plan_active !== false ? 'غیرفعال کردن پلن' : 'فعال کردن پلن'}
                </Button>

                {office.website_status === 'pending' && (
                  <>
                    <Button size="sm" variant="outline" onClick={() => websiteMutation.mutate({ id: office.id, action: 'approve' })}>
                      <CheckCircle className="h-3 w-3 ml-1" /> تأیید وبسایت
                    </Button>
                    <Button size="sm" variant="outline" className="text-danger" onClick={() => websiteMutation.mutate({ id: office.id, action: 'reject' })}>
                      <XCircle className="h-3 w-3 ml-1" /> رد
                    </Button>
                  </>
                )}
                {office.website_status === 'approved' && (
                  <Button size="sm" onClick={() => websiteMutation.mutate({ id: office.id, action: 'publish' })}>
                    <Globe className="h-3 w-3 ml-1" /> انتشار {office.subdomain}.posheapp.ir
                  </Button>
                )}
                {office.website_status === 'published' && (
                  <a href={`/site/${office.subdomain}`} target="_blank" rel="noreferrer">
                    <Button size="sm" variant="outline">مشاهده وبسایت</Button>
                  </a>
                )}
                <Button size="sm" variant="outline" onClick={() => setWalletOfficeId(office.id)}>
                  <Wallet className="h-3 w-3 ml-1" /> کیف پول
                </Button>
              </div>

              {walletOfficeId === office.id && walletData && (
                <div className="rounded-lg border border-primary/30 bg-primary/5 p-3 space-y-3">
                  <p className="text-sm font-medium">موجودی: {formatPrice(walletData.balance)}</p>
                  <div className="flex flex-wrap gap-2">
                    <Input className="max-w-[140px]" dir="ltr" value={walletAmount} onChange={(e) => setWalletAmount(e.target.value)} placeholder="مبلغ" />
                    <Input className="flex-1 min-w-[160px]" value={walletDesc} onChange={(e) => setWalletDesc(e.target.value)} placeholder="توضیح" />
                    <Button size="sm" disabled={walletCreditMutation.isPending} onClick={() => walletCreditMutation.mutate({ id: office.id, amount: Number(walletAmount), description: walletDesc })}>
                      شارژ حساب
                    </Button>
                    <Button size="sm" variant="ghost" onClick={() => setWalletOfficeId(null)}>بستن</Button>
                  </div>
                  {walletData.transactions.slice(0, 3).map((tx) => (
                    <p key={tx.id} className="text-xs text-muted">{tx.type_label}: {formatPrice(tx.amount)} — {tx.description}</p>
                  ))}
                </div>
              )}

              {office.users && office.users.length > 0 && (
                <div className="flex flex-wrap gap-2">
                  {office.users.map((u) => (
                    <span key={u.id} className="text-xs bg-white/5 rounded-lg px-2 py-1">{u.name} ({u.role})</span>
                  ))}
                </div>
              )}
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
