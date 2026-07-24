import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Building2, BadgeCheck, Globe, Power, CheckCircle, XCircle, ExternalLink } from 'lucide-react'
import api from '@/lib/api'
import { formatJalaliDate, formatNumber } from '@/lib/utils'
import { panelTypeLabel, subscriptionStatusLabel } from '@/constants/plans'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

interface OfficeRow {
  id: number
  name: string
  slug: string
  subdomain?: string
  city?: string
  panel_type?: string
  is_active: boolean
  plan_active?: boolean
  is_verified?: boolean
  website_status?: string
  show_on_website?: boolean
  trial_ends_at?: string
  properties_count: number
  users?: { id: number; name: string; mobile: string; role: string }[]
  subscription?: { status: string; plan?: { name: string } }
}

const websiteStatusLabel: Record<string, string> = {
  none: 'ندارد', pending: 'در انتظار تأیید', approved: 'تأیید شده',
  published: 'منتشر شده', rejected: 'رد شده',
}

export function AdminOfficesPage() {
  const queryClient = useQueryClient()
  const { data, isLoading } = useQuery({
    queryKey: ['admin-offices'],
    queryFn: async () => {
      const res = await api.get('/admin/offices')
      return { data: res.data.data as OfficeRow[], total: res.data.total as number }
    },
  })

  const planMutation = useMutation({
    mutationFn: ({ id, active }: { id: number; active: boolean }) =>
      api.put(`/admin/offices/${id}/plan-status`, { plan_active: active }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-offices'] }),
  })

  const websiteMutation = useMutation({
    mutationFn: ({ id, action }: { id: number; action: string }) =>
      api.put(`/admin/offices/${id}/website-status`, { action }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-offices'] }),
  })

  const offices = data?.data ?? []

  return (
    <div className="space-y-6 animate-fade-in max-w-5xl mx-auto">
      <AdminPageHeader title="مدیریت دفاتر" description={`${formatNumber(data?.total ?? offices.length)} دفتر`} />

      <Card>
        <CardHeader><CardTitle>لیست دفاتر</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          {isLoading ? <p className="text-sm text-muted">بارگذاری…</p> : offices.length === 0 ? (
            <p className="text-sm text-muted">دفتری یافت نشد.</p>
          ) : offices.map((office) => (
            <div key={office.id} className="rounded-xl border border-card-border p-4 space-y-3">
              <div className="flex flex-wrap items-start justify-between gap-2">
                <div className="flex flex-wrap items-center gap-2">
                  <Building2 className="h-4 w-4 text-primary" />
                  <span className="font-medium">{office.name}</span>
                  {office.is_verified && <BadgeCheck className="h-4 w-4 text-primary" />}
                  <Badge variant="outline">{panelTypeLabel(office.panel_type)}</Badge>
                  {office.plan_active === false && <Badge variant="outline" className="text-danger">پلن غیرفعال</Badge>}
                  {!office.is_active && <Badge variant="outline" className="text-danger">دفتر غیرفعال</Badge>}
                </div>
                <span className="text-xs text-muted">{office.city} · {formatNumber(office.properties_count)} ملک</span>
              </div>

              <p className="text-xs text-muted">
                اشتراک: {office.subscription?.plan?.name ?? '—'} ({subscriptionStatusLabel(office.subscription?.status) || 'بدون اشتراک'})
                {office.trial_ends_at && ` · آزمایشی تا ${formatJalaliDate(office.trial_ends_at)}`}
              </p>

              {office.subdomain && (
                <p className="text-xs flex items-center gap-1 text-primary">
                  <Globe className="h-3 w-3" />
                  {office.subdomain}.posheapp.ir — {websiteStatusLabel[office.website_status ?? 'none']}
                </p>
              )}

              <div className="flex flex-wrap gap-2">
                <Link to={`/tenants/${office.id}`}>
                  <Button size="sm" variant="outline"><ExternalLink className="h-3 w-3 ml-1" /> جزئیات</Button>
                </Link>
                <Button
                  size="sm"
                  variant={office.plan_active !== false ? 'outline' : 'default'}
                  disabled={planMutation.isPending}
                  onClick={() => planMutation.mutate({ id: office.id, active: office.plan_active === false })}
                >
                  <Power className="h-3 w-3 ml-1" />
                  {office.plan_active !== false ? 'غیرفعال کردن پلن' : 'فعال کردن پلن'}
                </Button>

                {office.website_status === 'pending' && (
                  <>
                    <Button size="sm" variant="outline" onClick={() => websiteMutation.mutate({ id: office.id, action: 'approve' })}>
                      <CheckCircle className="h-3 w-3 ml-1" /> تأیید وبسایت
                    </Button>
                    <Button size="sm" variant="outline" className="text-danger" onClick={() => websiteMutation.mutate({ id: office.id, action: 'reject' })}>
                      <XCircle className="h-3 w-3 ml-1" /> رد
                    </Button>
                  </>
                )}
                {office.website_status === 'approved' && (
                  <Button size="sm" onClick={() => websiteMutation.mutate({ id: office.id, action: 'publish' })}>
                    <Globe className="h-3 w-3 ml-1" /> انتشار {office.subdomain}.posheapp.ir
                  </Button>
                )}
                {office.website_status === 'published' && (
                  <a href={`/site/${office.subdomain}`} target="_blank" rel="noreferrer">
                    <Button size="sm" variant="outline">مشاهده وبسایت</Button>
                  </a>
                )}
              </div>

              {office.users && office.users.length > 0 && (
                <div className="flex flex-wrap gap-2">
                  {office.users.map((u) => (
                    <span key={u.id} className="text-xs bg-white/5 rounded-lg px-2 py-1">{u.name} ({u.role})</span>
                  ))}
                </div>
              )}
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
