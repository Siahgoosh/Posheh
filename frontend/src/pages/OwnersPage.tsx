import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Plus, Search, UserCircle, Phone, Building2 } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface Owner {
  id: number
  name: string
  mobile?: string
  national_id?: string
  properties_count?: number
}

export function OwnersPage() {
  const [search, setSearch] = useState('')
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ name: '', mobile: '', national_id: '', notes: '' })
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['owners', search],
    queryFn: async () => {
      const params = search ? `?q=${encodeURIComponent(search)}` : ''
      const res = await api.get(`/owners${params}`)
      return res.data
    },
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/owners', form),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['owners'] })
      setShowForm(false)
      setForm({ name: '', mobile: '', national_id: '', notes: '' })
    },
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">بانک مالکین</h1>
          <p className="text-muted text-sm mt-1">{data?.meta?.total ?? 0} مالک ثبت‌شده</p>
        </div>
        <Button onClick={() => setShowForm(!showForm)}><Plus className="h-4 w-4" />مالک جدید</Button>
      </div>

      <div className="relative">
        <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted" />
        <Input placeholder="جستجو نام، موبایل، کدملی..." value={search} onChange={(e) => setSearch(e.target.value)} className="pr-10" />
      </div>

      {showForm && (
        <Card className="p-5 space-y-4">
          <div className="grid sm:grid-cols-2 gap-3">
            <Input placeholder="نام مالک *" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
            <Input placeholder="موبایل" value={form.mobile} onChange={(e) => setForm((f) => ({ ...f, mobile: e.target.value }))} dir="ltr" />
            <Input placeholder="کد ملی" value={form.national_id} onChange={(e) => setForm((f) => ({ ...f, national_id: e.target.value }))} dir="ltr" />
          </div>
          <textarea className="w-full rounded-xl border border-card-border bg-background p-3 text-sm" placeholder="یادداشت" rows={2}
            value={form.notes} onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))} />
          <Button onClick={() => createMutation.mutate()} disabled={!form.name || createMutation.isPending}>ثبت مالک</Button>
        </Card>
      )}

      {isLoading ? (
        <div className="flex justify-center py-16"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
      ) : (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {(data?.data as Owner[] ?? []).map((owner) => (
            <Link key={owner.id} to={`/owners/${owner.id}`}>
              <Card className="p-5 glass-hover h-full">
                <div className="flex items-start gap-3">
                  <div className="h-10 w-10 rounded-full bg-primary/15 flex items-center justify-center shrink-0">
                    <UserCircle className="h-5 w-5 text-primary" />
                  </div>
                  <div className="min-w-0 flex-1">
                    <h3 className="font-semibold truncate">{owner.name}</h3>
                    {owner.mobile && <p className="text-sm text-muted flex items-center gap-1 mt-1" dir="ltr"><Phone className="h-3 w-3" />{owner.mobile}</p>}
                    <div className="flex gap-2 mt-3">
                      <Badge variant="outline" className="text-xs">
                        <Building2 className="h-3 w-3 ml-1" />{owner.properties_count ?? 0} ملک
                      </Badge>
                    </div>
                  </div>
                </div>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}
