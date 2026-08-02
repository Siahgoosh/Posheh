import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Pencil, Trash2, UserPlus, Users, X } from 'lucide-react'
import api from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface TeamMember {
  id: number
  name: string
  mobile: string
  email?: string
  username?: string
  role?: string
  role_label: string
}

const emptyForm = {
  name: '',
  mobile: '',
  email: '',
  username: '',
  password: '',
  role: 'consultant',
}

export function TeamPage() {
  const { user } = useAuthStore()
  const [form, setForm] = useState(emptyForm)
  const [editId, setEditId] = useState<number | null>(null)
  const [editForm, setEditForm] = useState(emptyForm)
  const [error, setError] = useState('')
  const queryClient = useQueryClient()

  const { data: members, isLoading } = useQuery({
    queryKey: ['team'],
    queryFn: async () => {
      const res = await api.get('/office/team')
      return res.data.data as TeamMember[]
    },
  })

  const inviteMutation = useMutation({
    mutationFn: () => api.post('/office/invite', form),
    onSuccess: () => {
      setForm(emptyForm)
      setError('')
      queryClient.invalidateQueries({ queryKey: ['team'] })
    },
    onError: (err: unknown) => {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      setError(
        Object.values(axiosErr.response?.data?.errors || {}).flat()[0]
          || axiosErr.response?.data?.message
          || 'خطا در افزودن عضو'
      )
    },
  })

  const updateMutation = useMutation({
    mutationFn: () => api.put(`/office/team/${editId}`, editForm),
    onSuccess: () => {
      setEditId(null)
      setError('')
      queryClient.invalidateQueries({ queryKey: ['team'] })
    },
    onError: (err: unknown) => {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      setError(
        Object.values(axiosErr.response?.data?.errors || {}).flat()[0]
          || axiosErr.response?.data?.message
          || 'خطا در ویرایش مشاور'
      )
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/office/team/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['team'] }),
    onError: (err: unknown) => {
      const axiosErr = err as { response?: { data?: { message?: string } } }
      setError(axiosErr.response?.data?.message || 'خطا در حذف مشاور')
    },
  })

  const openEdit = (member: TeamMember) => {
    setError('')
    setEditId(member.id)
    setEditForm({
      name: member.name,
      mobile: member.mobile,
      email: member.email || '',
      username: member.username || '',
      password: '',
      role: member.role || 'consultant',
    })
  }

  const canManage = user?.role === 'office_manager' || user?.role === 'super_admin'

  return (
    <div className="space-y-6 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <Users className="h-6 w-6 text-primary" />
          مدیریت تیم
        </h1>
        <p className="text-muted mt-1">افزودن، ویرایش و حذف مشاوران دفتر</p>
      </div>

      {canManage && (
        <Card>
          <CardContent className="p-4 space-y-3">
            <p className="text-sm font-medium flex items-center gap-2">
              <UserPlus className="h-4 w-4" /> افزودن مشاور جدید
            </p>
            <Input placeholder="نام" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
            <Input placeholder="موبایل (۰۹...)" value={form.mobile} onChange={(e) => setForm({ ...form, mobile: e.target.value })} dir="ltr" />
            <Input placeholder="ایمیل" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} dir="ltr" />
            <Input placeholder="نام کاربری" value={form.username} onChange={(e) => setForm({ ...form, username: e.target.value })} dir="ltr" />
            <Input placeholder="رمز عبور (حداقل ۸ کاراکتر)" type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
            <Button
              disabled={form.mobile.length < 11 || inviteMutation.isPending}
              onClick={() => inviteMutation.mutate()}
            >
              افزودن مشاور
            </Button>
          </CardContent>
        </Card>
      )}

      {error && <p className="text-sm text-danger">{error}</p>}

      {isLoading ? (
        <div className="flex justify-center py-12">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : (
        <div className="grid gap-3">
          {members?.map((m) => (
            <Card key={m.id}>
              <CardContent className="p-4 flex items-center justify-between gap-3">
                <div className="min-w-0">
                  <p className="font-medium">{m.name}</p>
                  <p className="text-sm text-muted truncate" dir="ltr">
                    {[m.email, m.username && `@${m.username}`, m.mobile].filter(Boolean).join(' · ')}
                  </p>
                </div>
                <div className="flex items-center gap-2 shrink-0">
                  <Badge variant="outline">{m.role_label}</Badge>
                  {canManage && m.id !== user?.id && (
                    <>
                      <Button variant="outline" size="icon" onClick={() => openEdit(m)} title="ویرایش">
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="outline"
                        size="icon"
                        className="text-danger hover:text-danger"
                        title="حذف"
                        disabled={deleteMutation.isPending}
                        onClick={() => {
                          if (window.confirm(`مشاور «${m.name}» حذف شود؟`)) {
                            deleteMutation.mutate(m.id)
                          }
                        }}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </>
                  )}
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {editId !== null && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
          <Card className="w-full max-w-md">
            <CardContent className="p-4 space-y-3">
              <div className="flex items-center justify-between">
                <p className="font-medium">ویرایش مشاور</p>
                <button type="button" onClick={() => setEditId(null)} className="text-muted hover:text-foreground">
                  <X className="h-5 w-5" />
                </button>
              </div>
              <Input placeholder="نام" value={editForm.name} onChange={(e) => setEditForm({ ...editForm, name: e.target.value })} />
              <Input placeholder="موبایل" value={editForm.mobile} onChange={(e) => setEditForm({ ...editForm, mobile: e.target.value })} dir="ltr" />
              <Input placeholder="ایمیل" value={editForm.email} onChange={(e) => setEditForm({ ...editForm, email: e.target.value })} dir="ltr" />
              <Input placeholder="نام کاربری" value={editForm.username} onChange={(e) => setEditForm({ ...editForm, username: e.target.value })} dir="ltr" />
              <Input placeholder="رمز عبور جدید (اختیاری)" type="password" value={editForm.password} onChange={(e) => setEditForm({ ...editForm, password: e.target.value })} />
              <select
                className="w-full rounded-xl border border-card-border bg-background px-3 py-2 text-sm"
                value={editForm.role}
                onChange={(e) => setEditForm({ ...editForm, role: e.target.value })}
              >
                <option value="consultant">مشاور</option>
                <option value="office_manager">مدیر دفتر</option>
              </select>
              <Button className="w-full" disabled={updateMutation.isPending} onClick={() => updateMutation.mutate()}>
                ذخیره تغییرات
              </Button>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  )
}
