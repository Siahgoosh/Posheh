import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, ImageIcon } from 'lucide-react'
import api from '@/lib/api'
import { adminPath } from '@/lib/adminPaths'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

export function AdminBlogImagesPage() {
  const qc = useQueryClient()
  const [dryRun, setDryRun] = useState<Record<string, unknown> | null>(null)

  const { data } = useQuery({
    queryKey: ['admin-blog-images'],
    queryFn: async () => (await api.get('/admin/blog-images')).data.data,
  })

  const { data: jobs } = useQuery({
    queryKey: ['admin-blog-image-jobs'],
    queryFn: async () => (await api.get('/admin/blog-images/jobs', { params: { status: 'generated' } })).data.data,
  })

  const bootstrap = useMutation({
    mutationFn: () => api.post('/admin/blog-images/bootstrap'),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-blog-images'] }),
  })
  const audit = useMutation({
    mutationFn: () => api.post('/admin/blog-images/audit'),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-blog-images'] }),
  })
  const runDry = useMutation({
    mutationFn: async () => (await api.post('/admin/blog-images/dry-run', { limit: 400, batch_size: 50 })).data.data,
    onSuccess: (d) => setDryRun(d),
  })
  const confirm = useMutation({
    mutationFn: () => api.post('/admin/blog-images/batches/confirm', { dry_run: dryRun, confirm: true }),
    onSuccess: () => {
      setDryRun(null)
      qc.invalidateQueries({ queryKey: ['admin-blog-images'] })
    },
  })
  const process = useMutation({
    mutationFn: () => api.post('/admin/blog-images/jobs/process', { limit: 10 }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-blog-images'] })
      qc.invalidateQueries({ queryKey: ['admin-blog-image-jobs'] })
    },
  })
  const approve = useMutation({
    mutationFn: (id: number) => api.post(`/admin/blog-images/jobs/${id}/approve`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-blog-image-jobs'] }),
  })
  const reject = useMutation({
    mutationFn: (id: number) => api.post(`/admin/blog-images/jobs/${id}/reject`, { reason: 'Poor Quality' }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-blog-image-jobs'] }),
  })

  const auditCounts = data?.audit || {}
  const jobCounts = data?.jobs || {}

  return (
    <div className="space-y-6 animate-fade-in max-w-6xl mx-auto">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="icon" onClick={() => window.history.back()}><ArrowRight className="h-5 w-5" /></Button>
          <div>
            <h1 className="text-2xl font-bold flex items-center gap-2"><ImageIcon className="h-6 w-6" /> Blog AI Images</h1>
            <p className="text-sm text-muted">فقط برای مقالات با ارزش واقعی — Preview → Human Approval. Illustrative by default.</p>
          </div>
        </div>
        <div className="flex flex-wrap gap-2">
          <Link to={adminPath('content-ops')}><Button variant="outline">Content Ops</Button></Link>
          <Button variant="outline" onClick={() => bootstrap.mutate()}>Bootstrap</Button>
          <Button variant="outline" onClick={() => audit.mutate()}>Scan All</Button>
          <Button variant="outline" onClick={() => runDry.mutate()}>Dry Run</Button>
          <Button onClick={() => process.mutate()}>Process Queue</Button>
        </div>
      </div>

      <Card>
        <CardContent className="p-4 text-sm text-muted space-y-1">
          <p>{data?.note}</p>
          <p>Auto-approve: <strong>{String(data?.policies?.auto_approve ?? false)}</strong> · Provider: <strong>{data?.policies?.default_provider}</strong></p>
          <p>تصویر جعلی به‌عنوان «عکس واقعی ملک» ممنوع است.</p>
        </CardContent>
      </Card>

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        {[
          ['Should generate', auditCounts.should_generate],
          ['No image', auditCounts.no_image],
          ['Hero missing', auditCounts.hero_missing],
          ['Generated (awaiting)', jobCounts.generated],
          ['Published/Approved', jobCounts.approved_or_published],
          ['Failed', jobCounts.failed],
          ['Cost (toman)', jobCounts.cost_toman],
          ['Queued', jobCounts.queued],
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
          <CardHeader><CardTitle className="text-base">Dry Run Preview</CardTitle></CardHeader>
          <CardContent className="space-y-3 text-sm">
            <p>Articles: <strong>{String(dryRun.count)}</strong> · Est. cost: <strong>{String(dryRun.estimated_total_cost_toman)}</strong> · Provider: {String(dryRun.provider)}</p>
            <p className="text-muted">{String(dryRun.note)}</p>
            <Button onClick={() => confirm.mutate()} disabled={confirm.isPending}>Confirm Mass Generation</Button>
            {confirm.isError && <p className="text-red-500 text-xs">Batch rejected — check budget / confirmation</p>}
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader><CardTitle className="text-base">Generated — Approve / Reject</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {(jobs || []).slice(0, 20).map((j: { id: number; public_url?: string; alt_text?: string; blog_post_id: number; status: string }) => (
            <div key={j.id} className="flex flex-wrap items-center gap-3 border-b border-card-border pb-3">
              {j.public_url && <img src={j.public_url} alt={j.alt_text || ''} className="h-16 w-28 object-cover rounded-md" width={112} height={64} />}
              <div className="flex-1 text-sm">
                <p>Job #{j.id} · post {j.blog_post_id} · {j.status}</p>
                <p className="text-xs text-muted">{j.alt_text}</p>
              </div>
              <Button size="sm" onClick={() => approve.mutate(j.id)}>Approve</Button>
              <Button size="sm" variant="outline" onClick={() => reject.mutate(j.id)}>Reject</Button>
            </div>
          ))}
          {!jobs?.length && <p className="text-muted text-sm">صف تأیید خالی است</p>}
        </CardContent>
      </Card>
    </div>
  )
}
