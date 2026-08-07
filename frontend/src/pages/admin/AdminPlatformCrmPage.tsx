import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

interface LeadRow {
  id: string
  kind: string
  source: string
  name?: string
  mobile?: string
  email?: string
  message?: string
  stage: string
  notes?: string
  office?: { name: string }
  context?: string
  context_url?: string
  follow_up_at?: string
  created_at?: string
}

const stageLabels: Record<string, string> = {
  new: 'جدید',
  contacted: 'تماس',
  qualified: 'واجد شرایط',
  demo: 'دمو',
  won: 'موفق',
  lost: 'ناموفق',
}

const sourceLabels: Record<string, string> = {
  manual: 'دستی',
  tour: 'تور مجازی',
  visit: 'درخواست بازدید',
  registration: 'ثبت‌نام',
}

export function AdminPlatformCrmPage() {
  const [name, setName] = useState('')
  const [mobile, setMobile] = useState('')
  const [message, setMessage] = useState('')
  const [sourceFilter, setSourceFilter] = useState('all')
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-platform-leads', sourceFilter],
    queryFn: async () => (await api.get('/admin/platform-leads', {
      params: { source: sourceFilter },
    })).data as { data: LeadRow[]; meta: Record<string, number> },
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/admin/platform-leads', { name, mobile, message }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-platform-leads'] })
      setName('')
      setMobile('')
      setMessage('')
    },
  })

  const updateStageMutation = useMutation({
    mutationFn: ({ id, stage }: { id: number; stage: string }) =>
      api.put(`/admin/platform-leads/${id}`, { stage }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-platform-leads'] }),
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader
        title="CRM پلتفرم پوشه"
        description="جذب مشتری — سرنخ‌های تور، بازدید و ثبت دستی در یک اینباکس"
      />

      <div className="grid sm:grid-cols-4 gap-4">
        <Card><CardContent className="pt-6"><p className="text-sm text-muted">سرنخ دستی</p><p className="text-2xl font-bold">{data?.meta?.manual ?? 0}</p></CardContent></Card>
        <Card><CardContent className="pt-6"><p className="text-sm text-muted">تور مجازی</p><p className="text-2xl font-bold">{data?.meta?.tour_leads ?? 0}</p></CardContent></Card>
        <Card><CardContent className="pt-6"><p className="text-sm text-muted">درخواست بازدید</p><p className="text-2xl font-bold">{data?.meta?.visit_requests ?? 0}</p></CardContent></Card>
        <Card><CardContent className="pt-6"><p className="text-sm text-muted">جدید (دستی)</p><p className="text-2xl font-bold text-primary">{data?.meta?.new_manual ?? 0}</p></CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>ثبت سرنخ جدید</CardTitle></CardHeader>
        <CardContent className="grid sm:grid-cols-2 gap-3">
          <Input placeholder="نام" value={name} onChange={(e) => setName(e.target.value)} />
          <Input placeholder="موبایل" value={mobile} onChange={(e) => setMobile(e.target.value)} dir="ltr" />
          <Input placeholder="یادداشت" value={message} onChange={(e) => setMessage(e.target.value)} className="sm:col-span-2" />
          <Button
            onClick={() => createMutation.mutate()}
            disabled={!mobile.trim() || createMutation.isPending}
            className="sm:col-span-2"
          >
            ثبت سرنخ
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between gap-4">
          <CardTitle>اینباکس سرنخ‌ها</CardTitle>
          <select
            className="rounded-lg border border-card-border bg-background px-3 py-1.5 text-sm"
            value={sourceFilter}
            onChange={(e) => setSourceFilter(e.target.value)}
          >
            <option value="all">همه منابع</option>
            <option value="manual">دستی</option>
            <option value="tour">تور</option>
            <option value="visit">بازدید</option>
          </select>
        </CardHeader>
        <CardContent className="space-y-2">
          {isLoading && <p className="text-sm text-muted">بارگذاری…</p>}
          {data?.data?.map((lead) => (
            <div key={lead.id} className="flex flex-wrap justify-between gap-2 border-b border-card-border pb-2 text-sm">
              <div className="min-w-0">
                <span className="font-medium">{lead.name || lead.mobile || '—'}</span>
                <Badge variant="outline" className="mr-2">{sourceLabels[lead.source] ?? lead.source}</Badge>
                {lead.office && <Badge variant="outline" className="mr-1">{lead.office.name}</Badge>}
                {lead.context && (
                  <span className="text-xs text-muted mr-2">
                    {lead.context_url ? (
                      <a href={lead.context_url} target="_blank" rel="noreferrer" className="hover:text-primary">
                        {lead.context}
                      </a>
                    ) : lead.context}
                  </span>
                )}
                {lead.message && <p className="text-xs text-muted mt-0.5 truncate">{lead.message}</p>}
              </div>
              <div className="flex items-center gap-2 shrink-0">
                {lead.kind === 'manual' && (
                  <select
                    className="rounded border border-card-border bg-background px-2 py-1 text-xs"
                    value={lead.stage}
                    onChange={(e) => {
                      const numericId = Number(lead.id.replace('lead-', ''))
                      updateStageMutation.mutate({ id: numericId, stage: e.target.value })
                    }}
                  >
                    {Object.entries(stageLabels).map(([k, v]) => (
                      <option key={k} value={k}>{v}</option>
                    ))}
                  </select>
                )}
                {lead.kind !== 'manual' && (
                  <Badge variant="outline">{stageLabels[lead.stage] ?? lead.stage}</Badge>
                )}
                <span className="text-xs text-muted">{lead.created_at?.slice(0, 10)}</span>
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
