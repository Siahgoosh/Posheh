import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { ArrowRight, Save, Settings } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

interface SettingItem {
  key: string
  value: string
  label: string
  type: string
  is_secret: boolean
  has_value: boolean
}

export function AdminSettingsPage() {
  const queryClient = useQueryClient()
  const [values, setValues] = useState<Record<string, string>>({})

  const { data, isLoading } = useQuery({
    queryKey: ['admin-system-settings'],
    queryFn: async () => {
      const res = await api.get('/admin/system-settings')
      const grouped = res.data.data as Record<string, SettingItem[]>
      const flat: Record<string, string> = {}
      Object.values(grouped).flat().forEach((s) => {
        flat[s.key] = s.value ?? ''
      })
      setValues(flat)
      return res.data
    },
  })

  const save = useMutation({
    mutationFn: async () => {
      await api.put('/admin/system-settings', { settings: values })
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-system-settings'] }),
  })

  const paymentKeys = ['zibal_merchant', 'zibal_sandbox', 'aqayepardakht_pin', 'aqayepardakht_sandbox']
  const generalKeys = ['trial_days_solo', 'frontend_url', 'app_public_name', 'invite_sms_template']
  const smsKeys = ['sms_mode', 'sms_provider', 'ippanel_username', 'ippanel_from_number', 'ippanel_otp_pattern_code']

  const renderField = (key: string, label?: string) => (
    <div key={key} className="space-y-1">
      <label className="text-sm text-muted">{label || key}</label>
      <Input
        value={values[key] ?? ''}
        onChange={(e) => setValues((v) => ({ ...v, [key]: e.target.value }))}
        dir={key.includes('merchant') || key.includes('pin') ? 'ltr' : 'rtl'}
        type={key.includes('password') || key.includes('secret') || key.includes('pin') || key.includes('merchant') ? 'password' : 'text'}
        placeholder={key === 'zibal_merchant' ? 'مرچنت ID زیبال' : ''}
      />
    </div>
  )

  const flatSettings = Object.values(data?.data ?? {}).flat() as SettingItem[]
  const labelFor = (key: string) => flatSettings.find((s) => s.key === key)?.label ?? key

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Link to="/admin"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
          <div>
            <h1 className="text-2xl font-bold flex items-center gap-2"><Settings className="h-6 w-6 text-primary" />تنظیمات سیستم</h1>
            <p className="text-sm text-muted">مرچنت زیبال، SMS و دوره آزمایشی — بلافاصله در وب و اپ اعمال می‌شود</p>
          </div>
        </div>
        <Button onClick={() => save.mutate()} disabled={save.isPending}>
          <Save className="h-4 w-4" />
          ذخیره
        </Button>
      </div>

      {isLoading ? (
        <p className="text-muted">در حال بارگذاری…</p>
      ) : (
        <div className="grid gap-6 lg:grid-cols-2">
          <Card className="glass">
            <CardHeader><CardTitle>درگاه پرداخت (زیبال)</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              {paymentKeys.map((k) => renderField(k, labelFor(k)))}
              <p className="text-xs text-muted">مرچنت فعلی: {data?.zibal?.merchant ? '••••••' + String(data.zibal.merchant).slice(-4) : 'تنظیم نشده'}</p>
            </CardContent>
          </Card>

          <Card className="glass">
            <CardHeader><CardTitle>عمومی و آزمایشی</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              {generalKeys.map((k) => renderField(k, labelFor(k)))}
            </CardContent>
          </Card>

          <Card className="glass lg:col-span-2">
            <CardHeader><CardTitle>پیامک (IPPanel)</CardTitle></CardHeader>
            <CardContent className="grid gap-4 md:grid-cols-2">
              {smsKeys.map((k) => renderField(k, labelFor(k)))}
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  )
}
