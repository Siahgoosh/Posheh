import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowRight, Phone, Building2, Users, Sparkles } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

export function OwnerDetailPage() {
  const { id } = useParams<{ id: string }>()
  const { data: owner, isLoading } = useQuery({
    queryKey: ['owner', id],
    queryFn: async () => (await api.get(`/owners/${id}`)).data.data,
    enabled: !!id,
  })

  const { data: matches, isLoading: matchesLoading } = useQuery({
    queryKey: ['owner-matches', id],
    queryFn: async () => (await api.get(`/owners/${id}/customer-matches`)).data.data,
    enabled: !!id,
  })

  if (isLoading) return <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
  if (!owner) return <p className="text-center text-muted py-20">مالک یافت نشد</p>

  return (
    <div className="space-y-6 max-w-3xl mx-auto animate-fade-in">
      <div className="flex items-center gap-3">
        <Link to="/owners"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
        <h1 className="text-2xl font-bold">{owner.name}</h1>
      </div>
      <Card>
        <CardHeader><CardTitle>اطلاعات</CardTitle></CardHeader>
        <CardContent className="space-y-2 text-sm">
          {owner.mobile && <p className="flex items-center gap-2" dir="ltr"><Phone className="h-4 w-4" />{owner.mobile}</p>}
          {owner.national_id && <p>کد ملی: {owner.national_id}</p>}
          {owner.notes && <p className="text-muted">{owner.notes}</p>}
        </CardContent>
      </Card>

      {owner.properties?.length > 0 && (
        <Card>
          <CardHeader><CardTitle className="flex items-center gap-2"><Building2 className="h-4 w-4" />املاک</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {owner.properties.map((p: { id: number; code: string; type_label?: string }) => (
              <Link key={p.id} to={`/properties/${p.id}`} className="block p-3 rounded-xl glass-hover text-sm">
                {p.code} — {p.type_label}
              </Link>
            ))}
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Sparkles className="h-4 w-4 text-primary" />
            تطبیق فایل با مشتری
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <p className="text-sm text-muted">
            بر اساس نیاز مشتریان دفتر، بهترین تطبیق‌ها برای فایل‌های این مالک پیشنهاد می‌شود.
          </p>
          {matchesLoading && <p className="text-sm text-muted">در حال محاسبه تطبیق…</p>}
          {!matchesLoading && matches && (
            <>
              <div className="flex gap-2 text-xs">
                <Badge variant="outline"><Users className="h-3 w-3 ml-1" />{matches.matched_customers_count ?? 0} مشتری مرتبط</Badge>
                <Badge variant="outline">{matches.properties_count ?? 0} فایل فعال</Badge>
              </div>
              {(matches.top_customers ?? []).length === 0 ? (
                <p className="text-sm text-muted py-4">هنوز تطبیق مناسبی پیدا نشد. نیاز/بودجه مشتریان را تکمیل کنید.</p>
              ) : (
                <div className="space-y-2">
                  {matches.top_customers.map((row: {
                    customer: { id: number; name: string; mobile?: string }
                    score: number
                    band: string
                    property_code: string
                  }) => (
                    <Link
                      key={`${row.customer.id}-${row.property_code}`}
                      to={`/customers/${row.customer.id}`}
                      className="flex items-center justify-between p-3 rounded-xl bg-background/50 hover:bg-background text-sm"
                    >
                      <div>
                        <p className="font-medium">{row.customer.name}</p>
                        <p className="text-xs text-muted mt-0.5">
                          {row.customer.mobile || '—'} · فایل {row.property_code}
                        </p>
                      </div>
                      <div className="text-left">
                        <Badge>{row.band}</Badge>
                        <p className="text-xs text-muted mt-1">امتیاز {row.score}</p>
                      </div>
                    </Link>
                  ))}
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
