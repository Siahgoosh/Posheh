import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { useMutation, useQuery } from '@tanstack/react-query'
import { BadgeCheck, Building2, MapPin, Phone, Send } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'
import { SeoHead } from '@/components/seo/SeoHead'

export function OfficeSitePage() {
  const { subdomain } = useParams<{ subdomain: string }>()
  const [visitForm, setVisitForm] = useState({ name: '', mobile: '', message: '', property_id: '' })
  const [sent, setSent] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['office-site', subdomain],
    queryFn: async () => (await api.get(`/sites/${subdomain}`)).data.data,
    enabled: !!subdomain,
  })

  const visitMutation = useMutation({
    mutationFn: () => api.post(`/sites/${subdomain}/visit-request`, {
      ...visitForm,
      property_id: visitForm.property_id ? Number(visitForm.property_id) : undefined,
    }),
    onSuccess: () => { setSent(true); setVisitForm({ name: '', mobile: '', message: '', property_id: '' }) },
  })

  if (isLoading || !data) {
    return <div className="min-h-screen flex items-center justify-center text-muted">بارگذاری…</div>
  }

  const office = data.office

  return (
    <div className="min-h-screen bg-background">
      <SeoHead title={office.name} description={office.description} path={`/site/${subdomain}`} />
      <header className="border-b border-card-border glass sticky top-0 z-10">
        <div className="container mx-auto max-w-5xl px-4 py-6 flex flex-wrap items-center gap-4">
          {office.logo_url ? <img src={office.logo_url} alt="" className="h-14 w-14 rounded-xl object-cover" /> : (
            <div className="h-14 w-14 rounded-xl bg-primary/15 flex items-center justify-center"><Building2 className="h-7 w-7 text-primary" /></div>
          )}
          <div className="flex-1 min-w-0">
            <h1 className="text-2xl font-bold flex items-center gap-2">{office.name}{office.is_verified && <BadgeCheck className="h-5 w-5 text-primary" />}</h1>
            <p className="text-muted text-sm flex items-center gap-1"><MapPin className="h-3 w-3" />{office.city} — {office.address}</p>
            {office.phone && <p className="text-sm"><Phone className="h-3 w-3 inline ml-1" />{office.phone}</p>}
          </div>
          <Link to="/" className="text-xs text-muted hover:text-primary">قدرت گرفته از پوشه</Link>
        </div>
      </header>

      <main className="container mx-auto max-w-5xl px-4 py-8 space-y-10">
        {office.description && <p className="text-muted leading-relaxed">{office.description}</p>}

        <section>
          <h2 className="text-xl font-bold mb-4">املاک</h2>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {data.properties?.map((p: { id: number; code: string; price?: number; city?: string; district?: string; area?: number }) => (
              <Card key={p.id} className="p-4 space-y-2">
                <p className="font-medium">{p.code}</p>
                <p className="text-sm text-muted">{p.city} {p.district} · {p.area} متر</p>
                {p.price && <p className="text-primary font-bold">{formatPrice(p.price)}</p>}
                <Button size="sm" variant="outline" onClick={() => setVisitForm((f) => ({ ...f, property_id: String(p.id) }))}>درخواست بازدید</Button>
              </Card>
            ))}
          </div>
        </section>

        {data.posts?.length > 0 && (
          <section>
            <h2 className="text-xl font-bold mb-4">وبلاگ دفتر</h2>
            <div className="space-y-4">
              {data.posts.map((post: { id: number; title: string; excerpt?: string; body?: string }) => (
                <Card key={post.id} className="p-4">
                  <h3 className="font-semibold">{post.title}</h3>
                  <p className="text-sm text-muted mt-2">{post.excerpt || post.body}</p>
                </Card>
              ))}
            </div>
          </section>
        )}

        <section>
          <Card className="p-6 max-w-md">
            <h2 className="text-lg font-bold mb-4 flex items-center gap-2"><Send className="h-5 w-5" /> درخواست بازدید</h2>
            {sent && <p className="text-sm text-success mb-3">درخواست شما ثبت شد.</p>}
            <div className="space-y-3">
              <Input placeholder="نام" value={visitForm.name} onChange={(e) => setVisitForm((f) => ({ ...f, name: e.target.value }))} />
              <Input placeholder="موبایل" value={visitForm.mobile} onChange={(e) => setVisitForm((f) => ({ ...f, mobile: e.target.value }))} dir="ltr" />
              <textarea className="w-full min-h-[72px] rounded-xl border border-card-border bg-background/50 p-3 text-sm" placeholder="پیام (اختیاری)" value={visitForm.message} onChange={(e) => setVisitForm((f) => ({ ...f, message: e.target.value }))} />
              <Button className="w-full" disabled={visitMutation.isPending || !visitForm.name || !visitForm.mobile} onClick={() => visitMutation.mutate()}>
                ارسال درخواست
              </Button>
            </div>
          </Card>
        </section>
      </main>
    </div>
  )
}
