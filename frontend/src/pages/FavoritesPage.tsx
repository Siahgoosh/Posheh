import { useQuery } from '@tanstack/react-query'
import { Star } from 'lucide-react'
import { Link } from 'react-router-dom'
import api from '@/lib/api'
import { Card } from '@/components/ui/card'
import { formatPrice } from '@/lib/utils'

export function FavoritesPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['favorites'],
    queryFn: async () => {
      const res = await api.get('/properties', { params: { favorites_only: true } })
      return res.data.data
    },
  })

  if (isLoading) return <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold flex items-center gap-2"><Star className="h-6 w-6 text-primary" />علاقه‌مندی‌ها</h1>
      <div className="space-y-3">
        {data?.map((p: { id: number; code: string; city?: string; price?: number }) => (
          <Link key={p.id} to={`/properties/${p.id}`}>
            <Card className="p-4 glass-hover flex justify-between">
              <span className="font-medium">{p.code}</span>
              <span className="text-muted text-sm">{p.city} — {formatPrice(p.price ?? 0)}</span>
            </Card>
          </Link>
        ))}
        {!data?.length && <p className="text-center text-muted py-12">علاقه‌مندی ثبت نشده</p>}
      </div>
    </div>
  )
}
