import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

const roleLabels: Record<string, string> = {
  platform_admin: 'مدیر پلتفرم',
  platform_support: 'پشتیبانی',
  platform_finance: 'مالی',
}

export function AdminStaffPage() {
  const [form, setForm] = useState({ name: '', mobile: '', role: 'platform_support' })
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-platform-staff'],
    queryFn: async () => {
      const res = await api.get('/admin/users/platform-staff')
      return res.data.data as { id: number; name: string; mobile: string; role: string; is_active: boolean }[]
    },
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/admin/users/platform-staff', form),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-platform-staff'] })
      setForm({ name: '', mobile: '', role: 'platform_support' })
    },
  })

  return (
    <div className="space-y-6 animate-fade-in max-w-2xl">
      <AdminPageHeader title="مدیران پلتفرم" description="دسترسی چندمدیره به پنل" />

      <Card>
        <CardHeader><CardTitle>افزودن مدیر</CardTitle></CardHeader>
        <CardContent className="grid gap-2 sm:grid-cols-2">
          <Input placeholder="نام" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
          <Input placeholder="موبایل" value={form.mobile} onChange={(e) => setForm({ ...form, mobile: e.target.value })} />
          <select value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })} className="rounded-xl border border-card-border bg-background px-3 py-2 text-sm sm:col-span-2">
            {Object.entries(roleLabels).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </select>
          <Button className="sm:col-span-2" onClick={() => createMutation.mutate()} disabled={!form.name || !form.mobile}>افزودن</Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>لیست مدیران</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : data?.map((u) => (
            <div key={u.id} className="flex justify-between text-sm">
              <span>{u.name} — {u.mobile}</span>
              <Badge>{roleLabels[u.role] ?? u.role}</Badge>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
