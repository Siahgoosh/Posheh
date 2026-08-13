import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, ImageIcon } from 'lucide-react'
import api from '@/lib/api'
import { adminPath } from '@/lib/adminPaths'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { BootstrapStatusBanner, useAdminBootstrap } from '@/lib/useAdminBootstrap'
import { extractApiError } from '@/lib/apiError'

export function AdminBlogImagesPage() {
  const qc = useQueryClient()
  const [dryRun, setDryRun] = useState<Record<string, unknown> | null>(null)
  const [feedback, setFeedback] = useState<{ type: 'ok' | 'err'; text: string } | null>(null)
  const bootstrap = useAdminBootstrap('/admin/blog-images/bootstrap', ['admin-blog-images'])

  const { data, isError, error } = useQuery({
    queryKey: ['admin-blog-images'],
    queryFn: async () => (await api.get('/admin/blog-images')).data.data,
  })

  const { data: jobs } = useQuery({
    queryKey: ['admin-blog-image-jobs'],
    queryFn: async () => (await api.get('/admin/blog-images/jobs', { params: { status: 'generated' } })).data.data,
  })

  const audit = useMutation({
    mutationFn: () => api.post('/admin/blog-images/audit'),
    onSuccess: () => {
      setFeedback({ type: 'ok', text: 'اسکن تصاویر انجام شد.' })
      qc.invalidateQueries({ queryKey: ['admin-blog-images'] })
    },
    onError: (err) => setFeedback({ type: 'err', text: extractApiError(err) }),
  })
  const runDry = useMutation({
    mutationFn: async () => (await api.post('/admin/blog-images/dry-run', { limit: 400, batch_size: 50 })).data.data,
    onSuccess: (d) => {
      setDryRun(d)
      setFeedback({ type: 'ok', text: 'اجرای آزمایشی آماده شد.' })
    },
    onError: (err) => setFeedback({ type: 'err', text: extractApiError(err) }),
  })
  const confirm = useMutation({
    mutationFn: () => api.post('/admin/blog-images/batches/confirm', { dry_run: dryRun, confirm: true }),
    onSuccess: () => {
      setDryRun(null)
      setFeedback({ type: 'ok', text: 'دسته تولید تأیید شد.' })
      qc.invalidateQueries({ queryKey: ['admin-blog-images'] })
    },
    onError: (err) => setFeedback({ type: 'err', text: extractApiError(err) }),
  })
  const process = useMutation({
    mutationFn: () => api.post('/admin/blog-images/jobs/process', { limit: 20 }),
    onSuccess: (res) => {
      setFeedback({ type: 'ok', text: `پردازش شد: ${res.data?.data?.processed ?? 0} کار` })
      qc.invalidateQueries({ queryKey: ['admin-blog-images'] })
      qc.invalidateQueries({ queryKey: ['admin-blog-image-jobs'] })
    },
    onError: (err) => setFeedback({ type: 'err', text: extractApiError(err) }),
  })
  const approve = useMutation({
    mutationFn: (id: number) => api.post(`/admin/blog-images/jobs/${id}/approve`),
    onSuccess: () => {
      setFeedback({ type: 'ok', text: 'تصویر تأیید و روی مقاله اعمال شد.' })
      qc.invalidateQueries({ queryKey: ['admin-blog-image-jobs'] })
      qc.invalidateQueries({ queryKey: ['admin-blog-images'] })
    },
    onError: (err) => setFeedback({ type: 'err', text: extractApiError(err) }),
  })
  const reject = useMutation({
    mutationFn: (id: number) => api.post(`/admin/blog-images/jobs/${id}/reject`, { reason: 'Poor Quality' }),
    onSuccess: () => {
      setFeedback({ type: 'ok', text: 'تصویر رد شد.' })
      qc.invalidateQueries({ queryKey: ['admin-blog-image-jobs'] })
    },
    onError: (err) => setFeedback({ type: 'err', text: extractApiError(err) }),
  })

  const auditCounts = data?.audit || {}
  const jobCounts = data?.jobs || {}

  return (
    <div className="space-y-6 animate-fade-in max-w-6xl mx-auto">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="icon" onClick={() => window.history.back()}><ArrowRight className="h-5 w-5" /></Button>
          <div>
            <h1 className="text-2xl font-bold flex items-center gap-2"><ImageIcon className="h-6 w-6" /> تصاویر هوشمند وبلاگ</h1>
            <p className="text-sm text-muted">اسکن → آزمایشی → تأیید دسته → پردازش صف. ارائه‌دهنده پیش‌فرض mock تصویرسازی توضیحی می‌سازد.</p>
          </div>
        </div>
        <div className="flex flex-wrap gap-2">
          <Link to={adminPath('blog')}><Button variant="outline">مقالات</Button></Link>
          <Link to={adminPath('content-ops')}><Button variant="outline">عملیات محتوا</Button></Link>
          <Button type="button" variant="outline" onClick={() => bootstrap.run()} disabled={bootstrap.isPending}>
            {bootstrap.isPending ? 'در حال راه‌اندازی…' : 'راه‌اندازی اولیه'}
          </Button>
          <Button variant="outline" onClick={() => audit.mutate()} disabled={audit.isPending}>اسکن همه</Button>
          <Button variant="outline" onClick={() => runDry.mutate()} disabled={runDry.isPending}>اجرای آزمایشی</Button>
          <Button onClick={() => process.mutate()} disabled={process.isPending}>پردازش صف</Button>
        </div>
      </div>

      <BootstrapStatusBanner msg={bootstrap.msg} />

      {feedback && (
        <div className={`rounded-xl border px-3 py-2 text-sm ${feedback.type === 'ok' ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200' : 'border-red-500/40 bg-red-500/10 text-red-200'}`}>
          {feedback.text}
        </div>
      )}
      {isError && (
        <div className="rounded-xl border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-200">
          {extractApiError(error)}
        </div>
      )}

      <Card>
        <CardContent className="p-4 text-sm text-muted space-y-1">
          <p>{data?.note || 'پس از راه‌اندازی اولیه، مقالات بدون تصویر را اسکن و تولید کنید.'}</p>
          <p>تأیید خودکار: <strong>{String(data?.policies?.auto_approve ?? false)}</strong> · ارائه‌دهنده: <strong>{data?.policies?.default_provider || 'mock'}</strong></p>
          <p>تصویر جعلی به‌عنوان «عکس واقعی ملک» ممنوع است. برای تصاویر واقعی OpenAI را با کلید فعال کنید.</p>
        </CardContent>
      </Card>

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        {[
          ['باید تولید شود', auditCounts.should_generate],
          ['بدون تصویر', auditCounts.no_image],
          ['بدون تصویر شاخص', auditCounts.hero_missing],
          ['تولیدشده (منتظر تأیید)', jobCounts.generated],
          ['منتشر/تأییدشده', jobCounts.approved_or_published],
          ['ناموفق', jobCounts.failed],
          ['هزینه (تومان)', jobCounts.cost_toman],
          ['در صف', jobCounts.queued],
        ].map(([label, val]) => (
          <Card key={String(label)}>
            <CardContent className="p-4">
              <p className="text-xs text-muted">{label}</p>
              <p className="text-2xl font-semibold mt-1">{val ?? 0}</p>
            </CardContent>
          </Card>
        ))}
      </div>

      {dryRun && (
        <Card>
          <CardHeader><CardTitle className="text-base">پیش‌نمایش اجرای آزمایشی</CardTitle></CardHeader>
          <CardContent className="space-y-3 text-sm">
            <p>مقالات: <strong>{String(dryRun.count)}</strong> · برآورد هزینه: <strong>{String(dryRun.estimated_total_cost_toman)}</strong> · ارائه‌دهنده: {String(dryRun.provider)}</p>
            <p className="text-muted">{String(dryRun.note)}</p>
            <Button onClick={() => confirm.mutate()} disabled={confirm.isPending || !dryRun.count}>تأیید تولید گروهی</Button>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader><CardTitle className="text-base">تولیدشده — تأیید / رد</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {(jobs || []).slice(0, 20).map((j: { id: number; public_url?: string; alt_text?: string; blog_post_id: number; status: string }) => (
            <div key={j.id} className="flex flex-wrap items-center gap-3 border-b border-card-border pb-3">
              {j.public_url && <img src={j.public_url} alt={j.alt_text || ''} className="h-16 w-28 object-cover rounded-md" width={112} height={64} />}
              <div className="flex-1 text-sm">
                <p>کار #{j.id} · مقاله {j.blog_post_id} · {j.status}</p>
                <p className="text-xs text-muted">{j.alt_text}</p>
              </div>
              <Button size="sm" onClick={() => approve.mutate(j.id)}>تأیید</Button>
              <Button size="sm" variant="outline" onClick={() => reject.mutate(j.id)}>رد</Button>
            </div>
          ))}
          {!jobs?.length && <p className="text-muted text-sm">صف تأیید خالی است — اسکن و پردازش را اجرا کنید یا از ویرایشگر «ساخت تصویر شاخص» بزنید.</p>}
        </CardContent>
      </Card>
    </div>
  )
}
