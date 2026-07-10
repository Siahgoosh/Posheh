import { Link, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { BadgeCheck, Building2, Phone, MapPin } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Card } from '@/components/ui/card'
import { SeoHead } from '@/components/seo/SeoHead'

export function OfficeLandingPage() {
  const { slug } = useParams<{ slug: string }>()

  const { data, isLoading } = useQuery({
    queryKey: ['office-public', slug],
    queryFn: async () => (await api.get(`/offices/${slug}`)).data.data,
    enabled: !!slug,
  })

  if (isLoading || !data) {
    return <div className="min-h-screen flex items-center justify-center text-muted">بارگذاری…</div>
  }

  return (
    <div className="min-h-screen bg-background">
      <SeoHead title={data.name} description={data.description || `دفتر املاک ${data.name}`} path={`/o/${slug}`} />
      <header className="border-b border-card-border glass">
        <div className="container mx-auto max-w-5xl px-4 py-8 flex flex-wrap items-center gap-4">
          {data.logo_url ? <img src={data.logo_url} alt="" className="h-16 w-16 rounded-xl object-cover" /> : (
            <div className="h-16 w-16 rounded-xl bg-primary/15 flex items-center justify-center"><Building2 className="h-8 w-8 text-primary" /></div>
          )}
          <div>
            <h1 className="text-2xl font-bold flex items-center gap-2">{data.name}{data.is_verified && <BadgeCheck className="h-6 w-6 text-primary" />}</h1>
            <p className="text-muted flex items-center gap-1 mt-1"><MapPin className="h-4 w-4" />{data.city} — {data.address}</p>
            {data.phone && <p className="text-sm flex items-center gap-1 mt-1"><Phone className="h-4 w-4" />{data.phone}</p>}
          </div>
          <Link to="/register" className="mr-auto text-sm text-primary hover:underline">ثبت‌نام در پوشه</Link>
        </div>
      </header>
      <main className="container mx-auto max-w-5xl px-4 py-10 space-y-6">
        {data.description && <p className="text-muted leading-relaxed">{data.description}</p>}
        <h2 className="text-xl font-bold">املاک این دفتر</h2>
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {data.properties?.map((p: { id: number; code: string; type: string; price?: number; city?: string; district?: string }) => (
            <Card key={p.id} className="p-4">
              <p className="font-medium">{p.code}</p>
              <p className="text-sm text-muted">{p.type} · {p.city} {p.district}</p>
              {p.price && <p className="text-primary font-bold mt-2">{formatPrice(p.price)}</p>}
            </Card>
          ))}
        </div>
      </main>
    </div>
  )
}
