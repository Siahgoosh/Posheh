import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Settings, Send, Save } from 'lucide-react'
import { useEffect, useState } from 'react'
import api from '@/lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

interface SettingItem {
  key: string
  value: string
  label: string
  type: string
  is_secret: boolean
}

export function AdminSettingsPage() {
  const queryClient = useQueryClient()
  const [testMobile, setTestMobile] = useState('09170577873')
  const [form, setForm] = useState<Record<string, string>>({})
  const [message, setMessage] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['admin-settings'],
    queryFn: async () => (await api.get('/admin/settings')).data.data as Record<string, SettingItem[]>,
  })

  useEffect(() => {
    if (!data) return
    const initial: Record<string, string> = {}
    Object.values(data).flat().forEach((item) => {
      if (item.is_secret || item.value === '********') return
      if (item.value) initial[item.key] = item.value
    })
    setForm((prev) => ({ ...initial, ...prev }))
  }, [data])

  const saveMutation = useMutation({
    mutationFn: async () => api.put('/admin/settings', { settings: form }),
    onSuccess: () => {
      setMessage('تنظیمات ذخیره شد')
      queryClient.invalidateQueries({ queryKey: ['admin-settings'] })
    },
  })

  const testSmsMutation = useMutation({
    mutationFn: async () => {
      const settingsToSend = { ...form }
      if (form.sms_mode === undefined || form.sms_mode === '') {
        settingsToSend.sms_mode = 'live'
      }
      return api.post('/admin/test-sms', {
        mobile: testMobile,
        settings: settingsToSend,
      })
    },
    onSuccess: (res) => setMessage(res.data.message || 'ارسال شد'),
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string; details?: unknown } } }
      const detail = e.response?.data?.details ? ` — ${JSON.stringify(e.response.data.details)}` : ''
      setMessage((e.response?.data?.message || 'خطا در ارسال') + detail)
    },
  })

  const updateField = (key: string, value: string) => {
    setForm((f) => ({ ...f, [key]: value }))
  }

  if (isLoading) {
    return <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
  }

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
        <p className="text-muted mt-1">مدیریت توکن‌ها و تنظیمات بدون نیاز به تغییر کد</p>
      </div>

      {message && <Card className="glass border-primary/30"><CardContent className="p-3 text-sm">{message}</CardContent></Card>}

      {data && Object.entries(data).map(([group, items]) => (
        <Card key={group} className="glass">
          <CardHeader><CardTitle>{groupLabels[group] || group}</CardTitle></CardHeader>
          <CardContent className="space-y-4">
            {items.map((item) => (
              <div key={item.key}>
                <label className="text-sm text-muted mb-1 block">{item.label}</label>
                {item.type === 'textarea' ? (
                  <textarea
                    className="w-full rounded-xl border border-card-border bg-white/5 p-3 text-sm"
                    defaultValue={item.value === '********' ? '' : item.value}
                    onChange={(e) => updateField(item.key, e.target.value)}
                    rows={3}
                  />
                ) : item.type === 'select' && item.key === 'sms_mode' ? (
                  <select
                    className="w-full h-11 rounded-xl border border-card-border bg-white/5 px-4"
                    defaultValue={item.value}
                    onChange={(e) => updateField(item.key, e.target.value)}
                  >
                    <option value="log">فقط لاگ (تست)</option>
                    <option value="live">ارسال واقعی پیامک</option>
                  </select>
                ) : item.type === 'boolean' ? (
                  <select
                    className="w-full h-11 rounded-xl border border-card-border bg-white/5 px-4"
                    defaultValue={item.value}
                    onChange={(e) => updateField(item.key, e.target.value)}
                  >
                    <option value="1">فعال</option>
                    <option value="0">غیرفعال</option>
                  </select>
                ) : (
                  <Input
                    type={item.is_secret ? 'password' : 'text'}
                    placeholder={item.is_secret && item.value === '********' ? '••••••••' : ''}
                    defaultValue={item.value === '********' ? '' : item.value}
                    onChange={(e) => updateField(item.key, e.target.value)}
                    dir={item.key.includes('mobile') || item.key.includes('number') ? 'ltr' : undefined}
                  />
                )}
              </div>
            ))}
          </CardContent>
        </Card>
      ))}

      <Card className="glass">
        <CardHeader><CardTitle className="flex items-center gap-2"><Send className="h-5 w-5" />تست پیامک</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <p className="text-sm text-muted">مقادیر فرم را پر کنید و «ارسال تست» بزنید — تنظیمات خودکار ذخیره می‌شود.</p>
          <div className="flex gap-3 flex-wrap">
            <Input value={testMobile} onChange={(e) => setTestMobile(e.target.value)} dir="ltr" className="max-w-xs" />
            <Button onClick={() => testSmsMutation.mutate()} disabled={testSmsMutation.isPending}>
              {testSmsMutation.isPending ? 'در حال ارسال...' : 'ارسال تست'}
            </Button>
          </div>
        </CardContent>
      </Card>

      <Button onClick={() => saveMutation.mutate()} disabled={saveMutation.isPending} className="w-full">
        <Save className="h-4 w-4" />
        ذخیره تنظیمات
      </Button>
    </div>
  )
}
