import { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Wallet, Settings, CheckCircle2, Plus } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { useAuthStore } from '@/stores/auth'

interface Commission {
  id: number
  title: string
  base_amount: number
  rate_percent: number
  commission_amount: number
  status: string
  user?: { name: string }
  deal?: { title: string }
}

export function CommissionsPage() {
  const { user } = useAuthStore()
  const isManager = user?.role === 'office_manager' || user?.role === 'super_admin'
  const queryClient = useQueryClient()
  const [showSettings, setShowSettings] = useState(false)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ user_id: '', title: '', base_amount: '', rate_percent: '30' })
  const [rates, setRates] = useState({ sale_rate_percent: 30, rent_rate_percent: 50 })

  const { data, isLoading } = useQuery({
    queryKey: ['commissions'],
    queryFn: async () => (await api.get('/commissions')).data,
  })

  const { data: settings } = useQuery({
    queryKey: ['commission-settings'],
    queryFn: async () => (await api.get('/commissions/settings')).data.data,
    enabled: isManager,
  })

  const payMutation = useMutation({
    mutationFn: (id: number) => api.post(`/commissions/${id}/pay`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['commissions'] }),
  })

  const saveSettingsMutation = useMutation({
    mutationFn: () => api.put('/commissions/settings', rates),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['commission-settings'] })
      setShowSettings(false)
    },
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/commissions', {
      user_id: parseInt(form.user_id),
      title: form.title,
      base_amount: parseInt(form.base_amount),
      rate_percent: parseInt(form.rate_percent),
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['commissions'] })
      setShowForm(false)
    },
  })

  useEffect(() => {
    if (settings) {
      setRates({ sale_rate_percent: settings.sale_rate_percent, rent_rate_percent: settings.rent_rate_percent })
    }
  }, [settings])

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <Wallet className="h-6 w-6 text-primary" /> کمیسیون مشاوران
          </h1>
          <p className="text-sm text-muted mt-1">محاسبه و تسویه سهم مشاوران از معاملات</p>
        </div>
        {isManager && (
          <div className="flex gap-2">
            <Button variant="outline" size="sm" onClick={() => setShowSettings(!showSettings)}>
              <Settings className="h-4 w-4" /> نرخ‌ها
            </Button>
            <Button size="sm" onClick={() => setShowForm(!showForm)}>
              <Plus className="h-4 w-4" /> ثبت دستی
            </Button>
          </div>
        )}
      </div>

      <div className="grid sm:grid-cols-3 gap-4">
        <Card className="p-4 border-accent/20 bg-accent/5">
          <p className="text-sm text-muted">معوق</p>
          <p className="text-2xl font-bold text-accent">{formatPrice(data?.summary?.pending_total ?? 0)}</p>
          <p className="text-xs text-muted">{data?.summary?.pending_count ?? 0} مورد</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-muted">پرداخت‌شده (ماه)</p>
          <p className="text-2xl font-bold text-success">{formatPrice(data?.summary?.paid_month ?? 0)}</p>
        </Card>
        <Card className="p-4">
          <p className="text-sm text-muted">نرخ فروش / اجاره</p>
          <p className="text-2xl font-bold">{settings?.sale_rate_percent ?? 30}% / {settings?.rent_rate_percent ?? 50}%</p>
        </Card>
      </div>

      {showSettings && isManager && (
        <Card className="p-5 space-y-3">
          <h3 className="font-semibold">تنظیم نرخ کمیسیون</h3>
          <div className="grid sm:grid-cols-2 gap-3">
            <div>
              <label className="text-xs text-muted">فروش (%)</label>
              <Input type="number" value={rates.sale_rate_percent}
                onChange={(e) => setRates((r) => ({ ...r, sale_rate_percent: Number(e.target.value) }))} dir="ltr" />
            </div>
            <div>
              <label className="text-xs text-muted">اجاره (%)</label>
              <Input type="number" value={rates.rent_rate_percent}
                onChange={(e) => setRates((r) => ({ ...r, rent_rate_percent: Number(e.target.value) }))} dir="ltr" />
            </div>
          </div>
          <Button onClick={() => saveSettingsMutation.mutate()}>ذخیره</Button>
        </Card>
      )}

      {showForm && isManager && (
        <Card className="p-5 space-y-3">
          <div className="grid sm:grid-cols-2 gap-3">
            <Input placeholder="شناسه مشاور *" value={form.user_id} onChange={(e) => setForm((f) => ({ ...f, user_id: e.target.value }))} dir="ltr" />
            <Input placeholder="عنوان *" value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} />
            <Input placeholder="مبلغ پایه (تومان)" value={form.base_amount} onChange={(e) => setForm((f) => ({ ...f, base_amount: e.target.value }))} dir="ltr" />
            <Input placeholder="درصد کمیسیون" value={form.rate_percent} onChange={(e) => setForm((f) => ({ ...f, rate_percent: e.target.value }))} dir="ltr" />
          </div>
          <Button onClick={() => createMutation.mutate()} disabled={!form.user_id || !form.title || !form.base_amount}>ثبت</Button>
        </Card>
      )}

      {isLoading ? (
        <div className="flex justify-center py-16"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
      ) : (
        <Card>
          <CardHeader><CardTitle>لیست کمیسیون‌ها</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {(data?.data as Commission[] ?? []).length === 0 && (
              <p className="text-muted text-sm text-center py-8">کمیسیونی ثبت نشده. معامله CRM را به «موفق» ببرید.</p>
            )}
            {(data?.data as Commission[] ?? []).map((c) => (
              <div key={c.id} className="flex items-center justify-between p-4 rounded-xl glass-hover text-sm gap-4">
                <div className="min-w-0">
                  <p className="font-medium truncate">{c.title}</p>
                  <p className="text-xs text-muted">{c.user?.name} · {c.rate_percent}% از {formatPrice(c.base_amount)}</p>
                </div>
                <div className="flex items-center gap-3 shrink-0">
                  <p className="font-bold text-primary">{formatPrice(c.commission_amount)}</p>
                  <Badge variant={c.status === 'paid' ? 'default' : 'outline'}>
                    {c.status === 'paid' ? 'پرداخت‌شده' : 'معوق'}
                  </Badge>
                  {c.status === 'pending' && isManager && (
                    <Button size="sm" variant="outline" onClick={() => payMutation.mutate(c.id)}>
                      <CheckCircle2 className="h-3.5 w-3.5" /> تسویه
                    </Button>
                  )}
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      )}
    </div>
  )
}
