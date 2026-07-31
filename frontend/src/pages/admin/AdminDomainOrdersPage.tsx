import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { formatJalaliDate, formatPrice } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

const statusLabels: Record<string, string> = {
  pending: 'در انتظار',
  paid: 'پرداخت شده',
  purchasing: 'در حال خرید',
  purchased: 'خریداری شده',
  rejected: 'رد شده',
  connected: 'متصل',
}

export function AdminDomainOrdersPage() {
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-domain-orders'],
    queryFn: async () => {
      const res = await api.get('/admin/domain-orders')
      return res.data.data as {
        id: number
        domain_name: string
        status: string
        price: number
        office?: { name: string }
        requester?: { name: string; email?: string }
        created_at: string
      }[]
    },
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      api.put(`/admin/domain-orders/${id}`, { status }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-domain-orders'] }),
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="سفارش دامنه .ir" description="مدیریت خرید و اتصال دامنه اختصاصی" />

      <Card>
        <CardHeader><CardTitle>سفارش‌ها</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : data?.map((o) => (
            <div key={o.id} className="rounded-xl border border-card-border p-4 text-sm space-y-2">
              <div className="flex flex-wrap justify-between gap-2">
                <span className="font-medium" dir="ltr">{o.domain_name}</span>
                <Badge>{statusLabels[o.status] ?? o.status}</Badge>
              </div>
              <p className="text-muted">دفتر: {o.office?.name} · {formatPrice(o.price)}</p>
              <p className="text-xs text-muted">
                درخواست‌دهنده: {o.requester?.name} {o.requester?.email && `(${o.requester.email})`}
              </p>
              <p className="text-xs text-muted">{formatJalaliDate(o.created_at)}</p>
              <div className="flex flex-wrap gap-2">
                {o.status === 'pending' && (
                  <>
                    <Button size="sm" onClick={() => updateMutation.mutate({ id: o.id, status: 'purchasing' })}>شروع خرید</Button>
                    <Button size="sm" variant="outline" onClick={() => updateMutation.mutate({ id: o.id, status: 'rejected' })}>رد</Button>
                  </>
                )}
                {o.status === 'purchasing' && (
                  <Button size="sm" onClick={() => updateMutation.mutate({ id: o.id, status: 'purchased' })}>خرید انجام شد</Button>
                )}
                {o.status === 'purchased' && (
                  <Button size="sm" onClick={() => updateMutation.mutate({ id: o.id, status: 'connected' })}>دامنه متصل شد</Button>
                )}
              </div>
            </div>
          ))}
          {!data?.length && <p className="text-muted text-sm">سفارشی ثبت نشده</p>}
        </CardContent>
      </Card>
    </div>
  )
}
