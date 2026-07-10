import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, Save, Plus } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface Plan {
  id: number
  slug: string
  panel_type: string
  name: string
  description?: string
  max_users: number
  max_properties: number
  monthly_price: number
  yearly_price?: number
  trial_days: number
  features: string[]
  is_active: boolean
  sort_order: number
}

const emptyPlan = {
  slug: '',
  panel_type: 'office',
  name: '',
  description: '',
  max_users: 3,
  max_properties: 100,
  monthly_price: 0,
  yearly_price: 0,
  trial_days: 3,
  features: [] as string[],
  is_active: true,
  sort_order: 0,
}

export function AdminPlansPage() {
  const queryClient = useQueryClient()
  const [editing, setEditing] = useState<Plan | null>(null)
  const [form, setForm] = useState(emptyPlan)
  const [featuresText, setFeaturesText] = useState('')

  const { data: plans, isLoading } = useQuery({
    queryKey: ['admin-plans'],
    queryFn: async () => {
      const res = await api.get('/admin/plans')
      return res.data.data as Plan[]
    },
  })

  const saveMutation = useMutation({
    mutationFn: () => {
      const payload = {
        ...form,
        features: featuresText.split(',').map((s) => s.trim()).filter(Boolean),
      }
      return editing
        ? api.put(`/admin/plans/${editing.id}`, payload)
        : api.post('/admin/plans', payload)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-plans'] })
      queryClient.invalidateQueries({ queryKey: ['plans'] })
      setEditing(null)
      setForm(emptyPlan)
      setFeaturesText('')
    },
  })

  const startEdit = (plan: Plan) => {
    setEditing(plan)
    setForm({
      slug: plan.slug,
      panel_type: plan.panel_type,
      name: plan.name,
      description: plan.description ?? '',
      max_users: plan.max_users,
      max_properties: plan.max_properties,
      monthly_price: plan.monthly_price,
      yearly_price: plan.yearly_price ?? 0,
      trial_days: plan.trial_days,
      features: plan.features,
      is_active: plan.is_active,
      sort_order: plan.sort_order,
    })
    setFeaturesText(plan.features.join(', '))
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-4xl mx-auto">
      <div className="flex items-center gap-3">
        <Link to="/admin"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
        <div>
          <h1 className="text-2xl font-bold">مدیریت پلن‌ها و قیمت‌ها</h1>
          <p className="text-sm text-muted">تعرفه، فیچرها و دوره آزمایشی</p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center justify-between">
            {editing ? `ویرایش: ${editing.name}` : 'پلن جدید'}
            {!editing && <Button size="sm" variant="outline" onClick={() => { setForm(emptyPlan); setFeaturesText('') }}><Plus className="h-4 w-4" /></Button>}
          </CardTitle>
        </CardHeader>
        <CardContent className="grid sm:grid-cols-2 gap-3">
          <Input placeholder="slug (solo/office/premium)" value={form.slug} onChange={(e) => setForm((f) => ({ ...f, slug: e.target.value }))} dir="ltr" />
          <select className="rounded-xl border border-card-border bg-background/50 p-2 text-sm" value={form.panel_type} onChange={(e) => setForm((f) => ({ ...f, panel_type: e.target.value }))}>
            <option value="solo">مشاور مستقل</option>
            <option value="office">دفتر</option>
            <option value="premium">حرفه‌ای</option>
          </select>
          <Input placeholder="نام پلن" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
          <Input placeholder="قیمت ماهانه (تومان)" type="number" value={form.monthly_price} onChange={(e) => setForm((f) => ({ ...f, monthly_price: Number(e.target.value) }))} dir="ltr" />
          <Input placeholder="حداکثر کاربر" type="number" value={form.max_users} onChange={(e) => setForm((f) => ({ ...f, max_users: Number(e.target.value) }))} dir="ltr" />
          <Input placeholder="حداکثر ملک" type="number" value={form.max_properties} onChange={(e) => setForm((f) => ({ ...f, max_properties: Number(e.target.value) }))} dir="ltr" />
          <Input placeholder="روز آزمایشی" type="number" value={form.trial_days} onChange={(e) => setForm((f) => ({ ...f, trial_days: Number(e.target.value) }))} dir="ltr" />
          <Input placeholder="ترتیب نمایش" type="number" value={form.sort_order} onChange={(e) => setForm((f) => ({ ...f, sort_order: Number(e.target.value) }))} dir="ltr" />
          <textarea className="sm:col-span-2 w-full min-h-[60px] rounded-xl border border-card-border bg-background/50 p-3 text-sm" placeholder="توضیحات" value={form.description} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} />
          <Input className="sm:col-span-2" placeholder="فیچرها (با ویرگول): filing, accounting, team, telegram_bot, ..." value={featuresText} onChange={(e) => setFeaturesText(e.target.value)} dir="ltr" />
          <label className="flex items-center gap-2 text-sm sm:col-span-2">
            <input type="checkbox" checked={form.is_active} onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.checked }))} />
            فعال
          </label>
          <div className="sm:col-span-2 flex gap-2">
            <Button onClick={() => saveMutation.mutate()} disabled={saveMutation.isPending}>
              <Save className="h-4 w-4" /> ذخیره
            </Button>
            {editing && <Button variant="ghost" onClick={() => { setEditing(null); setForm(emptyPlan); setFeaturesText('') }}>انصراف</Button>}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>پلن‌های فعال</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {isLoading ? <p className="text-sm text-muted">بارگذاری…</p> : plans?.map((plan) => (
            <div key={plan.id} className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-card-border px-3 py-2 text-sm">
              <div>
                <p className="font-medium">{plan.name} <Badge variant="outline" className="mr-1">{plan.slug}</Badge></p>
                <p className="text-xs text-muted">{formatPrice(plan.monthly_price)} · {plan.max_users} کاربر · {plan.trial_days} روز آزمایشی</p>
              </div>
              <Button variant="outline" size="sm" onClick={() => startEdit(plan)}>ویرایش</Button>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
