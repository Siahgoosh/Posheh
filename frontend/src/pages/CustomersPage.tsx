import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Plus, Search, Users, Target, Star } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Select, SelectOption } from '@/components/ui/select'

interface Customer {
  id: number
  name: string
  mobile?: string
  priority: string
  budget_min?: number
  budget_max?: number
  preferred_city?: string
}

export function CustomersPage() {
  const [search, setSearch] = useState('')
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({
    name: '', mobile: '', priority: 'normal', budget_min: '', budget_max: '',
    preferred_city: '', preferred_type: 'sale', notes: '',
  })
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['customers', search],
    queryFn: async () => {
      const params = search ? `?q=${encodeURIComponent(search)}` : ''
      return (await api.get(`/customers${params}`)).data
    },
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/customers', {
      ...form,
      budget_min: form.budget_min ? parseInt(form.budget_min) : null,
      budget_max: form.budget_max ? parseInt(form.budget_max) : null,
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] })
      setShowForm(false)
    },
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">مشتریان</h1>
          <p className="text-muted text-sm mt-1">پروفایل، بودجه و تطبیق هوشمند</p>
        </div>
        <Button onClick={() => setShowForm(!showForm)}><Plus className="h-4 w-4" />مشتری جدید</Button>
      </div>

      <div className="relative">
        <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted" />
        <Input placeholder="جستجو..." value={search} onChange={(e) => setSearch(e.target.value)} className="pr-10" />
      </div>

      {showForm && (
        <Card className="p-5 space-y-4">
          <div className="grid sm:grid-cols-2 gap-3">
            <Input placeholder="نام مشتری *" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
            <Input placeholder="موبایل" value={form.mobile} onChange={(e) => setForm((f) => ({ ...f, mobile: e.target.value }))} dir="ltr" />
            <Select value={form.priority} onChange={(e) => setForm((f) => ({ ...f, priority: e.target.value }))}>
              <SelectOption value="normal">عادی</SelectOption>
              <SelectOption value="vip">VIP</SelectOption>
            </Select>
            <Select value={form.preferred_type} onChange={(e) => setForm((f) => ({ ...f, preferred_type: e.target.value }))}>
              <SelectOption value="sale">خرید</SelectOption>
              <SelectOption value="rent">اجاره</SelectOption>
            </Select>
            <Input placeholder="بودجه از (تومان)" value={form.budget_min} onChange={(e) => setForm((f) => ({ ...f, budget_min: e.target.value }))} dir="ltr" />
            <Input placeholder="بودجه تا" value={form.budget_max} onChange={(e) => setForm((f) => ({ ...f, budget_max: e.target.value }))} dir="ltr" />
            <Input placeholder="شهر ترجیحی" value={form.preferred_city} onChange={(e) => setForm((f) => ({ ...f, preferred_city: e.target.value }))} />
          </div>
          <Button onClick={() => createMutation.mutate()} disabled={!form.name}>ثبت مشتری</Button>
        </Card>
      )}

      {isLoading ? (
        <div className="flex justify-center py-16"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
      ) : (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {(data?.data as Customer[] ?? []).map((c) => (
            <Link key={c.id} to={`/customers/${c.id}`}>
              <Card className="p-5 glass-hover h-full">
                <div className="flex justify-between items-start mb-2">
                  <h3 className="font-semibold flex items-center gap-2">
                    <Users className="h-4 w-4 text-primary" />{c.name}
                  </h3>
                  {c.priority === 'vip' && <Badge className="bg-accent/20 text-accent border-accent/30"><Star className="h-3 w-3" />VIP</Badge>}
                </div>
                {c.mobile && <p className="text-sm text-muted" dir="ltr">{c.mobile}</p>}
                {(c.budget_min || c.budget_max) && (
                  <p className="text-xs text-primary mt-2">
                    بودجه: {c.budget_min ? formatPrice(c.budget_min) : '—'} تا {c.budget_max ? formatPrice(c.budget_max) : '—'}
                  </p>
                )}
                {c.preferred_city && <p className="text-xs text-muted mt-1">{c.preferred_city}</p>}
                <p className="text-xs text-primary mt-3 flex items-center gap-1"><Target className="h-3 w-3" />مشاهده تطبیق هوشمند</p>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}
