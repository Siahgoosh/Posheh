import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { CAPABILITY_COUNT } from '@/constants/adminCapabilities'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

interface FeatureFlag {
  id: number
  key: string
  name: string
  description?: string
  is_enabled: boolean
}

export function AdminSystemPage() {
  const queryClient = useQueryClient()
  const [testMobile, setTestMobile] = useState('09170577873')
  const [testResult, setTestResult] = useState('')

  const { data: maintenance } = useQuery({
    queryKey: ['admin-maintenance'],
    queryFn: async () => (await api.get('/admin/platform/maintenance')).data.data,
  })

  const settings = useQuery({
    queryKey: ['admin-settings'],
    queryFn: async () => (await api.get('/admin/settings')).data,
  })

  const { data: flags } = useQuery({
    queryKey: ['admin-feature-flags'],
    queryFn: async () => (await api.get('/admin/feature-flags')).data.data as FeatureFlag[],
  })

  const saveMaintenance = useMutation({
    mutationFn: (payload: Record<string, boolean | string>) => api.put('/admin/platform/maintenance', payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-maintenance'] }),
  })

  const toggleFlag = useMutation({
    mutationFn: ({ key, is_enabled }: { key: string; is_enabled: boolean }) =>
      api.put(`/admin/feature-flags/${key}`, { is_enabled }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-feature-flags'] }),
  })

  const testSms = useMutation({
    mutationFn: () => api.post('/admin/system/sms-test', { mobile: testMobile }),
    onSuccess: (res) => setTestResult(res.data.message ?? 'ارسال شد'),
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } }
      setTestResult(e.response?.data?.message ?? 'خطا در ارسال')
    },
  })

  const flagsData = maintenance ?? {}

  return (
    <div className="space-y-6 animate-fade-in max-w-2xl">
      <AdminPageHeader
        title="سیستم و Feature Flags"
        description={`${CAPABILITY_COUNT} قابلیت مدیریتی · فاز ۲`}
      />

      <Card>
        <CardHeader><CardTitle>حالت تعمیرات</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={!!flagsData.maintenance_mode} onChange={(e) => saveMaintenance.mutate({ maintenance_mode: e.target.checked })} />
            maintenance mode
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={flagsData.registration_enabled !== false} onChange={(e) => saveMaintenance.mutate({ registration_enabled: e.target.checked })} />
            ثبت‌نام فعال
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={flagsData.trial_enabled !== false} onChange={(e) => saveMaintenance.mutate({ trial_enabled: e.target.checked })} />
            دوره آزمایشی فعال
          </label>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Feature Flags (دیتابیس)</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {flags?.map((flag) => (
            <label key={flag.key} className="flex items-start gap-2 text-sm border-b border-card-border pb-2">
              <input
                type="checkbox"
                checked={flag.is_enabled}
                onChange={(e) => toggleFlag.mutate({ key: flag.key, is_enabled: e.target.checked })}
              />
              <span>
                <strong>{flag.name}</strong>
                <span className="block text-xs text-muted">{flag.description ?? flag.key}</span>
              </span>
            </label>
          ))}
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>سلامت سرویس‌ها</CardTitle></CardHeader>
        <CardContent className="text-sm space-y-2">
          <p>SMS: {settings.data?.sms?.is_live ? 'فعال' : 'غیرفعال'} ({settings.data?.sms?.sms_mode})</p>
          <p>Zibal: {settings.data?.zibal?.configured ? 'پیکربندی شده' : '—'}</p>
          <p>Cafe Bazaar API: {settings.data?.cafe_bazaar?.api_configured ? 'بله' : 'خیر'}</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>تست SMS از پنل</CardTitle></CardHeader>
        <CardContent className="flex flex-wrap gap-2">
          <Input value={testMobile} onChange={(e) => setTestMobile(e.target.value)} placeholder="09xxxxxxxxx" className="max-w-xs" />
          <Button onClick={() => testSms.mutate()} disabled={testSms.isPending}>ارسال تست OTP</Button>
          {testResult && <p className="w-full text-sm text-muted mt-2">{testResult}</p>}
        </CardContent>
      </Card>
    </div>
  )
}
