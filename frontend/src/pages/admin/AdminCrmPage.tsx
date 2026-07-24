import { AdminListPage } from '@/components/admin/AdminListPage'
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

const stageLabels: Record<string, string> = {
  lead: 'سرنخ', contact: 'تماس', visit: 'بازدید', negotiation: 'مذاکره',
  closed_won: 'موفق', closed_lost: 'ناموفق',
}

export function AdminCrmPage() {
  return (
    <AdminListPage<Row>
      title="CRM — معاملات"
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
  )
}
