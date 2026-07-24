import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { formatJalaliDate } from '@/lib/utils'

export function AdminVisitsPage() {
  const visits = useQuery({
    queryKey: ['admin-property-visits'],
    queryFn: async () => (await api.get('/admin/property-visits')).data,
  })
  const requests = useQuery({
    queryKey: ['admin-visit-requests'],
    queryFn: async () => (await api.get('/admin/visit-requests')).data,
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="بازدیدها" description="بازدید ملک + درخواست وبسایت دفاتر" />
      <Card>
        <CardHeader><CardTitle>بازدیدهای برنامه‌ریزی‌شده</CardTitle></CardHeader>
        <CardContent className="space-y-2 text-sm">
          {(visits.data?.data ?? []).map((v: { id: number; visit_at?: string; property?: { title: string }; office?: { name: string } }) => (
            <div key={v.id} className="border-b border-card-border pb-2">
              {v.property?.title} — {v.office?.name} · {v.visit_at ? formatJalaliDate(v.visit_at) : '—'}
            </div>
          ))}
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>درخواست بازدید وبسایت</CardTitle></CardHeader>
        <CardContent className="space-y-2 text-sm">
          {(requests.data?.data ?? []).map((r: { id: number; name: string; mobile?: string; office?: { name: string } }) => (
            <div key={r.id} className="border-b border-card-border pb-2">
              {r.name} {r.mobile && <span dir="ltr">{r.mobile}</span>} — {r.office?.name}
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
