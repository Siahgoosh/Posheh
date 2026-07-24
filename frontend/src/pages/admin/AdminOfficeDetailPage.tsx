import { useParams, Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { formatJalaliDate, formatNumber, formatPrice } from '@/lib/utils'
import { useState } from 'react'

export function AdminOfficeDetailPage() {
  const { id } = useParams<{ id: string }>()
  const queryClient = useQueryClient()
  const [planId, setPlanId] = useState('')
  const [trialDate, setTrialDate] = useState('')

  const { data: office, isLoading } = useQuery({
    queryKey: ['admin-office', id],
    queryFn: async () => (await api.get(`/admin/offices/${id}`)).data.data,
    enabled: !!id,
  })

  const statusMutation = useMutation({
    mutationFn: (payload: Record<string, unknown>) => api.put(`/admin/offices/${id}/status`, payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-office', id] }),
  })

  const assignPlan = useMutation({
    mutationFn: () => api.post(`/admin/offices/${id}/assign-plan`, { plan_id: parseInt(planId, 10) }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-office', id] }),
  })

  if (isLoading || !office) return <p className="text-muted p-6">بارگذاری…</p>

  return (
    <div className="space-y-6 animate-fade-in max-w-3xl">
      <AdminPageHeader title={office.name} description={`دفتر #${office.id} · ${office.slug}`} backTo="/tenants" />

      <div className="grid gap-4 sm:grid-cols-3">
        <Card className="glass"><CardContent className="pt-6 text-center">
          <p className="text-2xl font-bold">{formatNumber(office.properties_count ?? 0)}</p>
          <p className="text-xs text-muted">ملک</p>
        </CardContent></Card>
        <Card className="glass"><CardContent className="pt-6 text-center">
          <p className="text-2xl font-bold">{formatNumber(office.users_count ?? office.users?.length ?? 0)}</p>
          <p className="text-xs text-muted">کاربر</p>
        </CardContent></Card>
        <Card className="glass"><CardContent className="pt-6 text-center">
          <p className="text-2xl font-bold">{formatPrice(office.wallet?.balance ?? 0)}</p>
          <p className="text-xs text-muted">کیف پول</p>
        </CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>وضعیت</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <div className="flex flex-wrap gap-2">
            <Badge>{office.is_active ? 'فعال' : 'غیرفعال'}</Badge>
            <Badge variant="outline">{office.subscription?.plan?.name ?? 'بدون پلن'}</Badge>
            {office.trial_ends_at && <Badge variant="outline">trial تا {formatJalaliDate(office.trial_ends_at)}</Badge>}
          </div>
          <div className="flex flex-wrap gap-2">
            <Button size="sm" variant="outline" onClick={() => statusMutation.mutate({ is_active: !office.is_active })}>
              {office.is_active ? 'تعلیق دفتر' : 'فعال‌سازی'}
            </Button>
            <Input type="date" value={trialDate} onChange={(e) => setTrialDate(e.target.value)} className="w-40" />
            <Button size="sm" variant="outline" disabled={!trialDate} onClick={() => statusMutation.mutate({ trial_ends_at: trialDate })}>
              تنظیم trial
            </Button>
          </div>
          <div className="flex gap-2">
            <Input placeholder="شناسه پلن" value={planId} onChange={(e) => setPlanId(e.target.value)} className="w-32" />
            <Button size="sm" onClick={() => assignPlan.mutate()} disabled={!planId}>تخصیص پلن</Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>کاربران دفتر</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {(office.users ?? []).map((u: { id: number; name: string; mobile: string; role: string }) => (
            <div key={u.id} className="flex justify-between text-sm border-b border-card-border pb-2">
              <Link to={`/users/${u.id}`} className="text-primary hover:underline">{u.name}</Link>
              <span className="text-muted" dir="ltr">{u.mobile}</span>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
