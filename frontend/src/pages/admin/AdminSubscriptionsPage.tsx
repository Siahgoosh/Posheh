import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { formatJalaliDate } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

export function AdminSubscriptionsPage() {
  const [extendId, setExtendId] = useState<number | null>(null)
  const [days, setDays] = useState('30')
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-subscriptions'],
    queryFn: async () => {
      const res = await api.get('/admin/subscriptions')
      return res.data.data as { id: number; status: string; starts_at: string; ends_at: string; office?: { name: string }; plan?: { name: string } }[]
    },
  })

  const extendMutation = useMutation({
    mutationFn: ({ id, days: d }: { id: number; days: number }) =>
      api.post(`/admin/subscriptions/${id}/extend`, { days: d }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-subscriptions'] })
      setExtendId(null)
    },
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="مدیریت اشتراک‌ها" />

      <Card>
        <CardHeader><CardTitle>اشتراک‌های فعال و منقضی</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : data?.map((s) => (
            <div key={s.id} className="rounded-xl border border-card-border p-3 text-sm space-y-2">
              <div className="flex flex-wrap justify-between gap-2">
                <div>
                  <p className="font-medium">{s.office?.name} — {s.plan?.name}</p>
                  <p className="text-muted">{formatJalaliDate(s.starts_at)} تا {formatJalaliDate(s.ends_at)}</p>
                </div>
                <Badge>{s.status}</Badge>
              </div>
              {extendId === s.id ? (
                <div className="flex gap-2 items-center">
                  <Input type="number" value={days} onChange={(e) => setDays(e.target.value)} className="w-24" />
                  <Button size="sm" onClick={() => extendMutation.mutate({ id: s.id, days: parseInt(days, 10) })}>تمدید</Button>
                  <Button size="sm" variant="ghost" onClick={() => setExtendId(null)}>لغو</Button>
                </div>
              ) : (
                <Button size="sm" variant="outline" onClick={() => setExtendId(s.id)}>تمدید دستی</Button>
              )}
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
