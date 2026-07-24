import { AdminListPage } from '@/components/admin/AdminListPage'
import { Badge } from '@/components/ui/badge'

interface Row {
  id: number
  title?: string
  status?: string
  office?: { name: string }
  property?: { title: string }
}

export function AdminContractsPage() {
  return (
    <AdminListPage<Row>
      title="قراردادها"
      endpoint="/admin/contracts"
      queryKey="admin-contracts"
      renderRow={(c) => (
        <div className="flex justify-between border-b border-card-border pb-2 text-sm">
          <span>{c.title ?? c.property?.title ?? `قرارداد #${c.id}`}</span>
          <div className="flex gap-2">
            <Badge variant="outline">{c.office?.name}</Badge>
            <Badge variant="outline">{c.status ?? '—'}</Badge>
          </div>
        </div>
      )}
    />
  )
}
