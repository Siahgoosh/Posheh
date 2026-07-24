import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

export function AdminCouponsPage() {
  const [form, setForm] = useState({ code: '', type: 'percent', value: '', description: '' })
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-coupons'],
    queryFn: async () => {
      const res = await api.get('/admin/coupons')
      return res.data.data as { id: number; code: string; type: string; value: number; is_active: boolean; used_count: number }[]
    },
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/admin/coupons', {
      ...form,
      value: parseInt(form.value, 10),
      is_active: true,
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-coupons'] })
      setForm({ code: '', type: 'percent', value: '', description: '' })
    },
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="کوپن و تخفیف" />

      <Card>
        <CardHeader><CardTitle>کوپن جدید</CardTitle></CardHeader>
        <CardContent className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
          <Input placeholder="کد" value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })} />
          <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className="rounded-xl border border-card-border bg-background px-3 py-2 text-sm">
            <option value="percent">درصد</option>
            <option value="fixed">مبلغ ثابت</option>
          </select>
          <Input placeholder="مقدار" value={form.value} onChange={(e) => setForm({ ...form, value: e.target.value })} />
          <Button onClick={() => createMutation.mutate()} disabled={!form.code || !form.value}>ایجاد</Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>کوپن‌های فعال</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : data?.map((c) => (
            <div key={c.id} className="flex justify-between text-sm">
              <span className="font-mono font-medium">{c.code}</span>
              <span>{c.type === 'percent' ? `${c.value}%` : c.value} · استفاده: {c.used_count} <Badge variant="outline">{c.is_active ? 'فعال' : 'غیرفعال'}</Badge></span>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
