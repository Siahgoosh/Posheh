import { AdminListPage } from '@/components/admin/AdminListPage'
import { Badge } from '@/components/ui/badge'

interface Row {
  id: number
  name: string
  mobile?: string
  priority?: string
  preferred_city?: string
  office?: { name: string }
}

export function AdminCustomersPage() {
  return (
    <AdminListPage<Row>
      title="مشتریان (همه دفاتر)"
      description="لیست سراسری مشتریان CRM"
      endpoint="/admin/customers"
      queryKey="admin-customers"
      searchPlaceholder="نام یا موبایل…"
      renderRow={(c) => (
        <div className="flex flex-wrap justify-between gap-2 border-b border-card-border pb-2 text-sm">
          <div>
            <span className="font-medium">{c.name}</span>
            {c.mobile && <span className="text-muted mr-2" dir="ltr">{c.mobile}</span>}
            {c.office && <Badge variant="outline" className="mr-2">{c.office.name}</Badge>}
          </div>
          <span className="text-muted text-xs">{c.preferred_city ?? '—'} · {c.priority ?? '—'}</span>
        </div>
      )}
    />
  )
}
