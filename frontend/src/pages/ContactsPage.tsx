import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Contact, Plus } from 'lucide-react'
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

export function ContactsPage() {
  const queryClient = useQueryClient()
  const [name, setName] = useState('')
  const [mobile, setMobile] = useState('')
  const [showForm, setShowForm] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['contacts'],
    queryFn: async () => (await api.get('/contacts')).data,
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/contacts', { name, mobile, type: 'lead' }),
    onSuccess: () => {
      setName('')
      setMobile('')
      setShowForm(false)
      queryClient.invalidateQueries({ queryKey: ['contacts'] })
    },
  })

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
        <Button onClick={() => setShowForm(!showForm)}>
          <Plus className="h-4 w-4" />
          مخاطب جدید
        </Button>
      </div>

      {showForm && (
        <Card className="glass max-w-md">
          <CardContent className="p-4 space-y-3">
            <Input placeholder="نام" value={name} onChange={(e) => setName(e.target.value)} />
            <Input placeholder="موبایل" value={mobile} onChange={(e) => setMobile(e.target.value)} dir="ltr" />
            <Button onClick={() => createMutation.mutate()} disabled={!name || createMutation.isPending}>
              ذخیره
            </Button>
          </CardContent>
        </Card>
      )}

      {isLoading ? (
        <div className="flex justify-center py-16"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
      ) : (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
          {data?.data?.map((c: { id: number; name: string; mobile?: string; type: string; status: string }) => (
            <Card key={c.id} className="glass-hover">
              <CardContent className="p-4">
                <p className="font-semibold">{c.name}</p>
                <p className="text-sm text-muted dir-ltr text-right">{c.mobile || '—'}</p>
                <div className="flex gap-2 mt-2">
                  <Badge variant="outline">{typeLabels[c.type] || c.type}</Badge>
                  <Badge variant="default">{statusLabels[c.status] || c.status}</Badge>
                </div>
              </CardContent>
            </Card>
          ))}
          {!data?.data?.length && <p className="text-muted col-span-full text-center py-8">مخاطبی ثبت نشده</p>}
        </div>
      )}
    </div>
  )
}
