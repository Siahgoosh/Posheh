import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, ShieldAlert, Activity } from 'lucide-react'
import api from '@/lib/api'
import { adminPath } from '@/lib/adminPaths'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { unknownFa } from '@/lib/blogLabelsFa'

export function AdminTechnicalSeoPage() {
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-seo-technical'],
    queryFn: async () => (await api.get('/admin/seo/technical')).data.data,
  })

  const runMutation = useMutation({
    mutationFn: () => api.post('/admin/seo/technical/run', { scope: 'manual', scan_links: true }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-seo-technical'] }),
  })

  const validateMutation = useMutation({
    mutationFn: () => api.post('/admin/seo/technical/validate-sitemap'),
  })

  const latest = data?.latest_audit
  const issues = (runMutation.data?.data?.issues || latest?.issues || []) as {
    severity: string
    category: string
    message: string
    url: string
  }[]

  return (
    <div className="space-y-6 animate-fade-in max-w-6xl mx-auto">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="icon" onClick={() => window.history.back()}>
            <ArrowRight className="h-5 w-5" />
          </Button>
          <div>
            <h1 className="text-2xl font-bold flex items-center gap-2"><ShieldAlert className="h-6 w-6" /> سئوی فنی</h1>
            <p className="text-sm text-muted">خزش‌پذیری / ایندکس‌پذیری / نقشه سایت / امنیت — بدون داده جعلی</p>
          </div>
        </div>
        <div className="flex gap-2">
          <Link to={adminPath('seo-growth')}><Button variant="outline">رشد سئو</Button></Link>
          <Link to={adminPath('blog')}><Button variant="outline">مدیریت وبلاگ</Button></Link>
          <Button variant="outline" onClick={() => validateMutation.mutate()} disabled={validateMutation.isPending}>اعتبارسنجی نقشه سایت</Button>
          <Button onClick={() => runMutation.mutate()} disabled={runMutation.isPending}>
            <Activity className="h-4 w-4" /> اجرای ممیزی
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader><CardTitle className="text-base">منابع داده</CardTitle></CardHeader>
        <CardContent className="text-sm text-muted space-y-1">
          <p>داده میدانی (CrUX / کنسول جستجو): <strong>{unknownFa(data?.field_data)}</strong></p>
          <p>داده آزمایشگاهی: <strong>{unknownFa(data?.lab_data)}</strong></p>
          <p>{data?.note}</p>
        </CardContent>
      </Card>

      {data?.performance_budget && (
        <Card>
          <CardHeader><CardTitle className="text-base">بودجه عملکرد (پیکربندی)</CardTitle></CardHeader>
          <CardContent className="grid sm:grid-cols-3 gap-2 text-sm">
            {Object.entries(data.performance_budget as Record<string, string | number>).map(([k, v]) => (
              <div key={k} className="rounded-lg border border-card-border px-3 py-2">
                <p className="text-xs text-muted">{k}</p>
                <p className="font-medium">{String(v)}</p>
              </div>
            ))}
          </CardContent>
        </Card>
      )}

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        {[
          ['آخرین وضعیت', latest?.status || '—'],
          ['ریدایرکت فعال', data?.redirects_active ?? '—'],
          ['لینک شکسته باز', data?.broken_links?.length ?? '—'],
          ['HSTS', data?.security_headers?.hsts ? 'روشن' : 'خاموش'],
        ].map(([label, value]) => (
          <Card key={String(label)}>
            <CardContent className="p-4">
              <p className="text-xs text-muted">{label}</p>
              <p className="text-xl font-bold">{String(value)}</p>
            </CardContent>
          </Card>
        ))}
      </div>

      {latest?.summary && (
        <Card>
          <CardHeader><CardTitle className="text-base">خلاصه شدت</CardTitle></CardHeader>
          <CardContent className="flex flex-wrap gap-3 text-sm">
            {Object.entries(latest.summary as Record<string, number>).map(([k, v]) => (
              <span key={k} className="rounded-lg border border-card-border px-3 py-1">{k}: {v}</span>
            ))}
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader><CardTitle className="text-base">مسائل</CardTitle></CardHeader>
        <CardContent className="space-y-2 max-h-[480px] overflow-y-auto">
          {isLoading && <p className="text-sm text-muted">بارگذاری…</p>}
          {!isLoading && issues.length === 0 && <p className="text-sm text-muted">هنوز ممیزی اجرا نشده یا مسئله‌ای نیست. «اجرای ممیزی» را بزنید.</p>}
          {issues.slice(0, 100).map((issue, i) => (
            <div key={`${issue.url}-${i}`} className="rounded-lg border border-card-border px-3 py-2 text-sm">
              <p className="font-medium">[{issue.severity}] {issue.category}</p>
              <p>{issue.message}</p>
              <p className="text-xs text-muted" dir="ltr">{issue.url}</p>
            </div>
          ))}
        </CardContent>
      </Card>

      {validateMutation.data?.data && (
        <Card>
          <CardHeader><CardTitle className="text-base">اعتبارسنجی نقشه سایت</CardTitle></CardHeader>
          <CardContent className="text-sm space-y-2">
            <pre className="text-xs overflow-auto max-h-40" dir="ltr">{JSON.stringify(validateMutation.data.data.metrics, null, 2)}</pre>
            <p>{(validateMutation.data.data.issues || []).length} مورد</p>
          </CardContent>
        </Card>
      )}

      {!!data?.broken_links?.length && (
        <Card>
          <CardHeader><CardTitle className="text-base">لینک‌های شکسته</CardTitle></CardHeader>
          <CardContent className="space-y-2 text-sm">
            {data.broken_links.map((link: { id: number; url: string; status_code?: number; anchor?: string }) => (
              <div key={link.id} className="flex justify-between gap-2 border border-card-border rounded-lg px-3 py-2">
                <div>
                  <p dir="ltr" className="text-xs">{link.url}</p>
                  <p className="text-muted text-xs">{link.anchor} · {link.status_code}</p>
                </div>
                <Button size="sm" variant="outline" onClick={() => api.post(`/admin/seo/technical/broken-links/${link.id}/resolve`).then(() => queryClient.invalidateQueries({ queryKey: ['admin-seo-technical'] }))}>
                  حل‌شده
                </Button>
              </div>
            ))}
          </CardContent>
        </Card>
      )}
    </div>
  )
}
