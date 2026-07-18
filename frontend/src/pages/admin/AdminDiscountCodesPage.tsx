import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Tag, ArrowRight, Plus } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

interface DiscountCode {
  id: number
  code: string
  type: 'percent' | 'fixed'
  value: number
  max_uses?: number
  used_count: number
  is_active: boolean
  description?: string
  plan?: { name: string }
}

export function AdminDiscountCodesPage() {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({
    code: '',
    type: 'percent' as 'percent' | 'fixed',
    value: 10,
    max_uses: '',
    description: '',
  })

  const { data: codes, isLoading } = useQuery({
    queryKey: ['admin-discount-codes'],
    queryFn: async () => {
      const res = await api.get('/admin/discount-codes')
      return res.data.data as DiscountCode[]
    },
  })

  const createMutation = useMutation({
    mutationFn: () =>
      api.post('/admin/discount-codes', {
        code: form.code,
        type: form.type,
        value: form.value,
        max_uses: form.max_uses ? Number(form.max_uses) : null,
        description: form.description || null,
        is_active: true,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-discount-codes'] })
      setForm({ code: '', type: 'percent', value: 10, max_uses: '', description: '' })
    },
  })

  const toggleMutation = useMutation({
    mutationFn: ({ id, is_active }: { id: number; is_active: boolean }) =>
      api.put(`/admin/discount-codes/${id}`, { is_active }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-discount-codes'] }),
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/discount-codes/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-discount-codes'] }),
  })

  return (
    <div className="space-y-6 max-w-4xl mx-auto animate-fade-in">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <Tag className="h-7 w-7 text-primary" />
            کدهای تخفیف
          </h1>
          <p className="text-sm text-muted mt-1">مدیریت کد تخفیف برای پرداخت اشتراک (وب و اندروید)</p>
        </div>
        <Link to="/admin"><Button variant="ghost" size="sm"><ArrowRight className="h-4 w-4" /> پنل مدیر</Button></Link>
      </div>

      <Card>
        <CardHeader><CardTitle className="text-base flex items-center gap-2"><Plus className="h-4 w-4" /> کد جدید</CardTitle></CardHeader>
        <CardContent className="grid sm:grid-cols-2 gap-3">
          <Input placeholder="کد (مثال: POSHEH20)" value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value })} />
          <select
            className="rounded-lg border border-card-border bg-background px-3 py-2 text-sm"
            value={form.type}
            onChange={(e) => setForm({ ...form, type: e.target.value as 'percent' | 'fixed' })}
          >
            <option value="percent">درصدی</option>
            <option value="fixed">مبلغ ثابت (تومان)</option>
          </select>
          <Input type="number" placeholder="مقدار" value={form.value} onChange={(e) => setForm({ ...form, value: Number(e.target.value) })} />
          <Input placeholder="سقف استفاده (اختیاری)" value={form.max_uses} onChange={(e) => setForm({ ...form, max_uses: e.target.value })} />
          <Input className="sm:col-span-2" placeholder="توضیح (اختیاری)" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
          <Button className="sm:col-span-2" disabled={!form.code || createMutation.isPending} onClick={() => createMutation.mutate()}>
            ایجاد کد تخفیف
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle className="text-base">کدهای فعال</CardTitle></CardHeader>
        <CardContent>
          {isLoading ? (
            <p className="text-muted text-center py-6">بارگذاری…</p>
          ) : !codes?.length ? (
            <p className="text-muted text-center py-6">هنوز کد تخفیفی تعریف نشده.</p>
          ) : (
            <div className="space-y-2">
              {codes.map((c) => (
                <div key={c.id} className="flex flex-wrap items-center justify-between gap-2 border-b border-card-border pb-3">
                  <div>
                    <p className="font-mono font-bold" dir="ltr">{c.code}</p>
                    <p className="text-sm text-muted">
                      {c.type === 'percent' ? `${c.value}٪` : `${c.value.toLocaleString('fa-IR')} تومان`}
                      {' · '}
                      {c.used_count}{c.max_uses ? `/${c.max_uses}` : ''} استفاده
                      {c.description ? ` · ${c.description}` : ''}
                    </p>
                  </div>
                  <div className="flex gap-2">
                    <Button size="sm" variant="outline" onClick={() => toggleMutation.mutate({ id: c.id, is_active: !c.is_active })}>
                      {c.is_active ? 'غیرفعال' : 'فعال'}
                    </Button>
                    <Button size="sm" variant="danger" onClick={() => deleteMutation.mutate(c.id)}>حذف</Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
