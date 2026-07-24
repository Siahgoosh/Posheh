import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { CAPABILITY_COUNT } from '@/constants/adminCapabilities'

export function AdminSystemPage() {
  const queryClient = useQueryClient()
  const { data } = useQuery({
    queryKey: ['admin-maintenance'],
    queryFn: async () => (await api.get('/admin/platform/maintenance')).data.data,
  })
  const settings = useQuery({
    queryKey: ['admin-settings'],
    queryFn: async () => (await api.get('/admin/settings')).data,
  })

  const save = useMutation({
    mutationFn: (payload: Record<string, boolean | string>) => api.put('/admin/platform/maintenance', payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-maintenance'] }),
  })

  const flags = data ?? {}

  return (
    <div className="space-y-6 animate-fade-in max-w-2xl">
      <AdminPageHeader
        title="سیستم و Feature Flags"
        description={`${CAPABILITY_COUNT} قابلیت مدیریتی فعال در پنل`}
      />
      <Card>
        <CardHeader><CardTitle>حالت تعمیرات</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={!!flags.maintenance_mode} onChange={(e) => save.mutate({ maintenance_mode: e.target.checked })} />
            maintenance mode
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={flags.registration_enabled !== false} onChange={(e) => save.mutate({ registration_enabled: e.target.checked })} />
            ثبت‌نام فعال
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={flags.trial_enabled !== false} onChange={(e) => save.mutate({ trial_enabled: e.target.checked })} />
            دوره آزمایشی فعال
          </label>
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
    </div>
  )
}
