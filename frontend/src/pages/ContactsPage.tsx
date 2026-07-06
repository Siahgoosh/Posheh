import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Contact, Plus } from 'lucide-react'
import { Link } from 'react-router-dom'
import { useState } from 'react'
import api from '@/lib/api'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

const typeLabels: Record<string, string> = {
  buyer: 'خریدار',
  seller: 'فروشنده',
  lead: 'سرنخ',
  owner: 'مالک',
}

const statusLabels: Record<string, string> = {
  new: 'جدید',
  contacted: 'تماس گرفته',
  qualified: 'واجد شرایط',
  closed: 'بسته',
  lost: 'از دست رفته',
}

const sourceOptions = [
  { value: 'website', label: 'وب‌سایت' },
  { value: 'referral', label: 'معرفی' },
  { value: 'walk_in', label: 'مراجعه حضوری' },
  { value: 'divar', label: 'دیوار' },
  { value: 'social', label: 'شبکه اجتماعی' },
  { value: 'other', label: 'سایر' },
]

export function ContactsPage() {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({
    name: '', mobile: '', email: '', type: 'lead', source: '', budget_max: '', notes: '',
  })
  const [showForm, setShowForm] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['contacts'],
    queryFn: async () => (await api.get('/contacts')).data,
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/contacts', {
      ...form,
      budget_max: form.budget_max ? parseInt(form.budget_max) : null,
    }),
    onSuccess: () => {
      setForm({ name: '', mobile: '', email: '', type: 'lead', source: '', budget_max: '', notes: '' })
      setShowForm(false)
      queryClient.invalidateQueries({ queryKey: ['contacts'] })
    },
  })

  const selectCls = 'flex h-11 w-full rounded-xl border border-card-border bg-white/5 px-4 text-sm'

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <Contact className="h-6 w-6 text-primary" />
            مخاطبین (CRM)
          </h1>
          <p className="text-muted mt-1">مدیریت خریداران، فروشندگان و سرنخ‌ها</p>
        </div>
        <div className="flex gap-2">
          <Link to="/crm"><Button variant="secondary">قیف فروش</Button></Link>
          <Button onClick={() => setShowForm(!showForm)}>
            <Plus className="h-4 w-4" />
            مخاطب جدید
          </Button>
        </div>
      </div>

      {showForm && (
        <Card className="glass max-w-xl">
          <CardContent className="p-4 grid sm:grid-cols-2 gap-3">
            <Input placeholder="نام *" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
            <Input placeholder="موبایل" value={form.mobile} onChange={(e) => setForm({ ...form, mobile: e.target.value })} dir="ltr" />
            <Input placeholder="ایمیل" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} dir="ltr" />
            <select className={selectCls} value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}>
              {Object.entries(typeLabels).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
            </select>
            <select className={selectCls} value={form.source} onChange={(e) => setForm({ ...form, source: e.target.value })}>
              <option value="">منبع</option>
              {sourceOptions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>
            <Input placeholder="بودجه حداکثر (تومان)" dir="ltr" value={form.budget_max}
              onChange={(e) => setForm({ ...form, budget_max: e.target.value })} />
            <Input placeholder="یادداشت" className="sm:col-span-2" value={form.notes}
              onChange={(e) => setForm({ ...form, notes: e.target.value })} />
            <Button className="sm:col-span-2" onClick={() => createMutation.mutate()}
              disabled={!form.name || createMutation.isPending}>
              ذخیره مخاطب
            </Button>
          </CardContent>
        </Card>
      )}

      {isLoading ? (
        <div className="flex justify-center py-16"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
      ) : (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
          {data?.data?.map((c: { id: number; name: string; mobile?: string; type: string; status: string; budget_max?: number }) => (
            <Link key={c.id} to={`/contacts/${c.id}`}>
            <Card className="glass-hover">
              <CardContent className="p-4">
                <p className="font-semibold">{c.name}</p>
                <p className="text-sm text-muted dir-ltr text-right">{c.mobile || '—'}</p>
                <div className="flex gap-2 mt-2 flex-wrap">
                  <Badge variant="outline">{typeLabels[c.type] || c.type}</Badge>
                  <Badge variant="default">{statusLabels[c.status] || c.status}</Badge>
                  {c.budget_max ? <Badge variant="success">تا {c.budget_max.toLocaleString('fa-IR')}</Badge> : null}
                </div>
              </CardContent>
            </Card>
            </Link>
          ))}
          {!data?.data?.length && <p className="text-muted col-span-full text-center py-8">مخاطبی ثبت نشده</p>}
        </div>
      )}
    </div>
  )
}
