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
  const [editId, setEditId] = useState<number | null>(null)
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-coupons'],
    queryFn: async () => {
      const res = await api.get('/admin/coupons')
      return res.data.data as { id: number; code: string; type: string; value: number; is_active: boolean; used_count: number }[]
    },
  })

  const saveMutation = useMutation({
    mutationFn: () => {
      const payload = { ...form, value: parseInt(form.value, 10), is_active: true }
      return editId ? api.put(`/admin/coupons/${editId}`, payload) : api.post('/admin/coupons', payload)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-coupons'] })
      setForm({ code: '', type: 'percent', value: '', description: '' })
      setEditId(null)
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/coupons/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-coupons'] }),
  })

  const toggleMutation = useMutation({
    mutationFn: ({ id, is_active }: { id: number; is_active: boolean }) => api.put(`/admin/coupons/${id}`, { is_active }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-coupons'] }),
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="کوپن و تخفیف" />

      <Card>
        <CardHeader><CardTitle>{editId ? 'ویرایش کوپن' : 'کوپن جدید'}</CardTitle></CardHeader>
        <CardContent className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
          <Input placeholder="کد" value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })} />
          <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className="rounded-xl border border-card-border bg-background px-3 py-2 text-sm">
            <option value="percent">درصد</option>
            <option value="fixed">مبلغ ثابت</option>
          </select>
          <Input placeholder="مقدار" value={form.value} onChange={(e) => setForm({ ...form, value: e.target.value })} />
          <Button onClick={() => saveMutation.mutate()} disabled={!form.code || !form.value}>{editId ? 'ذخیره' : 'ایجاد'}</Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>کوپن‌ها</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : data?.map((c) => (
            <div key={c.id} className="flex flex-wrap justify-between gap-2 text-sm border-b border-card-border pb-2">
              <span className="font-mono font-medium">{c.code}</span>
              <div className="flex items-center gap-2">
                <span>{c.type === 'percent' ? `${c.value}%` : c.value} · {c.used_count} بار</span>
                <Badge variant="outline">{c.is_active ? 'فعال' : 'غیرفعال'}</Badge>
                <Button size="sm" variant="ghost" onClick={() => toggleMutation.mutate({ id: c.id, is_active: !c.is_active })}>تغییر وضعیت</Button>
                <Button size="sm" variant="ghost" onClick={() => { setEditId(c.id); setForm({ code: c.code, type: c.type, value: String(c.value), description: '' }) }}>ویرایش</Button>
                <Button size="sm" variant="ghost" className="text-danger" onClick={() => deleteMutation.mutate(c.id)}>حذف</Button>
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
