import { useQuery } from '@tanstack/react-query'
import { Star } from 'lucide-react'
import api from '@/lib/api'
import { formatNumber } from '@/lib/utils'
import { PropertyCard, type PropertyItem } from '@/components/PropertyCard'

export function FavoritesPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['favorites'],
    queryFn: async () => (await api.get('/properties/favorites')).data,
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <Star className="h-6 w-6 text-warning" />
          علاقه‌مندی‌ها
        </h1>
        <p className="text-muted mt-1">{formatNumber(data?.meta?.total ?? 0)} ملک ذخیره شده</p>
      </div>

      {isLoading ? (
        <div className="flex justify-center py-20">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : data?.data?.length ? (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {data.data.map((p: PropertyItem) => (
            <PropertyCard key={p.id} property={{ ...p, is_favorite: true }} />
          ))}
        </div>
      ) : (
        <div className="text-center py-20 text-muted">
          <Star className="h-12 w-12 mx-auto mb-4 opacity-30" />
          <p>هنوز ملکی به علاقه‌مندی‌ها اضافه نکرده‌اید</p>
        </div>
      )}
    </div>
  )
}
