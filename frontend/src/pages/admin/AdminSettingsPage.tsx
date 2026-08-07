import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { useState } from 'react'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

const GROUP_LABELS: Record<string, string> = {
  sms: 'پیامک',
  payment: 'پرداخت',
  general: 'عمومی',
  communication: 'مرکز ارتباطات',
}

interface CommStatus {
  telegram_configured?: boolean
  whatsapp_configured?: boolean
  email_from_configured?: boolean
  ai_provider?: string
  ai_openai_configured?: boolean
  email_inbound_domain?: string
  telegram_webhook_url?: string
  email_inbound_url?: string
}

export function AdminSettingsPage() {
  const queryClient = useQueryClient()
  const [draft, setDraft] = useState<Record<string, string>>({})

  const { data, isLoading } = useQuery({
    queryKey: ['admin-settings'],
    queryFn: async () => {
      const res = await api.get('/admin/settings')
      return res.data as {
        data: Record<string, { key: string; value: string; label: string; type: string; is_secret: boolean }[]>
        sms: Record<string, unknown>
        cafe_bazaar: Record<string, unknown>
        communication: CommStatus
      }
    },
  })

  const saveMutation = useMutation({
    mutationFn: (settings: Record<string, string>) => api.put('/admin/settings', { settings }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-settings'] })
      setDraft({})
    },
  })

  const webhookMutation = useMutation({
    mutationFn: async () => (await api.post('/admin/communication/telegram/webhook/register')).data,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-settings'] }),
  })

  const groups = data?.data ?? {}
  const comm = data?.communication

  return (
    <div className="space-y-6 animate-fade-in max-w-3xl">
      <AdminPageHeader title="تنظیمات سیستم" />

      {comm && (
        <Card className="glass border-primary/20">
          <CardHeader><CardTitle>مرکز ارتباطات — وضعیت و Webhook</CardTitle></CardHeader>
          <CardContent className="text-sm space-y-2">
            <div className="flex flex-wrap gap-2">
              <Badge variant={comm.telegram_configured ? 'default' : 'outline'}>
                تلگرام {comm.telegram_configured ? '✓' : '—'}
              </Badge>
              <Badge variant={comm.whatsapp_configured ? 'default' : 'outline'}>
                واتساپ {comm.whatsapp_configured ? '✓' : '—'}
              </Badge>
              <Badge variant={comm.email_from_configured ? 'default' : 'outline'}>
                ایمیل {comm.email_from_configured ? '✓' : '—'}
              </Badge>
              <Badge variant="outline">AI: {comm.ai_provider}</Badge>
            </div>
            <p className="text-muted">دامنه inbound تیکت: <span className="text-foreground">{comm.email_inbound_domain}</span></p>
            <p className="text-muted break-all">Webhook تلگرام: <code className="text-xs">{comm.telegram_webhook_url}</code></p>
            <p className="text-muted break-all">Webhook ایمیل inbound: <code className="text-xs">{comm.email_inbound_url}</code></p>
            <div className="flex flex-wrap gap-2 pt-2">
              <Button
                size="sm"
                variant="outline"
                disabled={!comm.telegram_configured || webhookMutation.isPending}
                onClick={() => webhookMutation.mutate()}
              >
                {webhookMutation.isPending ? 'در حال ثبت…' : 'ثبت Webhook در تلگرام'}
              </Button>
              {webhookMutation.isSuccess && (
                <span className="text-xs text-primary">{(webhookMutation.data as { message?: string })?.message}</span>
              )}
              {webhookMutation.isError && (
                <span className="text-xs text-danger">
                  {(webhookMutation.error as { response?: { data?: { message?: string } } })?.response?.data?.message
                    || 'ثبت webhook ناموفق بود'}
                </span>
              )}
            </div>
            <p className="text-xs text-muted">باز کردن URL webhook در مرورگر فقط health-check است؛ ثبت واقعی با دکمه بالا یا Bot API انجام می‌شود.</p>
          </CardContent>
        </Card>
      )}

      {data?.cafe_bazaar && (
        <Card className="glass border-primary/20">
          <CardHeader><CardTitle>کافه‌بازار</CardTitle></CardHeader>
          <CardContent className="text-sm space-y-1 text-muted">
            <p>پکیج: {(data.cafe_bazaar as { package_name?: string }).package_name}</p>
            <p>توکن API: {(data.cafe_bazaar as { api_configured?: boolean }).api_configured ? '✓ پیکربندی شده' : '✗ تنظیم نشده — روی سرور CAFE_BAZAAR_API_TOKEN'}</p>
          </CardContent>
        </Card>
      )}

      {isLoading ? <p className="text-muted">بارگذاری…</p> : Object.entries(groups).map(([group, items]) => (
        <Card key={group}>
          <CardHeader><CardTitle>{GROUP_LABELS[group] ?? group}</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            {items.map((s) => (
              <div key={s.key}>
                <label className="text-sm text-muted block mb-1">{s.label}</label>
                {s.type === 'select' && s.key === 'comm_ai_provider' ? (
                  <select
                    className="w-full rounded-xl border border-card-border bg-background px-3 py-2 text-sm"
                    defaultValue={s.value}
                    onChange={(e) => setDraft((d) => ({ ...d, [s.key]: e.target.value }))}
                  >
                    <option value="internal">internal (قوانین داخلی)</option>
                    <option value="openai">openai</option>
                  </select>
                ) : (
                  <Input
                    type={s.is_secret ? 'password' : 'text'}
                    defaultValue={s.value}
                    placeholder={s.is_secret && s.value === '********' ? 'بدون تغییر' : ''}
                    onChange={(e) => setDraft((d) => ({ ...d, [s.key]: e.target.value }))}
                    dir={s.key.includes('email') || s.key.includes('whatsapp') ? 'ltr' : undefined}
                  />
                )}
              </div>
            ))}
          </CardContent>
        </Card>
      ))}

      <Button onClick={() => saveMutation.mutate(draft)} disabled={!Object.keys(draft).length}>
        ذخیره تغییرات
      </Button>
    </div>
  )
}
