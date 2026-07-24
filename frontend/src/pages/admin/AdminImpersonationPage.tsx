import { AdminListPage } from '@/components/admin/AdminListPage'
import { formatJalaliDate } from '@/lib/utils'

interface Row {
  id: number
  started_at?: string
  ended_at?: string
  admin?: { name: string; mobile: string }
  target_user?: { name: string; mobile: string }
}

export function AdminImpersonationPage() {
  return (
    <AdminListPage<Row>
      title="لاگ Impersonation"
      endpoint="/admin/impersonation-sessions"
      queryKey="admin-impersonation"
      renderRow={(s) => (
        <div className="border-b border-card-border pb-2 text-sm">
          <span className="font-medium">{s.admin?.name}</span>
          <span className="text-muted"> → </span>
          <span>{s.target_user?.name}</span>
          <span className="text-xs text-muted mr-2"> · {s.started_at ? formatJalaliDate(s.started_at) : '—'}</span>
          {!s.ended_at && <span className="text-warning text-xs">فعال</span>}
        </div>
      )}
    />
  )
}
