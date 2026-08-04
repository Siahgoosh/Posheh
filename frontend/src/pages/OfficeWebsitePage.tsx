import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Globe, Plus, ExternalLink, CalendarClock, Phone, CheckCircle2, XCircle, FileCheck, Link2 } from 'lucide-react'
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
  const [domainQuery, setDomainQuery] = useState('')
  const [domainCheck, setDomainCheck] = useState<{ available: boolean | null; message: string; domain_name: string } | null>(null)
  const [ownDomain, setOwnDomain] = useState('')

  const { data: status } = useQuery({
    queryKey: ['office-website'],
    queryFn: async () => (await api.get('/office/website')).data.data as {
      subdomain?: string; website_status: string; website_description?: string
      website_published_at?: string; url?: string
      custom_domain?: string; custom_domain_status?: string
      dns_instructions?: { type: string; host: string; value: string; note?: string }[]
      latest_order?: { domain_name: string; status: string; price: number }
      ir_domain_price?: number
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
    enabled: hasWebsite,
  })

  const { data: pendingProps } = useQuery({
    queryKey: ['office-pending-properties'],
    queryFn: async () => (await api.get('/office/website/pending-properties')).data.data as {
      id: number; code: string; type_label?: string; price?: number; city?: string; district?: string
      area?: number; creator?: { name?: string }
    }[],
    enabled: hasWebsite,
  })

  const approveMutation = useMutation({
    mutationFn: ({ id, approved }: { id: number; approved: boolean }) =>
      api.post(`/properties/${id}/website-approval`, { approved }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['office-pending-properties'] }),
  })

  const checkDomainMutation = useMutation({
    mutationFn: async () => {
      const res = await api.post('/office/domain/check', { domain_name: domainQuery })
      return res.data.data as { available: boolean | null; message: string; domain_name: string }
    },
    onSuccess: (data) => setDomainCheck(data),
  })

  const payDomainMutation = useMutation({
    mutationFn: async () => {
      const name = domainCheck?.domain_name || domainQuery
      const res = await api.post('/office/domain/pay', { domain_name: name })
      return res.data.data as { redirect_url: string }
    },
    onSuccess: (data) => {
      if (data.redirect_url) window.location.href = data.redirect_url
    },
  })

  const connectDomainMutation = useMutation({
    mutationFn: () => api.post('/office/domain/connect', { domain_name: ownDomain }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['office-website'] })
    },
  })

  const verifyDomainMutation = useMutation({
    mutationFn: () => api.post('/office/domain/verify'),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['office-website'] }),
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

      <Card>
        <CardHeader>
          <CardTitle className="text-base flex items-center gap-2">
            <Link2 className="h-4 w-4 text-primary" /> دامنه اختصاصی .ir
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-sm text-muted">
            دامنه .ir را جستجو کنید (بررسی از whois.nic.ir). پس از پرداخت {status?.ir_domain_price ? formatPrice(status.ir_domain_price) : '۱۱۰٬۰۰۰ تومان'}، دامنه توسط پوشه خریداری و به وبسایت متصل می‌شود.
          </p>

          {status?.custom_domain && (
            <div className="rounded-xl bg-muted/10 p-3 text-sm">
              <p dir="ltr" className="font-medium">{status.custom_domain}</p>
              <p className="text-muted text-xs mt-1">وضعیت: {status.custom_domain_status}</p>
            </div>
          )}

          {status?.latest_order && (
            <p className="text-xs text-muted">
              آخرین سفارش: {status.latest_order.domain_name} — {status.latest_order.status}
            </p>
          )}

          <div className="space-y-2">
            <p className="text-sm font-medium">جستجوی دامنه .ir</p>
            <div className="flex gap-2">
              <Input
                placeholder="myoffice یا myoffice.ir"
                value={domainQuery}
                onChange={(e) => { setDomainQuery(e.target.value.toLowerCase()); setDomainCheck(null) }}
                dir="ltr"
              />
              <Button variant="outline" onClick={() => checkDomainMutation.mutate()} disabled={!domainQuery || checkDomainMutation.isPending}>
                بررسی
              </Button>
            </div>
            {domainCheck && (
              <div className={`text-sm p-3 rounded-xl ${domainCheck.available === true ? 'bg-success/10 text-success' : domainCheck.available === false ? 'bg-danger/10 text-danger' : 'bg-warning/10 text-warning'}`}>
                <p dir="ltr" className="font-medium">{domainCheck.domain_name}</p>
                <p className="mt-1">{domainCheck.message}</p>
                <a href={`https://www.nic.ir/Whois/?domain=${domainCheck.domain_name}`} target="_blank" rel="noreferrer" className="text-xs underline mt-2 inline-block">
                  بررسی در nic.ir
                </a>
              </div>
            )}
            {domainCheck?.available !== false && (
              <Button
                className="w-full"
                onClick={() => payDomainMutation.mutate()}
                disabled={!domainCheck || payDomainMutation.isPending}
              >
                پرداخت و ثبت دامنه ({formatPrice(status?.ir_domain_price || 110000)})
              </Button>
            )}
          </div>

          <div className="space-y-2 border-t border-card-border pt-4">
            <p className="text-sm font-medium">دامنه دارید؟ اتصال مستقیم</p>
            <div className="flex gap-2">
              <Input
                placeholder="yourdomain.ir"
                value={ownDomain}
                onChange={(e) => setOwnDomain(e.target.value.toLowerCase())}
                dir="ltr"
              />
              <Button variant="outline" onClick={() => connectDomainMutation.mutate()} disabled={!ownDomain}>
                اتصال
              </Button>
            </div>
          </div>

          {status?.dns_instructions && status.dns_instructions.length > 0 && (
            <div className="rounded-xl border border-card-border p-3 text-xs space-y-2">
              <p className="font-medium">تنظیمات DNS:</p>
              {status.dns_instructions.map((d, i) => (
                <div key={i} dir="ltr" className="bg-muted/10 p-2 rounded">
                  {d.type} {d.host} → {d.value}
                  {d.note && <span className="text-muted block text-[10px]">{d.note}</span>}
                </div>
              ))}
              <Button size="sm" variant="outline" onClick={() => verifyDomainMutation.mutate()}>
                تأیید DNS
              </Button>
            </div>
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
