import { useQuery } from '@tanstack/react-query'
import { useParams } from 'react-router-dom'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

export function OwnerPortalPage() {
  const { token } = useParams<{ token: string }>()

  const { data, isLoading, error } = useQuery({
    queryKey: ['owner-portal', token],
    queryFn: async () => (await api.get(`/owner-portal/${token}`)).data.data,
    enabled: !!token,
  })

  if (isLoading) {
    return <div className="min-h-screen flex items-center justify-center"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
  }

  if (error || !data) {
    return <div className="min-h-screen flex items-center justify-center text-muted">لینک پورتال نامعتبر یا منقضی شده است.</div>
  }

  return (
    <div className="min-h-screen bg-background text-foreground p-6">
      <div className="max-w-3xl mx-auto space-y-6">
        <div className="text-center space-y-2">
          <h1 className="text-2xl font-bold">پورتال مالک</h1>
          <p className="text-muted">{data.owner.name} — {data.office?.name}</p>
        </div>
        <Card>
          <CardHeader><CardTitle>املاک شما</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            {data.properties?.length ? data.properties.map((p: { id: number; code: string; type: string; status: string; price: number; city: string; district?: string }) => (
              <div key={p.id} className="p-3 rounded-xl border border-card-border text-sm">
                <p className="font-medium">{p.code} — {p.type}</p>
                <p className="text-muted">{p.city}{p.district ? ` / ${p.district}` : ''} — {formatPrice(p.price)}</p>
                <p className="text-xs text-muted mt-1">وضعیت: {p.status}</p>
              </div>
            )) : <p className="text-muted">ملکی ثبت نشده.</p>}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
