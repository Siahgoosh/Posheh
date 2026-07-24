import { AdminListPage } from '@/components/admin/AdminListPage'
import { formatPrice } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'

interface Row {
  id: number
  title?: string
  commission_amount?: number
  status?: string
  office?: { name: string }
  user?: { name: string }
}

export function AdminCommissionsPage() {
  return (
    <AdminListPage<Row>
      title="کمیسیون‌ها"
      endpoint="/admin/commissions"
      queryKey="admin-commissions"
      renderRow={(c) => (
        <div className="flex justify-between border-b border-card-border pb-2 text-sm">
          <div>
            <span className="font-medium">{c.title ?? `کمیسیون #${c.id}`}</span>
            <Badge variant="outline" className="mr-2">{c.office?.name}</Badge>
          </div>
          <span>{formatPrice(c.commission_amount ?? 0)} · {c.status}</span>
        </div>
      )}
    />
  )
}
