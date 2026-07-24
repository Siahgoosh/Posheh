import { AdminListPage } from '@/components/admin/AdminListPage'
import { Badge } from '@/components/ui/badge'
import { formatNumber } from '@/lib/utils'

interface Row {
  id: number
  name: string
  mobile?: string
  properties_count?: number
  office?: { name: string }
}

export function AdminOwnersPage() {
  return (
    <AdminListPage<Row>
      title="مالکان (همه دفاتر)"
      endpoint="/admin/owners"
      queryKey="admin-owners"
      renderRow={(o) => (
        <div className="flex justify-between border-b border-card-border pb-2 text-sm">
          <div>
            <span className="font-medium">{o.name}</span>
            {o.office && <Badge variant="outline" className="mr-2">{o.office.name}</Badge>}
          </div>
          <span className="text-muted">{formatNumber(o.properties_count ?? 0)} ملک</span>
        </div>
      )}
    />
  )
}
