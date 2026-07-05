import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Plus, Search } from 'lucide-react'
import api from '@/lib/api'
import { formatNumber } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { PropertyCard, type PropertyItem } from '@/components/PropertyCard'

export function PropertiesPage() {
  const [search, setSearch] = useState('')
  const [type, setType] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['properties', search, type],
    queryFn: async () => {
      const params = new URLSearchParams()
      if (search) params.set('q', search)
      if (type) params.set('type', type)
      const res = await api.get(`/properties?${params}`)
      return res.data
    },
  })

  const propertyTypes = [
    { value: '', label: 'همه' },
    { value: 'sale', label: 'فروش' },
    { value: 'rent', label: 'اجاره' },
    { value: 'mortgage', label: 'رهن' },
    { value: 'pre_sale', label: 'پیش‌فروش' },
    { value: 'land', label: 'زمین' },
    { value: 'commercial', label: 'تجاری' },
  ]

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">املاک</h1>
          <p className="text-muted mt-1">
            {formatNumber(data?.meta?.total ?? 0)} ملک ثبت شده
          </p>
        </div>
        <Link to="/properties/new">
          <Button>
            <Plus className="h-4 w-4" />
            ثبت ملک
          </Button>
        </Link>
      </div>

      <div className="flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted" />
          <Input
            placeholder="جستجوی سریع..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pr-10"
          />
        </div>
        <div className="flex gap-2 overflow-x-auto pb-1">
          {propertyTypes.map((t) => (
            <Button
              key={t.value}
              variant={type === t.value ? 'default' : 'secondary'}
              size="sm"
              onClick={() => setType(t.value)}
            >
              {t.label}
            </Button>
          ))}
        </div>
      </div>

      {isLoading ? (
        <div className="flex justify-center py-20">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {data?.data?.map((property: PropertyItem) => (
            <PropertyCard key={property.id} property={property} />
          ))}
        </div>
      )}

      {!isLoading && !data?.data?.length && (
        <div className="text-center py-20 text-muted">ملکی یافت نشد</div>
      )}
    </div>
  )
}
