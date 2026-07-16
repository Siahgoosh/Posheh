import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Globe, Plus, ExternalLink, CalendarClock, Phone, CheckCircle2, XCircle, FileCheck } from 'lucide-react'
import { useState } from 'react'
import api from '@/lib/api'
import { formatJalaliDate, toPersianDigits, formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { usePlanFeature } from '@/components/SubscriptionGuard'

const statusMap: Record<string, { label: string; color: string }> = {
  none: { label: 'ایجاد نشده', color: 'text-muted' },
  pending: { label: 'در انتظار تأیید مدیر کل', color: 'text-warning' },
  approved: { label: 'تأیید شده — آماده انتشار', color: 'text-primary' },
  published: { label: 'منتشر شده', color: 'text-success' },
  rejected: { label: 'رد شده', color: 'text-danger' },
}

export function OfficeWebsitePage() {
  const hasWebsite = usePlanFeature('website_listing')
  const queryClient = useQueryClient()
  const [subdomain, setSubdomain] = useState('')
  const [description, setDescription] = useState('')
  const [postTitle, setPostTitle] = useState('')
  const [postBody, setPostBody] = useState('')

  const { data: status } = useQuery({
    queryKey: ['office-website'],
    queryFn: async () => (await api.get('/office/website')).data.data as {
      subdomain?: string; website_status: string; website_description?: string
      website_published_at?: string; url?: string
    },
    enabled: hasWebsite,
  })

  const requestMutation = useMutation({
    mutationFn: () => api.post('/office/website/request', { subdomain, description }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['office-website'] }),
  })

  const postMutation = useMutation({
    mutationFn: () => api.post('/office/website/posts', { title: postTitle, body: postBody }),
    onSuccess: () => { setPostTitle(''); setPostBody('') },
  })

  const { data: visitRequests } = useQuery({
    queryKey: ['office-visit-requests'],
    queryFn: async () => (await api.get('/office/website/visit-requests')).data.data as {
      id: number; name: string; mobile: string; email?: string; property_code?: string
      preferred_date?: string; preferred_time?: string; message?: string; status: string; created_at?: string
    }[],
    enabled: hasWebsite && status?.website_status === 'published',
  })

  const { data: pendingProps } = useQuery({
    queryKey: ['office-pending-properties'],
    queryFn: async () => (await api.get('/office/website/pending-properties')).data.data as {
      id: number; code: string; type_label?: string; price?: number; city?: string; district?: string
      area?: number; creator?: { name?: string }
    }[],
    enabled: hasWebsite && status?.website_status === 'published',
  })

  const approveMutation = useMutation({
    mutationFn: ({ id, approved }: { id: number; approved: boolean }) =>
      api.post(`/properties/${id}/website-approval`, { approved }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['office-pending-properties'] }),
  })

  if (!hasWebsite) {
    return <div className="p-8 text-center text-muted">وبسایت اختصاصی در پلن حرفه‌ای فعال است.</div>
  }

  const st = statusMap[status?.website_status ?? 'none']

  return (
    <div className="space-y-6 max-w-2xl mx-auto animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2"><Globe className="h-6 w-6 text-primary" /> وبسایت اختصاصی دفتر</h1>
        <p className="text-sm text-muted mt-1">آدرس: name.posheapp.ir — پس از تأیید مدیر کل منتشر می‌شود</p>
      </div>

      <Card>
        <CardHeader><CardTitle className="text-base">وضعیت</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          <p className={`text-sm font-medium ${st.color}`}>{st.label}</p>
          {status?.subdomain && <p className="text-sm" dir="ltr">{status.subdomain}.posheapp.ir</p>}
          {status?.website_published_at && <p className="text-xs text-muted">منتشر شده: {formatJalaliDate(status.website_published_at)}</p>}
          {status?.url && status.website_status === 'published' && (
            <a href={`/site/${status.subdomain}`} target="_blank" rel="noreferrer">
              <Button variant="outline" size="sm"><ExternalLink className="h-3 w-3 ml-1" /> مشاهده</Button>
            </a>
          )}
        </CardContent>
      </Card>

      {(!status?.website_status || status.website_status === 'none' || status.website_status === 'rejected') && (
        <Card>
          <CardHeader><CardTitle className="text-base">درخواست وبسایت</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            <Input placeholder="زیردامنه (مثال: tehran-amlak)" value={subdomain} onChange={(e) => setSubdomain(e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))} dir="ltr" />
            <p className="text-xs text-muted">آدرس نهایی: {subdomain || 'name'}.posheapp.ir</p>
            <textarea className="w-full min-h-[80px] rounded-xl border border-card-border bg-background/50 p-3 text-sm" placeholder="معرفی دفتر برای وبسایت" value={description} onChange={(e) => setDescription(e.target.value)} />
            <Button onClick={() => requestMutation.mutate()} disabled={!subdomain || requestMutation.isPending}>ارسال برای تأیید</Button>
          </CardContent>
        </Card>
      )}

      {status?.website_status === 'published' && (
        <Card>
          <CardHeader><CardTitle className="text-base flex items-center gap-2"><Plus className="h-4 w-4" /> پست معرفی فایل</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            <Input placeholder="عنوان پست" value={postTitle} onChange={(e) => setPostTitle(e.target.value)} />
            <textarea className="w-full min-h-[100px] rounded-xl border border-card-border bg-background/50 p-3 text-sm" placeholder="متن معرفی ملک یا خدمات" value={postBody} onChange={(e) => setPostBody(e.target.value)} />
            <Button onClick={() => postMutation.mutate()} disabled={!postTitle}>انتشار در وبسایت</Button>
          </CardContent>
        </Card>
      )}

      {status?.website_status === 'published' && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <FileCheck className="h-4 w-4 text-primary" /> فایل‌های در انتظار تأیید انتشار
              {pendingProps?.length ? <span className="text-xs text-muted">({toPersianDigits(String(pendingProps.length))})</span> : null}
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {!pendingProps?.length ? (
              <p className="text-sm text-muted">فایلی در انتظار تأیید نیست. فایل‌هایی که مشاوران برای نمایش در وبسایت ثبت کنند اینجا نمایش داده می‌شوند.</p>
            ) : (
              pendingProps.map((p) => (
                <div key={p.id} className="rounded-xl border border-card-border p-3 flex items-center justify-between gap-3">
                  <div className="min-w-0">
                    <p className="font-medium">کد {toPersianDigits(p.code)} {p.type_label ? `· ${p.type_label}` : ''}</p>
                    <p className="text-xs text-muted truncate">
                      {[p.city, p.district].filter(Boolean).join('، ')}
                      {p.price ? ` · ${formatPrice(p.price)}` : ''}
                      {p.creator?.name ? ` · ${p.creator.name}` : ''}
                    </p>
                  </div>
                  <div className="flex gap-2 shrink-0">
                    <Button size="sm" onClick={() => approveMutation.mutate({ id: p.id, approved: true })} disabled={approveMutation.isPending}>
                      <CheckCircle2 className="h-4 w-4 ml-1" /> تأیید
                    </Button>
                    <Button size="sm" variant="outline" className="text-danger border-danger/30" onClick={() => approveMutation.mutate({ id: p.id, approved: false })} disabled={approveMutation.isPending}>
                      <XCircle className="h-4 w-4 ml-1" /> رد
                    </Button>
                  </div>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      )}

      {status?.website_status === 'published' && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <CalendarClock className="h-4 w-4 text-primary" /> درخواست‌های بازدید
              {visitRequests?.length ? <span className="text-xs text-muted">({toPersianDigits(String(visitRequests.length))})</span> : null}
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {!visitRequests?.length ? (
              <p className="text-sm text-muted">هنوز درخواست بازدیدی ثبت نشده است.</p>
            ) : (
              visitRequests.map((v) => (
                <div key={v.id} className="rounded-xl border border-card-border p-3 space-y-1">
                  <div className="flex items-center justify-between gap-2">
                    <span className="font-medium">{v.name}</span>
                    <a href={`tel:${v.mobile}`} className="text-sm text-primary flex items-center gap-1" dir="ltr">
                      <Phone className="h-3 w-3" /> {toPersianDigits(v.mobile)}
                    </a>
                  </div>
                  <div className="flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted">
                    {v.property_code && <span>ملک: کد {toPersianDigits(v.property_code)}</span>}
                    {v.preferred_date && <span>روز: {v.preferred_date}</span>}
                    {v.preferred_time && <span>ساعت: {v.preferred_time}</span>}
                    {v.created_at && <span>ثبت: {formatJalaliDate(v.created_at)}</span>}
                  </div>
                  {v.message && <p className="text-sm text-muted">{v.message}</p>}
                </div>
              ))
            )}
          </CardContent>
        </Card>
      )}
    </div>
  )
}
