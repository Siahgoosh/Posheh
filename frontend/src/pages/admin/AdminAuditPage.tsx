import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { formatJalaliDate } from '@/lib/utils'
import { Card, CardContent } from '@/components/ui/card'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

export function AdminAuditPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-audit'],
    queryFn: async () => {
      const res = await api.get('/admin/audit-logs')
      return res.data.data as { id: number; action: string; description?: string; created_at: string; actor?: { name: string }; ip_address?: string }[]
    },
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="لاگ ممیزی" description="تمام عملیات حساس مدیران" />

      <Card>
        <CardContent className="pt-6 space-y-2">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : data?.map((log) => (
            <div key={log.id} className="text-sm border-b border-card-border pb-2">
              <p className="font-medium">{log.action}</p>
              <p className="text-muted">{log.description} — {log.actor?.name} · {formatJalaliDate(log.created_at)} · {log.ip_address}</p>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
