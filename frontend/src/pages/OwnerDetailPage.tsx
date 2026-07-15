import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowRight, Phone, Building2 } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

export function OwnerDetailPage() {
  const { id } = useParams<{ id: string }>()
  const { data: owner, isLoading } = useQuery({
    queryKey: ['owner', id],
    queryFn: async () => (await api.get(`/owners/${id}`)).data.data,
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
    </div>
  )
}
