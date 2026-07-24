import { AdminListPage } from '@/components/admin/AdminListPage'
import { Badge } from '@/components/ui/badge'

interface Row {
  id: number
  title?: string
  code?: string
  status?: string
  office?: { name: string }
}

export function AdminPropertiesPage() {
  return (
    <AdminListPage<Row>
      title="املاک (سراسری)"
      endpoint="/admin/properties"
      queryKey="admin-properties"
      searchPlaceholder="عنوان یا کد…"
      renderRow={(p) => (
        <div className="flex justify-between border-b border-card-border pb-2 text-sm">
          <div>
            <span className="font-medium">{p.title ?? p.code}</span>
            {p.office && <Badge variant="outline" className="mr-2">{p.office.name}</Badge>}
          </div>
          <Badge variant="outline">{p.status ?? '—'}</Badge>
        </div>
      )}
    />
  )
}
