import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { CheckCircle2, AlertCircle, Settings, Send, Save, KeyRound } from 'lucide-react'
import { useCallback, useEffect, useState } from 'react'
import api from '@/lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

interface SettingItem {
  key: string
  value: string
  has_value: boolean
  label: string
  type: string
  is_secret: boolean
}

interface SmsStatus {
  sms_mode: string
  is_live: boolean
  has_api_key: boolean
  has_credentials: boolean
  has_from_number: boolean
  is_ready: boolean
}

interface SettingsResponse {
  data: Record<string, SettingItem[]>
  sms_status: SmsStatus
}

type FormState = Record<string, string>
type SecretDrafts = Record<string, string>

function buildFormFromSettings(groups: Record<string, SettingItem[]>): FormState {
  const form: FormState = {}
  Object.values(groups)
    .flat()
    .forEach((item) => {
      if (item.is_secret) return
      form[item.key] = item.value ?? ''
    })
  return form
}

export function AdminSettingsPage() {
  const queryClient = useQueryClient()
  const [testMobile, setTestMobile] = useState('09170577873')
  const [form, setForm] = useState<FormState>({})
  const [secretDrafts, setSecretDrafts] = useState<SecretDrafts>({})
  const [feedback, setFeedback] = useState<{ type: 'success' | 'error' | 'info'; text: string } | null>(null)
  const [hydrated, setHydrated] = useState(false)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['admin-settings'],
    queryFn: async () => (await api.get<SettingsResponse>('/admin/settings')).data,
  })

  useEffect(() => {
    if (!data?.data || hydrated) return
    setForm(buildFormFromSettings(data.data))
    setHydrated(true)
  }, [data, hydrated])

  const buildPayload = useCallback((): FormState => {
    const payload: FormState = { ...form }
    Object.entries(secretDrafts).forEach(([key, value]) => {
      if (value.trim()) payload[key] = value.trim()
    })
    return payload
  }, [form, secretDrafts])

  const applyServerResponse = (response: SettingsResponse, message: string, type: 'success' | 'error' = 'success') => {
    queryClient.setQueryData(['admin-settings'], response)
    setForm(buildFormFromSettings(response.data))
    setSecretDrafts({})
    setHydrated(true)
    setFeedback({ type, text: message })
  }

  const saveMutation = useMutation({
    mutationFn: async () => api.put<SettingsResponse & { message: string; saved?: string[] }>('/admin/settings', {
      settings: buildPayload(),
    }),
    onSuccess: (res) => {
      const savedCount = res.data.saved?.length ?? 0
      applyServerResponse(
        { data: res.data.data, sms_status: res.data.sms_status },
        savedCount > 0 ? `تنظیمات ذخیره شد (${savedCount} مورد)` : 'تنظیمات ذخیره شد'
      )
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string; data?: Record<string, SettingItem[]>; sms_status?: SmsStatus } } }
      if (e.response?.data?.data) {
        applyServerResponse(
          { data: e.response.data.data, sms_status: e.response.data.sms_status! },
          e.response.data.message || 'خطا در ذخیره',
          'error'
        )
      } else {
        setFeedback({ type: 'error', text: e.response?.data?.message || 'خطا در ذخیره تنظیمات' })
      }
    },
  })

  const testSmsMutation = useMutation({
    mutationFn: async () => {
      const payload = buildPayload()
      if (!payload.sms_mode) payload.sms_mode = 'live'
      return api.post<SettingsResponse & { message: string; success: boolean }>('/admin/test-sms', {
        mobile: testMobile,
        settings: payload,
      })
    },
    onSuccess: (res) => {
      if (res.data.data) {
        applyServerResponse(
          { data: res.data.data, sms_status: res.data.sms_status },
          res.data.message || 'پیامک تست ارسال شد'
        )
      } else {
        setFeedback({ type: 'success', text: res.data.message || 'پیامک تست ارسال شد' })
      }
    },
    onError: (err: unknown) => {
      const e = err as {
        response?: {
          data?: {
            message?: string
            details?: unknown
            data?: Record<string, SettingItem[]>
            sms_status?: SmsStatus
          }
        }
      }
      if (e.response?.data?.data) {
        applyServerResponse(
          { data: e.response.data.data, sms_status: e.response.data.sms_status! },
          (e.response.data.message || 'خطا در ارسال پیامک') +
            (e.response.data.details ? ` — ${JSON.stringify(e.response.data.details)}` : ''),
          'error'
        )
      } else {
        setFeedback({
          type: 'error',
          text:
            (e.response?.data?.message || 'خطا در ارسال پیامک') +
            (e.response?.data?.details ? ` — ${JSON.stringify(e.response.data.details)}` : ''),
        })
      }
    },
  })

  const updateField = (key: string, value: string) => {
    setForm((prev) => ({ ...prev, [key]: value }))
  }

  const updateSecret = (key: string, value: string) => {
    setSecretDrafts((prev) => ({ ...prev, [key]: value }))
  }

  if (isLoading) {
    return (
      <div className="flex justify-center py-20">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  if (isError || !data) {
    return (
      <Card className="glass border-danger/30">
        <CardContent className="p-6 text-danger">خطا در بارگذاری تنظیمات. دوباره وارد شوید.</CardContent>
      </Card>
    )
  }

  const smsStatus = data.sms_status
  const groupLabels: Record<string, string> = {
    sms: 'پیامک (IPPanel)',
    payment: 'درگاه‌های پرداخت',
    general: 'تنظیمات عمومی',
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-3xl">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <Settings className="h-6 w-6 text-primary" />
          تنظیمات سیستم
        </h1>
        <p className="text-muted mt-1">مدیریت توکن‌ها و تنظیمات — بدون نیاز به تغییر کد</p>
      </div>

      {feedback && (
        <Card className={`glass ${feedback.type === 'error' ? 'border-danger/40' : 'border-primary/30'}`}>
          <CardContent className="p-3 text-sm flex items-start gap-2">
            {feedback.type === 'error' ? (
              <AlertCircle className="h-4 w-4 text-danger shrink-0 mt-0.5" />
            ) : (
              <CheckCircle2 className="h-4 w-4 text-success shrink-0 mt-0.5" />
            )}
            <span className="break-all">{feedback.text}</span>
          </CardContent>
        </Card>
      )}

      <Card className="glass">
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-base">
            <KeyRound className="h-5 w-5" />
            وضعیت پیامک
          </CardTitle>
        </CardHeader>
        <CardContent className="flex flex-wrap gap-2">
          <Badge variant={smsStatus.is_live ? 'success' : 'outline'}>
            {smsStatus.is_live ? 'ارسال واقعی' : 'فقط لاگ'}
          </Badge>
          <Badge variant={smsStatus.has_api_key ? 'success' : 'warning'}>
            {smsStatus.has_api_key ? 'کلید API ذخیره شده' : 'کلید API تنظیم نشده'}
          </Badge>
          <Badge variant={smsStatus.has_from_number ? 'success' : 'warning'}>
            {smsStatus.has_from_number ? 'شماره ارسال‌کننده OK' : 'شماره ارسال‌کننده خالی'}
          </Badge>
          <Badge variant={smsStatus.is_ready ? 'success' : 'danger'}>
            {smsStatus.is_ready ? 'آماده ارسال' : 'ناقص'}
          </Badge>
        </CardContent>
      </Card>

      {Object.entries(data.data).map(([group, items]) => (
        <Card key={group} className="glass">
          <CardHeader>
            <CardTitle>{groupLabels[group] || group}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {items.map((item) => (
              <div key={item.key}>
                <div className="flex items-center justify-between mb-1">
                  <label className="text-sm text-muted">{item.label}</label>
                  {item.is_secret && item.has_value && !secretDrafts[item.key] && (
                    <span className="text-xs text-success flex items-center gap-1">
                      <CheckCircle2 className="h-3 w-3" />
                      ذخیره شده
                    </span>
                  )}
                </div>

                {item.type === 'textarea' ? (
                  <textarea
                    className="w-full rounded-xl border border-card-border bg-white/5 p-3 text-sm"
                    value={form[item.key] ?? ''}
                    onChange={(e) => updateField(item.key, e.target.value)}
                    rows={3}
                  />
                ) : item.type === 'select' && item.key === 'sms_mode' ? (
                  <select
                    className="w-full h-11 rounded-xl border border-card-border bg-white/5 px-4"
                    value={form[item.key] ?? 'log'}
                    onChange={(e) => updateField(item.key, e.target.value)}
                  >
                    <option value="log">فقط لاگ (تست — کد 123456)</option>
                    <option value="live">ارسال واقعی پیامک</option>
                  </select>
                ) : item.type === 'select' && item.key === 'ippanel_api_mode' ? (
                  <select
                    className="w-full h-11 rounded-xl border border-card-border bg-white/5 px-4"
                    value={form[item.key] ?? 'auto'}
                    onChange={(e) => updateField(item.key, e.target.value)}
                  >
                    <option value="auto">خودکار (Edge + Legacy)</option>
                    <option value="edge">فقط Edge API</option>
                    <option value="legacy">فقط Legacy GET</option>
                  </select>
                ) : item.type === 'boolean' ? (
                  <select
                    className="w-full h-11 rounded-xl border border-card-border bg-white/5 px-4"
                    value={form[item.key] ?? '0'}
                    onChange={(e) => updateField(item.key, e.target.value)}
                  >
                    <option value="1">فعال</option>
                    <option value="0">غیرفعال</option>
                  </select>
                ) : item.is_secret ? (
                  <Input
                    type="password"
                    value={secretDrafts[item.key] ?? ''}
                    placeholder={item.has_value ? 'برای تغییر، کلید جدید وارد کنید' : 'کلید را وارد کنید'}
                    onChange={(e) => updateSecret(item.key, e.target.value)}
                    dir="ltr"
                    autoComplete="new-password"
                  />
                ) : (
                  <Input
                    type="text"
                    value={form[item.key] ?? ''}
                    onChange={(e) => updateField(item.key, e.target.value)}
                    dir={item.key.includes('mobile') || item.key.includes('number') || item.key.includes('url') ? 'ltr' : undefined}
                  />
                )}

                {item.key === 'ippanel_api_key' && (
                  <p className="text-xs text-muted mt-1">
                    کلید API را از پنل IPPanel کپی کنید. پس از ذخیره، «ذخیره شده» نمایش داده می‌شود.
                  </p>
                )}
                {item.key === 'ippanel_from_number' && (
                  <p className="text-xs text-muted mt-1">فرمت E.164 — مثال: +983000505</p>
                )}
              </div>
            ))}
          </CardContent>
        </Card>
      ))}

      <Card className="glass">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Send className="h-5 w-5" />
            تست پیامک
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <p className="text-sm text-muted">
            ابتدا تنظیمات را ذخیره کنید، سپس تست بزنید. حالت پیامک برای تست روی «ارسال واقعی» قرار می‌گیرد.
          </p>
          <div className="flex gap-3 flex-wrap items-center">
            <Input
              value={testMobile}
              onChange={(e) => setTestMobile(e.target.value)}
              dir="ltr"
              className="max-w-xs"
              placeholder="0912xxxxxxx"
            />
            <Button
              type="button"
              onClick={() => testSmsMutation.mutate()}
              disabled={testSmsMutation.isPending || saveMutation.isPending}
            >
              {testSmsMutation.isPending ? 'در حال ارسال...' : 'ارسال تست'}
            </Button>
          </div>
        </CardContent>
      </Card>

      <Button
        type="button"
        onClick={() => saveMutation.mutate()}
        disabled={saveMutation.isPending}
        className="w-full"
      >
        <Save className="h-4 w-4" />
        {saveMutation.isPending ? 'در حال ذخیره...' : 'ذخیره تنظیمات'}
      </Button>
    </div>
  )
}
