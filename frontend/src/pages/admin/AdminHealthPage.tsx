import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { formatNumber } from '@/lib/utils'

interface OfficeHealth {
  id: number
  name: string
  is_active: boolean
  health_score: number
  properties_count: number
  users_count: number
  plan?: string
  factors: Record<string, number>
}

function scoreColor(score: number): string {
  if (score >= 75) return 'text-success'
  if (score >= 50) return 'text-warning'
  return 'text-danger'
}

export function AdminHealthPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-health-scores'],
    queryFn: async () => (await api.get('/admin/health-scores')).data.data as OfficeHealth[],
  })

  const avg = data?.length
    ? Math.round(data.reduce((s, o) => s + o.health_score, 0) / data.length)
    : 0

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader
        title="Health Score دفاتر"
        description="امتیاز سلامت دفتر بر اساس فعال بودن، اشتراک، املاک و کاربران"
      />

      <div className="grid sm:grid-cols-3 gap-4">
        <Card><CardContent className="pt-6"><p className="text-sm text-muted">میانگین امتیاز</p><p className={`text-3xl font-bold ${scoreColor(avg)}`}>{avg}</p></CardContent></Card>
        <Card><CardContent className="pt-6"><p className="text-sm text-muted">تعداد دفاتر</p><p className="text-3xl font-bold">{formatNumber(data?.length ?? 0)}</p></CardContent></Card>
        <Card><CardContent className="pt-6"><p className="text-sm text-muted">امتیاز زیر ۵۰</p><p className="text-3xl font-bold text-danger">{data?.filter((o) => o.health_score < 50).length ?? 0}</p></CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>رتبه‌بندی دفاتر</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {isLoading && <p className="text-muted text-sm">بارگذاری…</p>}
          {data?.map((office) => (
            <div key={office.id} className="flex flex-wrap items-center justify-between gap-2 border-b border-card-border pb-3 text-sm">
              <div className="flex items-center gap-2">
                <span className={`text-xl font-bold ${scoreColor(office.health_score)}`}>{office.health_score}</span>
                <div>
                  <Link to={`/tenants/${office.id}`} className="font-medium hover:text-primary">{office.name}</Link>
                  <p className="text-xs text-muted">{office.plan ?? '—'} · {office.properties_count} ملک · {office.users_count} کاربر</p>
                </div>
              </div>
              <Badge variant={office.is_active ? 'default' : 'outline'}>{office.is_active ? 'فعال' : 'تعلیق'}</Badge>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
