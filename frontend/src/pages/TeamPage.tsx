import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { UserPlus, Users } from 'lucide-react'
import api from '@/lib/api'
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
  role_label: string
}

export function TeamPage() {
  const [form, setForm] = useState({
    name: '',
    mobile: '',
    email: '',
    username: '',
    password: '',
  })
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
      setForm({ name: '', mobile: '', email: '', username: '', password: '' })
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

  return (
    <div className="space-y-6 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <Users className="h-6 w-6 text-primary" />
          مدیریت تیم
        </h1>
        <p className="text-muted mt-1">اعضای دفتر — ورود با ایمیل/نام کاربری و رمز عبور</p>
      </div>

      <Card>
        <CardContent className="p-4 space-y-3">
          <p className="text-sm font-medium flex items-center gap-2">
            <UserPlus className="h-4 w-4" /> افزودن عضو جدید
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
            افزودن عضو
          </Button>
          {error && <p className="text-sm text-danger">{error}</p>}
        </CardContent>
      </Card>

      {isLoading ? (
        <div className="flex justify-center py-12">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : (
        <div className="grid gap-3">
          {members?.map((m) => (
            <Card key={m.id}>
              <CardContent className="p-4 flex items-center justify-between">
                <div>
                  <p className="font-medium">{m.name}</p>
                  <p className="text-sm text-muted" dir="ltr">
                    {[m.email, m.username && `@${m.username}`, m.mobile].filter(Boolean).join(' · ')}
                  </p>
                </div>
                <Badge variant="outline">{m.role_label}</Badge>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
