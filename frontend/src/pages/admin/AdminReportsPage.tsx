import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { formatNumber, formatPrice } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { MiniBarChart } from '@/pages/admin/AdminSuperPanelPage'

export function AdminReportsPage() {
  const { data } = useQuery({
    queryKey: ['admin-marketing'],
    queryFn: async () => {
      const res = await api.get('/admin/marketing')
      return res.data.data
    },
  })

  if (!data) return <div className="p-8 text-center text-muted">بارگذاری گزارش‌ها…</div>

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="گزارش‌های تحلیلی" />

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Card className="glass"><CardContent className="pt-6"><p className="text-sm text-muted">MRR (تقریبی)</p><p className="text-2xl font-bold">{formatPrice(data.revenue?.monthly ?? 0)}</p></CardContent></Card>
        <Card className="glass"><CardContent className="pt-6"><p className="text-sm text-muted">کاربران جدید هفته</p><p className="text-2xl font-bold">{formatNumber(data.users?.new_week ?? 0)}</p></CardContent></Card>
        <Card className="glass"><CardContent className="pt-6"><p className="text-sm text-muted">دفاتر Trial</p><p className="text-2xl font-bold">{formatNumber(data.offices?.on_trial ?? 0)}</p></CardContent></Card>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader><CardTitle>بازدید سایت</CardTitle></CardHeader>
          <CardContent>
            {data.visits_chart && <MiniBarChart data={data.visits_chart} valueKey="views" label="۱۴ روز اخیر" />}
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>ثبت‌نام‌ها</CardTitle></CardHeader>
          <CardContent>
            {data.registrations_chart && <MiniBarChart data={data.registrations_chart} valueKey="count" label="۱۴ روز اخیر" />}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
