import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Building2 } from 'lucide-react'
import api from '@/lib/api'
import { AdminNav } from '@/components/admin/AdminNav'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

interface Office {
  id: number
  name: string
  is_active: boolean
  city?: string
  properties_count: number
  users?: { id: number; name: string }[]
  subscription?: { ends_at: string; plan?: { name: string } }
  trial_ends_at?: string
}

export function AdminOfficesPage() {
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-offices'],
    queryFn: async () => (await api.get('/admin/offices')).data,
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, ...payload }: { id: number; is_active?: boolean }) =>
      api.put(`/admin/offices/${id}`, payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-offices'] }),
  })

  const extendMutation = useMutation({
    mutationFn: ({ id, days }: { id: number; days: number }) =>
      api.post(`/admin/offices/${id}/extend-subscription`, { days }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-offices'] }),
  })

  return (
    <div className="space-y-4 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <Building2 className="h-6 w-6 text-primary" />
          مدیریت دفاتر
        </h1>
        <p className="text-muted mt-1">فعال/غیرفعال کردن و تمدید اشتراک</p>
      </div>

      <AdminNav />

      {isLoading ? (
        <div className="flex justify-center py-16"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
      ) : (
        <div className="space-y-3">
          {data?.data?.map((office: Office) => (
            <Card key={office.id} className="glass">
              <CardContent className="p-4 flex flex-wrap items-center justify-between gap-4">
                <div>
                  <div className="flex items-center gap-2">
                    <p className="font-semibold">{office.name}</p>
                    <Badge variant={office.is_active ? 'success' : 'danger'}>
                      {office.is_active ? 'فعال' : 'غیرفعال'}
                    </Badge>
                  </div>
                  <p className="text-sm text-muted mt-1">
                    {office.city || '—'} · {office.properties_count} ملک · {office.users?.length ?? 0} کاربر
                  </p>
                  <p className="text-xs text-muted mt-1">
                    {office.subscription?.plan?.name
                      ? `پلن: ${office.subscription.plan.name}`
                      : `آزمایشی تا: ${office.trial_ends_at ? new Date(office.trial_ends_at).toLocaleDateString('fa-IR') : '—'}`}
                  </p>
                </div>
                <div className="flex flex-wrap gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => updateMutation.mutate({ id: office.id, is_active: !office.is_active })}
                  >
                    {office.is_active ? 'غیرفعال' : 'فعال'}
                  </Button>
                  <Button size="sm" onClick={() => extendMutation.mutate({ id: office.id, days: 30 })}>
                    +۳۰ روز
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
