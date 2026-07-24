import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useState } from 'react'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

export function AdminSettingsPage() {
  const queryClient = useQueryClient()
  const [draft, setDraft] = useState<Record<string, string>>({})

  const { data, isLoading } = useQuery({
    queryKey: ['admin-settings'],
    queryFn: async () => {
      const res = await api.get('/admin/settings')
      return res.data as { data: Record<string, { key: string; value: string; label: string; type: string; is_secret: boolean }[]>; sms: Record<string, unknown>; cafe_bazaar: Record<string, unknown> }
    },
  })

  const saveMutation = useMutation({
    mutationFn: (settings: Record<string, string>) => api.put('/admin/settings', { settings }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-settings'] })
      setDraft({})
    },
  })

  const groups = data?.data ?? {}

  return (
    <div className="space-y-6 animate-fade-in max-w-3xl">
      <AdminPageHeader title="تنظیمات سیستم" />

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
          <CardHeader><CardTitle className="capitalize">{group}</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            {items.map((s) => (
              <div key={s.key}>
                <label className="text-sm text-muted block mb-1">{s.label}</label>
                <Input
                  type={s.is_secret ? 'password' : 'text'}
                  defaultValue={s.value}
                  placeholder={s.is_secret && s.value === '********' ? 'بدون تغییر' : ''}
                  onChange={(e) => setDraft((d) => ({ ...d, [s.key]: e.target.value }))}
                />
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
