import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowRight, Target, Star } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

export function CustomerDetailPage() {
  const { id } = useParams<{ id: string }>()

  const { data: customer, isLoading } = useQuery({
    queryKey: ['customer', id],
    queryFn: async () => (await api.get(`/customers/${id}`)).data.data,
    enabled: !!id,
  })

  const { data: matches } = useQuery({
    queryKey: ['customer-matches', id],
    queryFn: async () => (await api.get(`/customers/${id}/matches`)).data.data,
    enabled: !!id,
  })

  if (isLoading) return <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
  if (!customer) return <p className="text-center text-muted py-20">مشتری یافت نشد</p>

  return (
    <div className="space-y-6 max-w-4xl mx-auto animate-fade-in">
      <div className="flex items-center gap-3">
        <Link to="/customers"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            {customer.name}
            {customer.priority === 'vip' && <Badge className="bg-accent/20 text-accent"><Star className="h-3 w-3" />VIP</Badge>}
          </h1>
          {customer.mobile && <p className="text-muted text-sm" dir="ltr">{customer.mobile}</p>}
        </div>
      </div>

      <Card>
        <CardHeader><CardTitle>نیازمندی‌ها</CardTitle></CardHeader>
        <CardContent className="text-sm space-y-1">
          {customer.preferred_city && <p>شهر: {customer.preferred_city}</p>}
          {(customer.budget_min || customer.budget_max) && (
            <p>بودجه: {customer.budget_min ? formatPrice(customer.budget_min) : '—'} — {customer.budget_max ? formatPrice(customer.budget_max) : '—'}</p>
          )}
          {customer.notes && <p className="text-muted mt-2">{customer.notes}</p>}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2"><Target className="h-5 w-5 text-primary" />تطبیق هوشمند</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {!matches?.length && <p className="text-muted text-sm">ملک منطبقی یافت نشد. فیلترها را گسترده‌تر کنید.</p>}
          {matches?.map((m: {
            score: number
            match_type?: string
            reasons: string[]
            warnings?: string[]
            checks?: Array<{ ok: boolean; label: string; detail?: string }>
            property: { id: number; code: string; type_label: string; price?: number; city?: string }
          }) => (
            <Link key={m.property.id} to={`/properties/${m.property.id}`}
              className="flex items-center justify-between p-4 rounded-xl glass-hover gap-3">
              <div className="min-w-0">
                <p className="font-medium">{m.property.code}</p>
                <p className="text-xs text-muted">{m.property.type_label} · {m.property.city}
                  {m.match_type ? ` · ${m.match_type}` : ''}
                </p>
                <div className="flex flex-wrap gap-1 mt-1">
                  {(m.checks?.length ? m.checks.filter((c) => c.ok) : m.reasons.map((r) => ({ ok: true, label: r }))).map((c) => (
                    <Badge key={c.label} variant="outline" className="text-[10px] text-emerald-600">✓ {c.label}</Badge>
                  ))}
                  {(m.warnings ?? m.checks?.filter((c) => !c.ok).map((c) => c.detail || c.label) ?? []).map((w) => (
                    <Badge key={w} variant="outline" className="text-[10px] text-amber-600">⚠ {w}</Badge>
                  ))}
                </div>
              </div>
              <div className="text-left shrink-0">
                <p className="text-lg font-bold text-primary">{m.score}%</p>
                <p className="text-xs text-muted">تطابق</p>
              </div>
            </Link>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
