import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { formatJalaliDate, formatPrice } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { useState } from 'react'

const statusLabels: Record<string, string> = {
  pending_payment: 'در انتظار پرداخت',
  paid: 'پرداخت شده — خرید دامنه',
  purchasing: 'در حال خرید',
  purchased: 'خریداری شده',
  dns_pending: 'تنظیم DNS',
  connected: 'متصل',
  rejected: 'رد شده',
  pending: 'قدیمی',
}

export function AdminDomainOrdersPage() {
  const queryClient = useQueryClient()
  const [notes, setNotes] = useState<Record<number, string>>({})

  const { data: dnsGuide } = useQuery({
    queryKey: ['domain-dns-guide'],
    queryFn: async () => (await api.get('/admin/domain-orders/dns-guide')).data.data as {
      records: { type: string; host: string; value: string; note?: string }[]
      note: string
    },
  })

  const { data, isLoading } = useQuery({
    queryKey: ['admin-domain-orders'],
    queryFn: async () => {
      const res = await api.get('/admin/domain-orders')
      return res.data.data as {
        id: number
        domain_name: string
        status: string
        price: number
        office?: { id: number; name: string; slug: string; subdomain?: string }
        requester?: { name: string; email?: string; mobile?: string }
        created_at: string
        admin_notes?: string
      }[]
    },
  })

  const assignMutation = useMutation({
    mutationFn: ({ id, activate }: { id: number; activate?: boolean }) =>
      api.post(`/admin/domain-orders/${id}/assign`, {
        admin_notes: notes[id],
        order_status: 'purchased',
        activate,
      }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-domain-orders'] }),
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      api.put(`/admin/domain-orders/${id}`, { status, admin_notes: notes[id] }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-domain-orders'] }),
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="سفارش دامنه .ir" description="پس از پرداخت مشتری، دامنه را در nic.ir بخرید و DNS را تنظیم کنید" />

      <Card>
        <CardHeader><CardTitle>رکوردهای DNS (برای nic.ir)</CardTitle></CardHeader>
        <CardContent className="text-sm space-y-2">
          <p className="text-muted">{dnsGuide?.note}</p>
          {dnsGuide?.records?.map((r, i) => (
            <div key={i} className="p-2 rounded bg-muted/10 font-mono text-xs" dir="ltr">
              {r.type} | {r.host} → {r.value}
              {r.note && <span className="block text-muted font-sans">{r.note}</span>}
            </div>
          ))}
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>سفارش‌ها</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : data?.map((o) => (
            <div key={o.id} className="rounded-xl border border-card-border p-4 text-sm space-y-2">
              <div className="flex flex-wrap justify-between gap-2">
                <span className="font-medium" dir="ltr">{o.domain_name}</span>
                <Badge>{statusLabels[o.status] ?? o.status}</Badge>
              </div>
              <p>دفتر: {o.office?.name} (#{o.office?.id}) — {o.office?.subdomain && `${o.office.subdomain}.posheapp.ir`}</p>
              <p className="text-muted">{formatPrice(o.price)} · {formatJalaliDate(o.created_at)}</p>
              <p className="text-xs text-muted">
                مشتری: {o.requester?.name} — {[o.requester?.email, o.requester?.mobile].filter(Boolean).join(' · ')}
              </p>
              <Input
                placeholder="یادداشت مدیر"
                defaultValue={o.admin_notes || ''}
                onChange={(e) => setNotes((n) => ({ ...n, [o.id]: e.target.value }))}
              />
              <div className="flex flex-wrap gap-2">
                {o.status === 'paid' && (
                  <>
                    <Button size="sm" onClick={() => updateMutation.mutate({ id: o.id, status: 'purchasing' })}>شروع خرید در nic.ir</Button>
                    <Button size="sm" variant="outline" onClick={() => assignMutation.mutate({ id: o.id })}>دامنه خرید شد — اتصال به دفتر</Button>
                  </>
                )}
                {o.status === 'purchasing' && (
                  <Button size="sm" onClick={() => assignMutation.mutate({ id: o.id })}>ثبت خرید و DNS</Button>
                )}
                {(o.status === 'purchased' || o.status === 'dns_pending') && (
                  <Button size="sm" onClick={() => assignMutation.mutate({ id: o.id, activate: true })}>DNS تنظیم شد — فعال‌سازی</Button>
                )}
                <Button size="sm" variant="outline" onClick={() => updateMutation.mutate({ id: o.id, status: 'rejected' })}>رد</Button>
              </div>
            </div>
          ))}
          {!data?.length && <p className="text-muted text-sm">سفارشی ثبت نشده</p>}
        </CardContent>
      </Card>
    </div>
  )
}
