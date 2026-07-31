import { Link } from 'react-router-dom'
import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Plus } from 'lucide-react'
import api from '@/lib/api'
import { formatJalaliDate, formatNumber } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

interface UserRow {
  id: number
  name: string
  mobile: string
  email?: string
  username?: string
  role: string
  role_label?: string
  is_active: boolean
  last_login_at?: string
  office?: { id: number; name: string }
}

const roleLabels: Record<string, string> = {
  super_admin: 'مدیر سیستم',
  platform_admin: 'مدیر پلتفرم',
  platform_support: 'پشتیبانی',
  platform_finance: 'مالی',
  office_manager: 'مدیر دفتر',
  consultant: 'مشاور',
}

export function AdminUsersPage() {
  const [q, setQ] = useState('')
  const [role, setRole] = useState('')
  const [showCreate, setShowCreate] = useState(false)
  const [createForm, setCreateForm] = useState({
    name: '',
    email: '',
    username: '',
    mobile: '',
    password: '',
    office_id: '',
    role: 'consultant',
  })
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-users', q, role],
    queryFn: async () => {
      const res = await api.get('/admin/users', { params: { q: q || undefined, role: role || undefined } })
      return { users: res.data.data as UserRow[], total: res.data.total as number }
    },
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/admin/users', {
      ...createForm,
      office_id: parseInt(createForm.office_id, 10),
    }),
    onSuccess: () => {
      setShowCreate(false)
      setCreateForm({ name: '', email: '', username: '', mobile: '', password: '', office_id: '', role: 'consultant' })
      queryClient.invalidateQueries({ queryKey: ['admin-users'] })
    },
  })

  const toggleMutation = useMutation({
    mutationFn: ({ id, is_active }: { id: number; is_active: boolean }) =>
      api.put(`/admin/users/${id}`, { is_active }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-users'] }),
  })

  const logoutAllMutation = useMutation({
    mutationFn: (id: number) => api.post(`/admin/users/${id}/logout-all`),
    onSuccess: () => alert('خروج از همه دستگاه‌ها انجام شد'),
  })

  const impersonateMutation = useMutation({
    mutationFn: async (id: number) => {
      const res = await api.post(`/admin/impersonate/${id}`)
      return res.data
    },
    onSuccess: (data) => {
      if (data.token) {
        window.open(`https://posheapp.ir/dashboard?token=${data.token}`, '_blank')
      }
    },
  })

  const users = data?.users ?? []

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <AdminPageHeader title="مدیریت کاربران" description={`${formatNumber(data?.total ?? users.length)} کاربر`} />
        <Button onClick={() => setShowCreate(!showCreate)}>
          <Plus className="h-4 w-4" /> افزودن کاربر
        </Button>
      </div>

      {showCreate && (
        <Card>
          <CardHeader><CardTitle>ایجاد کاربر جدید</CardTitle></CardHeader>
          <CardContent className="grid sm:grid-cols-2 gap-3">
            <Input placeholder="نام" value={createForm.name} onChange={(e) => setCreateForm({ ...createForm, name: e.target.value })} />
            <Input placeholder="ایمیل" value={createForm.email} onChange={(e) => setCreateForm({ ...createForm, email: e.target.value })} dir="ltr" />
            <Input placeholder="نام کاربری" value={createForm.username} onChange={(e) => setCreateForm({ ...createForm, username: e.target.value })} dir="ltr" />
            <Input placeholder="موبایل" value={createForm.mobile} onChange={(e) => setCreateForm({ ...createForm, mobile: e.target.value })} dir="ltr" />
            <Input placeholder="رمز عبور" type="password" value={createForm.password} onChange={(e) => setCreateForm({ ...createForm, password: e.target.value })} />
            <Input placeholder="شناسه دفتر" value={createForm.office_id} onChange={(e) => setCreateForm({ ...createForm, office_id: e.target.value })} dir="ltr" />
            <select value={createForm.role} onChange={(e) => setCreateForm({ ...createForm, role: e.target.value })} className="rounded-xl border border-card-border bg-background px-3 py-2 text-sm">
              <option value="consultant">مشاور</option>
              <option value="office_manager">مدیر دفتر</option>
            </select>
            <Button onClick={() => createMutation.mutate()} disabled={createMutation.isPending}>ایجاد</Button>
          </CardContent>
        </Card>
      )}

      <div className="flex flex-wrap gap-2">
        <Input placeholder="جستجو نام، ایمیل، موبایل…" value={q} onChange={(e) => setQ(e.target.value)} className="max-w-xs" />
        <select
          value={role}
          onChange={(e) => setRole(e.target.value)}
          className="rounded-xl border border-card-border bg-background px-3 py-2 text-sm"
        >
          <option value="">همه نقش‌ها</option>
          {Object.entries(roleLabels).map(([k, v]) => (
            <option key={k} value={k}>{v}</option>
          ))}
        </select>
      </div>

      <Card>
        <CardHeader><CardTitle>لیست کاربران</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : users.map((u) => (
            <div key={u.id} className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-card-border p-3 text-sm">
              <div>
                <Link to={`/users/${u.id}`} className="font-medium text-primary hover:underline">{u.name}</Link>
                <p className="text-muted" dir="ltr">
                  {[u.email, u.username && `@${u.username}`, u.mobile].filter(Boolean).join(' · ')}
                </p>
                <p className="text-muted text-xs">{roleLabels[u.role] ?? u.role}</p>
                {u.office && <p className="text-xs text-muted">دفتر: {u.office.name}</p>}
                {u.last_login_at && <p className="text-xs text-muted">آخرین ورود: {formatJalaliDate(u.last_login_at)}</p>}
              </div>
              <div className="flex flex-wrap gap-2 items-center">
                <Badge variant={u.is_active ? 'success' : 'outline'}>{u.is_active ? 'فعال' : 'غیرفعال'}</Badge>
                <Button size="sm" variant="outline" onClick={() => toggleMutation.mutate({ id: u.id, is_active: !u.is_active })}>
                  {u.is_active ? 'غیرفعال' : 'فعال'}
                </Button>
                <Button size="sm" variant="outline" onClick={() => logoutAllMutation.mutate(u.id)}>خروج همه</Button>
                {u.role !== 'super_admin' && !u.role.startsWith('platform_') && (
                  <Button size="sm" onClick={() => impersonateMutation.mutate(u.id)}>ورود به پنل</Button>
                )}
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
