import { useQuery } from '@tanstack/react-query'
import { Calendar, AlertTriangle } from 'lucide-react'
import api from '@/lib/api'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { formatPrice } from '@/lib/utils'

export function RentalsPage() {
  const { data: expiring } = useQuery({
    queryKey: ['rentals-expiring'],
    queryFn: async () => (await api.get('/rentals/expiring')).data.data,
  })

  const { data: contracts } = useQuery({
    queryKey: ['rentals'],
    queryFn: async () => (await api.get('/rentals')).data.data,
  })

  const list = contracts?.data || []

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold flex items-center gap-2"><Calendar className="h-6 w-6 text-primary" />مدیریت اجاره ملک</h1>

      {expiring?.length > 0 && (
        <Card className="p-4 border-amber-500/30 bg-amber-500/5">
          <h2 className="font-semibold flex items-center gap-2 text-amber-500 mb-3"><AlertTriangle className="h-4 w-4" />تمدید نزدیک ({expiring.length})</h2>
          {expiring.map((c: { id: number; end_date: string; property?: { code: string }; tenant_name?: string }) => (
            <p key={c.id} className="text-sm">{c.property?.code} — {c.tenant_name} — پایان: {c.end_date}</p>
          ))}
        </Card>
      )}

      <div className="space-y-2">
        {list.map((c: { id: number; tenant_name?: string; end_date?: string; rent?: number; property?: { code: string } }) => (
          <Card key={c.id} className="p-4 flex justify-between">
            <div>
              <p className="font-medium">{c.property?.code}</p>
              <p className="text-xs text-muted">{c.tenant_name}</p>
            </div>
            <div className="text-left">
              <p className="text-sm">{formatPrice(c.rent ?? 0)}</p>
              <Badge variant="outline" className="text-xs">{c.end_date}</Badge>
            </div>
          </Card>
        ))}
        {!list.length && <p className="text-center text-muted py-8">قرارداد اجاره‌ای ثبت نشده</p>}
      </div>
    </div>
  )
}
