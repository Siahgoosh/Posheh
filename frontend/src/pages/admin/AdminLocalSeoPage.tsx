import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, MapPin } from 'lucide-react'
import api from '@/lib/api'
import { adminPath } from '@/lib/adminPaths'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

export function AdminLocalSeoPage() {
  const qc = useQueryClient()
  const [locName, setLocName] = useState('')
  const [locType, setLocType] = useState('city')
  const [locDesc, setLocDesc] = useState('')
  const [locUnique, setLocUnique] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['admin-seo-local'],
    queryFn: async () => (await api.get('/admin/seo/local')).data.data,
  })

  const bootstrap = useMutation({
    mutationFn: () => api.post('/admin/seo/local/bootstrap'),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-seo-local'] }),
  })
  const napScan = useMutation({
    mutationFn: () => api.get('/admin/seo/local/nap-scan'),
  })
  const genOpp = useMutation({
    mutationFn: () => api.post('/admin/seo/local/opportunities/generate'),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-seo-local'] }),
  })
  const createLoc = useMutation({
    mutationFn: () => api.post('/admin/seo/local/locations', {
      name: locName,
      type: locType,
      description: locDesc,
      unique_value: locUnique,
    }),
    onSuccess: () => {
      setLocName(''); setLocDesc(''); setLocUnique('')
      qc.invalidateQueries({ queryKey: ['admin-seo-local'] })
    },
  })

  const counts = data?.counts || {}

  return (
    <div className="space-y-6 animate-fade-in max-w-6xl mx-auto">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="icon" onClick={() => window.history.back()}><ArrowRight className="h-5 w-5" /></Button>
          <div>
            <h1 className="text-2xl font-bold flex items-center gap-2"><MapPin className="h-6 w-6" /> Local SEO / Entities</h1>
            <p className="text-sm text-muted">بدون صفحه شهری Thin — بدون NAP/مختصات/Review جعلی</p>
          </div>
        </div>
        <div className="flex flex-wrap gap-2">
          <Link to={adminPath('seo-technical')}><Button variant="outline">Technical SEO</Button></Link>
          <Link to={adminPath('seo-growth')}><Button variant="outline">SEO Growth</Button></Link>
          <Button variant="outline" onClick={() => bootstrap.mutate()}>Bootstrap Entities</Button>
          <Button variant="outline" onClick={() => napScan.mutate()}>NAP Scan</Button>
          <Button onClick={() => genOpp.mutate()}>Weekly Opportunities</Button>
        </div>
      </div>

      <Card>
        <CardContent className="p-4 text-sm text-muted space-y-1">
          <p>{data?.note}</p>
          <p>Field metrics: clicks/impressions/ROI = <strong>{data?.field_metrics?.location_roi || 'UNKNOWN'}</strong></p>
          <p>Internal graph only — <strong>not</strong> a Google Knowledge Graph claim.</p>
        </CardContent>
      </Card>

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        {[
          ['Locations', counts.locations],
          ['Published locations', counts.published_locations],
          ['Topics', counts.topics],
          ['Local articles', counts.local_articles],
          ['Approved knowledge', counts.approved_knowledge],
          ['Live web properties', counts.active_web_properties],
        ].map(([label, value]) => (
          <Card key={String(label)}>
            <CardContent className="p-4">
              <p className="text-xs text-muted">{label}</p>
              <p className="text-2xl font-bold">{value ?? '—'}</p>
            </CardContent>
          </Card>
        ))}
      </div>

      {!!data?.nap_warnings?.length && (
        <Card>
          <CardHeader><CardTitle className="text-base">NAP / Entity Consistency</CardTitle></CardHeader>
          <CardContent className="space-y-2 text-sm">
            {data.nap_warnings.map((w: { severity: string; code: string; message: string }, i: number) => (
              <div key={i} className="rounded-lg border border-card-border px-3 py-2">
                [{w.severity}] {w.code}: {w.message}
              </div>
            ))}
          </CardContent>
        </Card>
      )}

      {napScan.data?.data?.warnings && (
        <Card>
          <CardHeader><CardTitle className="text-base">Latest NAP Scan</CardTitle></CardHeader>
          <CardContent className="text-sm space-y-2">
            {napScan.data.data.warnings.map((w: { severity: string; message: string }, i: number) => (
              <p key={i}>[{w.severity}] {w.message}</p>
            ))}
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader><CardTitle className="text-base">Topical Authority (internal)</CardTitle></CardHeader>
        <CardContent className="space-y-2 text-sm">
          {(data?.topical_authority_internal || []).map((t: { topic: string; authority_score?: number; has_pillar?: boolean }) => (
            <div key={t.topic} className="flex justify-between border border-card-border rounded-lg px-3 py-2">
              <span>{t.topic}</span>
              <span className="text-muted">score {t.authority_score ?? '—'} · pillar {t.has_pillar ? 'yes' : 'no'}</span>
            </div>
          ))}
          {!data?.topical_authority_internal?.length && <p className="text-muted">هنوز داده topic score رشد SEO موجود نیست.</p>}
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle className="text-base">Local Opportunities (max 10)</CardTitle></CardHeader>
        <CardContent className="space-y-2 text-sm">
          {(data?.opportunities || []).map((o: { id: number; rank: number; title: string; reason?: string; priority: string }) => (
            <div key={o.id} className="rounded-lg border border-card-border px-3 py-2">
              <p className="font-medium">#{o.rank} [{o.priority}] {o.title}</p>
              <p className="text-xs text-muted">{o.reason}</p>
            </div>
          ))}
          {!data?.opportunities?.length && <p className="text-muted">فرصتی ثبت نشده — Generate را بزنید.</p>}
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle className="text-base">ایجاد Location (Draft — Publish فقط با Quality Gate)</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <Input placeholder="نام مکان واقعی" value={locName} onChange={(e) => setLocName(e.target.value)} />
          <select className="w-full rounded-xl border border-card-border bg-background/50 p-3 text-sm" value={locType} onChange={(e) => setLocType(e.target.value)}>
            {['country', 'province', 'city', 'district', 'neighborhood', 'street'].map((t) => <option key={t} value={t}>{t}</option>)}
          </select>
          <textarea className="w-full min-h-[80px] rounded-xl border border-card-border bg-background/50 p-3 text-sm" placeholder="توضیح واقعی (≥120)" value={locDesc} onChange={(e) => setLocDesc(e.target.value)} />
          <textarea className="w-full min-h-[80px] rounded-xl border border-card-border bg-background/50 p-3 text-sm" placeholder="Unique Value محلی واقعی (≥80) — نه keyword stuffing" value={locUnique} onChange={(e) => setLocUnique(e.target.value)} />
          <Button disabled={!locName || createLoc.isPending} onClick={() => createLoc.mutate()}>ذخیره Draft</Button>
          {createLoc.isError && <p className="text-sm text-danger">خطا در ذخیره</p>}
          {createLoc.isSuccess && <p className="text-sm text-muted">Draft ذخیره شد. Publish فقط پس از پاس شدن Quality Gate.</p>}
        </CardContent>
      </Card>

      {data?.business && (
        <Card>
          <CardHeader><CardTitle className="text-base">Business Profile (NAP source)</CardTitle></CardHeader>
          <CardContent className="text-sm space-y-1">
            <p>{data.business.business_name} · {data.business.email}</p>
            <p dir="ltr">{data.business.website}</p>
            <p>Phone: {data.business.phone || '— (not set — do not invent)'}</p>
            <p>Address: {data.business.address_line || '— (not set — do not invent)'}</p>
            <p>NAP complete: {data.business.nap_complete ? 'yes' : 'partial'}</p>
          </CardContent>
        </Card>
      )}

      {isLoading && <p className="text-muted text-sm">بارگذاری…</p>}
    </div>
  )
}
