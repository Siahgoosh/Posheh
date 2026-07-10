import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowRight, Building2, BadgeCheck } from 'lucide-react'
import api from '@/lib/api'
import { formatNumber } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface OfficeRow {
  id: number
  name: string
  slug: string
  city?: string
  panel_type?: string
  is_active: boolean
  is_verified?: boolean
  show_on_website?: boolean
  trial_ends_at?: string
  properties_count: number
  users?: { id: number; name: string; mobile: string; role: string }[]
  subscription?: { status: string; plan?: { name: string } }
}

export function AdminOfficesPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-offices'],
    queryFn: async () => {
      const res = await api.get('/admin/offices')
      return { data: res.data.data as OfficeRow[], total: res.data.total as number }
    },
  })

  const offices = data?.data ?? []

  return (
    <div className="space-y-6 animate-fade-in max-w-5xl mx-auto">
      <div className="flex items-center gap-3">
        <Link to="/admin"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
        <div>
          <h1 className="text-2xl font-bold">مدیریت دفاتر و کاربران</h1>
          <p className="text-sm text-muted">{formatNumber(data?.total ?? offices.length)} دفتر ثبت‌شده</p>
        </div>
      </div>

      <Card>
        <CardHeader><CardTitle>لیست دفاتر</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {isLoading ? (
            <p className="text-sm text-muted">بارگذاری…</p>
          ) : offices.length === 0 ? (
            <p className="text-sm text-muted">دفتری یافت نشد.</p>
          ) : (
            offices.map((office) => (
              <div key={office.id} className="rounded-xl border border-card-border p-4 space-y-2">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <div className="flex items-center gap-2">
                    <Building2 className="h-4 w-4 text-primary" />
                    <span className="font-medium">{office.name}</span>
                    {office.is_verified && <BadgeCheck className="h-4 w-4 text-primary" />}
                    <Badge variant="outline">{office.panel_type || '—'}</Badge>
                    {!office.is_active && <Badge variant="outline" className="text-danger">غیرفعال</Badge>}
                    {office.show_on_website && <Badge>در وبسایت</Badge>}
                  </div>
                  <span className="text-xs text-muted">{office.city} · {formatNumber(office.properties_count)} ملک</span>
                </div>
                <p className="text-xs text-muted">
                  اشتراک: {office.subscription?.plan?.name ?? '—'} ({office.subscription?.status ?? 'بدون اشتراک'})
                  {office.trial_ends_at && ` · آزمایشی تا ${new Date(office.trial_ends_at).toLocaleDateString('fa-IR')}`}
                </p>
                {office.users && office.users.length > 0 && (
                  <div className="flex flex-wrap gap-2">
                    {office.users.map((u) => (
                      <span key={u.id} className="text-xs bg-white/5 rounded-lg px-2 py-1">{u.name} ({u.role})</span>
                    ))}
                  </div>
                )}
              </div>
            ))
          )}
        </CardContent>
      </Card>
    </div>
  )
}
