import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { formatJalaliDate } from '@/lib/utils'

export function AdminChurnPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-churn'],
    queryFn: async () => (await api.get('/admin/platform/churn')).data.data as {
      id: number; name: string; plan_active?: boolean; trial_ends_at?: string; subscription_status?: string
    }[],
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="ریسک ریزش" description="دفاتر با اشتراک منقضی یا trial رو به اتمام" />
      <Card>
        <CardHeader><CardTitle>دفاتر در معرض خطر</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : (data ?? []).map((o) => (
            <div key={o.id} className="flex justify-between items-center border-b border-card-border pb-2 text-sm">
              <div>
                <span className="font-medium">{o.name}</span>
                <span className="text-muted text-xs mr-2"> · {o.subscription_status ?? '—'}</span>
                {o.trial_ends_at && <span className="text-xs text-warning">trial: {formatJalaliDate(o.trial_ends_at)}</span>}
              </div>
              <Link to={`/tenants/${o.id}`}><Button size="sm" variant="outline">مدیریت</Button></Link>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
