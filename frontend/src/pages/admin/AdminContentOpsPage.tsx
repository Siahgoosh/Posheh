import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, Bot, Sparkles } from 'lucide-react'
import api from '@/lib/api'
import { adminPath } from '@/lib/adminPaths'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { OPS_JOB_TYPE_FA, labelFa } from '@/lib/blogLabelsFa'
import { BootstrapStatusBanner, useAdminBootstrap } from '@/lib/useAdminBootstrap'

export function AdminContentOpsPage() {
  const qc = useQueryClient()
  const [jobType, setJobType] = useState('brief')
  const [postId, setPostId] = useState('')
  const bootstrap = useAdminBootstrap('/admin/content-ops/bootstrap', ['admin-content-ops', 'admin-content-ops-jobs'])

  const { data, isLoading } = useQuery({
    queryKey: ['admin-content-ops'],
    queryFn: async () => (await api.get('/admin/content-ops')).data.data,
  })

  const { data: jobs } = useQuery({
    queryKey: ['admin-content-ops-jobs'],
    queryFn: async () => (await api.get('/admin/content-ops/jobs')).data.data,
  })

  const processJobs = useMutation({
    mutationFn: () => api.post('/admin/content-ops/jobs/process', { limit: 10 }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-content-ops'] })
      qc.invalidateQueries({ queryKey: ['admin-content-ops-jobs'] })
    },
  })
  const enqueue = useMutation({
    mutationFn: () => api.post('/admin/content-ops/jobs', {
      type: jobType,
      blog_post_id: postId ? Number(postId) : undefined,
      run_now: true,
      input: {},
    }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-content-ops-jobs'] })
      qc.invalidateQueries({ queryKey: ['admin-content-ops'] })
    },
  })
  const weekly = useMutation({
    mutationFn: () => api.get('/admin/content-ops/reports', { params: { type: 'weekly', generate: 1 } }),
  })

  const jobCounts = data?.jobs || {}
  const usage = data?.ai_usage?.month || {}

  return (
    <div className="space-y-6 animate-fade-in max-w-6xl mx-auto">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="icon" onClick={() => window.history.back()}><ArrowRight className="h-5 w-5" /></Button>
          <div>
            <h1 className="text-2xl font-bold flex items-center gap-2"><Bot className="h-6 w-6" /> عملیات محتوای هوشمند</h1>
            <p className="text-sm text-muted">هوش مصنوعی = دستیار — نه ناشر خودکار. بدون آمار/ریویو/قیمت جعلی.</p>
          </div>
        </div>
        <div className="flex flex-wrap gap-2">
          <Link to={adminPath('blog')}><Button variant="outline">مدیریت وبلاگ</Button></Link>
          <Link to={adminPath('seo-growth')}><Button variant="outline">رشد سئو</Button></Link>
          <Button type="button" variant="outline" onClick={() => bootstrap.run()} disabled={bootstrap.isPending}>
            {bootstrap.isPending ? 'در حال راه‌اندازی…' : 'راه‌اندازی اولیه'}
          </Button>
          <Button variant="outline" onClick={() => processJobs.mutate()}>پردازش صف</Button>
          <Button onClick={() => weekly.mutate()}><Sparkles className="h-4 w-4 ml-1" /> گزارش هفتگی</Button>
        </div>
      </div>

      <BootstrapStatusBanner msg={bootstrap.msg} />

      <Card>
        <CardContent className="p-4 text-sm text-muted space-y-1">
          <p>{data?.note}</p>
          <p>انتشار خودکار: <strong>{String(data?.policies?.auto_publish ?? false)}</strong> · درج خودکار لینک: <strong>{String(data?.policies?.auto_insert_links ?? false)}</strong></p>
          <p>فقط راهنمای کیفیت داخلی — نمره گوگل نیست.</p>
        </CardContent>
      </Card>

      {isLoading ? <p className="text-muted">در حال بارگذاری…</p> : (
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
          {[
            ['در صف', jobCounts.queued],
            ['در حال اجرا', jobCounts.running],
            ['ناموفق', jobCounts.failed],
            ['تکمیل‌شده', jobCounts.completed],
            ['نیاز به به‌روزرسانی', data?.refresh_needed],
            ['ادعای پرریسک', data?.high_risk_claims],
            ['توکن ماه جاری', usage.tokens],
            ['هزینه ماه (تومان)', usage.cost_toman],
          ].map(([label, val]) => (
            <Card key={String(label)}>
              <CardContent className="p-4">
                <p className="text-xs text-muted">{label}</p>
                <p className="text-2xl font-semibold mt-1">{val ?? 0}</p>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      <Card>
        <CardHeader><CardTitle className="text-base">افزودن کار هوش مصنوعی</CardTitle></CardHeader>
        <CardContent className="flex flex-wrap gap-2 items-end">
          <label className="text-sm">
            نوع کار
            <select className="block mt-1 border rounded-md px-2 py-1 bg-background" value={jobType} onChange={(e) => setJobType(e.target.value)}>
              {Object.keys(OPS_JOB_TYPE_FA).map((t) => (
                <option key={t} value={t}>{OPS_JOB_TYPE_FA[t]}</option>
              ))}
            </select>
          </label>
          <label className="text-sm">
            شناسه مقاله (اختیاری)
            <input className="block mt-1 border rounded-md px-2 py-1 bg-background" value={postId} onChange={(e) => setPostId(e.target.value)} placeholder="اختیاری" />
          </label>
          <Button onClick={() => enqueue.mutate()} disabled={enqueue.isPending}>اجرا</Button>
          {enqueue.isError && <p className="text-sm text-red-500">دستیار موقتاً در دسترس نیست یا کار رد شد</p>}
        </CardContent>
      </Card>

      <div className="grid lg:grid-cols-2 gap-4">
        <Card>
          <CardHeader><CardTitle className="text-base">صف بررسی</CardTitle></CardHeader>
          <CardContent className="space-y-2 text-sm">
            {(data?.review_queue || []).slice(0, 12).map((p: { id: number; title: string; ops_status?: string; review_status?: string }) => (
              <div key={p.id} className="flex justify-between gap-2 border-b border-card-border pb-2">
                <Link className="text-primary" to={adminPath(`blog/${p.id}/edit`)}>{p.title}</Link>
                <span className="text-xs text-muted">{p.ops_status || p.review_status}</span>
              </div>
            ))}
            {!data?.review_queue?.length && <p className="text-muted">صف خالی است</p>}
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle className="text-base">کارهای اخیر</CardTitle></CardHeader>
          <CardContent className="space-y-2 text-sm max-h-80 overflow-auto">
            {(jobs || []).slice(0, 20).map((j: { id: number; type: string; status: string; error?: string; confidence?: number }) => (
              <div key={j.id} className="border-b border-card-border pb-2">
                <div className="flex justify-between">
                  <span>#{j.id} {labelFa(OPS_JOB_TYPE_FA, j.type)}</span>
                  <span>{j.status}</span>
                </div>
                {j.error && <p className="text-xs text-red-500">{j.error}</p>}
                {j.confidence != null && <p className="text-xs text-muted">اطمینان: {j.confidence}</p>}
              </div>
            ))}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader><CardTitle className="text-base">برترین فرصت‌ها (از رشد سئو)</CardTitle></CardHeader>
        <CardContent className="space-y-2 text-sm">
          {(data?.top_opportunities || []).map((o: { id: number; type: string; title: string; priority_score?: number }) => (
            <div key={o.id} className="flex justify-between gap-2">
              <span>{o.title}</span>
              <span className="text-xs text-muted">{o.type} · {o.priority_score ?? '—'}</span>
            </div>
          ))}
          {!data?.top_opportunities?.length && <p className="text-muted">هنوز فرصت واقعی ثبت نشده (اتصال کنسول جستجو/CSV لازم است)</p>}
        </CardContent>
      </Card>
    </div>
  )
}
