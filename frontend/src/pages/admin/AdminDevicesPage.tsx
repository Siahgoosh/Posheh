import { AdminListPage } from '@/components/admin/AdminListPage'
import { Badge } from '@/components/ui/badge'
import { formatJalaliDate } from '@/lib/utils'

interface Row {
  id: number
  device_name?: string
  platform?: string
  last_active_at?: string
  user?: { name: string; mobile: string; office?: { name: string } }
}

export function AdminDevicesPage() {
  return (
    <AdminListPage<Row>
      title="دستگاه‌ها و نشست‌ها"
      endpoint="/admin/devices"
      queryKey="admin-devices"
      renderRow={(d) => (
        <div className="flex justify-between border-b border-card-border pb-2 text-sm">
          <div>
            <span className="font-medium">{d.user?.name}</span>
            <span className="text-muted mr-2" dir="ltr">{d.user?.mobile}</span>
            {d.user?.office && <Badge variant="outline">{d.user.office.name}</Badge>}
          </div>
          <span className="text-muted text-xs">{d.platform} · {d.device_name} · {d.last_active_at ? formatJalaliDate(d.last_active_at) : '—'}</span>
        </div>
      )}
    />
  )
}
