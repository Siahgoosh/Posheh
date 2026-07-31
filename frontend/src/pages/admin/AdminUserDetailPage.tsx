import { useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { formatJalaliDate } from '@/lib/utils'
import { useState } from 'react'

const roles = [
  { value: 'consultant', label: 'مشاور' },
  { value: 'office_manager', label: 'مدیر دفتر' },
  { value: 'super_admin', label: 'مدیر سیستم' },
]

export function AdminUserDetailPage() {
  const { id } = useParams<{ id: string }>()
  const queryClient = useQueryClient()
  const [form, setForm] = useState({
    name: '',
    email: '',
    username: '',
    mobile: '',
    role: '',
    password: '',
  })
  const [msg, setMsg] = useState('')

  const { data: user, isLoading } = useQuery({
    queryKey: ['admin-user', id],
    queryFn: async () => {
      const res = await api.get(`/admin/users/${id}`)
      const u = res.data.data
      setForm({
        name: u.name ?? '',
        email: u.email ?? '',
        username: u.username ?? '',
        mobile: u.mobile ?? '',
        role: u.role ?? '',
        password: '',
      })
      return u
    },
    enabled: !!id,
  })

  const updateMutation = useMutation({
    mutationFn: () => api.put(`/admin/users/${id}`, {
      name: form.name,
      email: form.email || null,
      username: form.username || null,
      mobile: form.mobile || null,
      role: form.role,
      ...(form.password ? { password: form.password } : {}),
    }),
    onSuccess: () => {
      setMsg('ذخیره شد.')
      setForm((f) => ({ ...f, password: '' }))
      queryClient.invalidateQueries({ queryKey: ['admin-user', id] })
    },
  })

  const logoutAll = useMutation({
    mutationFn: () => api.post(`/admin/users/${id}/logout-all`),
  })

  const toggleActive = useMutation({
    mutationFn: () => api.put(`/admin/users/${id}`, { is_active: !user?.is_active }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-user', id] }),
  })

  const impersonate = useMutation({
    mutationFn: async () => {
      const res = await api.post(`/admin/impersonate/${id}`)
      if (res.data.url) window.open(res.data.url, '_blank')
    },
  })

  if (isLoading || !user) return <p className="text-muted p-6">بارگذاری…</p>

  return (
    <div className="space-y-6 animate-fade-in max-w-2xl">
      <AdminPageHeader
        title={user.name}
        description={[user.email, user.username && `@${user.username}`, user.mobile].filter(Boolean).join(' · ')}
        backTo="/users"
      />

      <Card>
        <CardHeader><CardTitle>ویرایش کاربر</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <Input placeholder="نام" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
          <Input placeholder="ایمیل" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} dir="ltr" />
          <Input placeholder="نام کاربری" value={form.username} onChange={(e) => setForm({ ...form, username: e.target.value })} dir="ltr" />
          <Input placeholder="موبایل" value={form.mobile} onChange={(e) => setForm({ ...form, mobile: e.target.value })} dir="ltr" />
          <select value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })} className="w-full rounded-xl border border-card-border bg-background px-3 py-2 text-sm">
            {roles.map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
          </select>
          <Input
            type="password"
            placeholder="رمز عبور جدید (اختیاری)"
            value={form.password}
            onChange={(e) => setForm({ ...form, password: e.target.value })}
          />
          {msg && <p className="text-sm text-success">{msg}</p>}
          <Button onClick={() => updateMutation.mutate()}>ذخیره تغییرات</Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>اطلاعات</CardTitle></CardHeader>
        <CardContent className="text-sm space-y-2">
          <p>دفتر: {user.office?.name ?? '—'}</p>
          <p>آخرین ورود: {user.last_login_at ? formatJalaliDate(user.last_login_at) : '—'}</p>
          <Badge>{user.is_active ? 'فعال' : 'غیرفعال'}</Badge>
        </CardContent>
      </Card>

      <div className="flex flex-wrap gap-2">
        <Button variant="outline" onClick={() => logoutAll.mutate()}>خروج همه دستگاه‌ها</Button>
        <Button variant="outline" onClick={() => impersonate.mutate()}>ورود به پنل مشاور</Button>
        <Button variant="outline" onClick={() => toggleActive.mutate()}>
          {user.is_active ? 'غیرفعال' : 'فعال'}
        </Button>
      </div>

      {(user.devices ?? []).length > 0 && (
        <Card>
          <CardHeader><CardTitle>دستگاه‌ها</CardTitle></CardHeader>
          <CardContent className="text-sm space-y-1">
            {user.devices.map((d: { id: number; platform: string; device_name: string }) => (
              <p key={d.id}>{d.platform} — {d.device_name}</p>
            ))}
          </CardContent>
        </Card>
      )}
    </div>
  )
}
