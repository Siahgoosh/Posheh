import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminListPage } from '@/components/admin/AdminListPage'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { formatPrice } from '@/lib/utils'

interface Row {
  id: number
  title?: string
  stage: string
  value?: number
  contact_name?: string
  office?: { name: string }
}

interface FollowUp {
  id: number
  title?: string
  contact_name?: string
  contact_mobile?: string
  stage: string
  follow_up_at?: string
  is_overdue: boolean
  office?: { name: string }
  assignee?: { name: string }
}

const stageLabels: Record<string, string> = {
  lead: 'سرنخ', contact: 'تماس', visit: 'بازدید', negotiation: 'مذاکره',
  closed_won: 'موفق', closed_lost: 'ناموفق',
}

export function AdminCrmPage() {
  const { data: followUps } = useQuery({
    queryKey: ['admin-crm-follow-ups'],
    queryFn: async () => (await api.get('/admin/crm/follow-ups')).data as {
      data: FollowUp[]
      meta: { overdue: number; upcoming: number }
    },
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="CRM — معاملات" description="یادآور پیگیری و لیست معاملات" />

      <div className="grid sm:grid-cols-2 gap-4">
        <Card><CardContent className="pt-6"><p className="text-sm text-muted">پیگیری عقب‌افتاده</p><p className="text-2xl font-bold text-danger">{followUps?.meta.overdue ?? 0}</p></CardContent></Card>
        <Card><CardContent className="pt-6"><p className="text-sm text-muted">پیگیری ۷ روز آینده</p><p className="text-2xl font-bold">{followUps?.meta.upcoming ?? 0}</p></CardContent></Card>
      </div>

      {(followUps?.data?.length ?? 0) > 0 && (
        <Card>
          <CardHeader><CardTitle>یادآور پیگیری</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {followUps?.data.map((deal) => (
              <div key={deal.id} className="flex justify-between text-sm border-b border-card-border pb-2">
                <div>
                  <span className="font-medium">{deal.title ?? deal.contact_name}</span>
                  <Badge variant="outline" className="mr-2">{deal.office?.name}</Badge>
                  {deal.is_overdue && <Badge variant="danger" className="mr-1">عقب‌افتاده</Badge>}
                </div>
                <span className="text-muted text-xs">{deal.follow_up_at?.slice(0, 10)}</span>
              </div>
            ))}
          </CardContent>
        </Card>
      )}

      <AdminListPage<Row>
        title="همه معاملات"
        endpoint="/admin/crm-deals"
        queryKey="admin-crm"
        renderRow={(d) => (
          <div className="flex justify-between border-b border-card-border pb-2 text-sm">
            <div>
              <span className="font-medium">{d.title ?? d.contact_name ?? '—'}</span>
              {d.office && <Badge variant="outline" className="mr-2">{d.office.name}</Badge>}
            </div>
            <div className="text-left">
              <Badge variant="outline">{stageLabels[d.stage] ?? d.stage}</Badge>
              {d.value != null && <span className="text-xs text-muted mr-2">{formatPrice(d.value)}</span>}
            </div>
          </div>
        )}
      />
    </div>
  )
}
