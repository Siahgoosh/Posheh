import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Star } from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface PropertyItem {
  id: number
  code: string
  type_label: string
  price?: number
  city?: string
  status_label: string
}

export function FavoritesPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['favorites'],
    queryFn: async () => {
      const res = await api.get('/properties', { params: { favorites_only: true } })
      return res.data.data as PropertyItem[]
    },
  })

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  return (
    <div className="space-y-6 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <Star className="h-6 w-6 text-warning" />
          علاقه‌مندی‌ها
        </h1>
        <p className="text-muted mt-1">املاکی که نشان کرده‌اید</p>
      </div>

      {!data?.length ? (
        <Card className="p-12 text-center">
          <Star className="h-12 w-12 text-muted mx-auto mb-4 opacity-40" />
          <p className="text-muted">هنوز ملکی به علاقه‌مندی‌ها اضافه نکرده‌اید</p>
          <Link to="/properties" className="text-primary text-sm mt-2 inline-block hover:underline">
            مشاهده املاک
          </Link>
        </Card>
      ) : (
        <div className="grid gap-3">
          {data.map((p) => (
            <Link key={p.id} to={`/properties/${p.id}`}>
              <Card className="glass-hover">
                <CardContent className="flex items-center justify-between p-4">
                  <div>
                    <p className="font-medium">{p.code}</p>
                    <p className="text-sm text-muted">{p.type_label} · {p.city}</p>
                  </div>
                  <div className="text-left">
                    {p.price != null && <p className="font-semibold text-primary">{formatPrice(p.price)}</p>}
                    <Badge variant="outline" className="mt-1">{p.status_label}</Badge>
                  </div>
                </CardContent>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}
