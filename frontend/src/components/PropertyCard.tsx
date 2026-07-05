import { Link } from 'react-router-dom'
import { MapPin, Bed, Maximize, Star } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { formatPrice, formatNumber } from '@/lib/utils'

export interface PropertyItem {
  id: number
  code: string
  type_label: string
  status_label?: string
  price?: number
  rent?: number
  area?: number
  rooms?: number
  city?: string
  district?: string
  permission_label?: string
  created_at_jalali?: string
  is_favorite?: boolean
}

export function PropertyCard({ property }: { property: PropertyItem }) {
  return (
    <Link to={`/properties/${property.id}`}>
      <Card className="!p-0 overflow-hidden glass-hover cursor-pointer h-full group">
        <div className="h-40 bg-gradient-to-br from-primary/25 via-accent/15 to-primary/10 flex items-center justify-center relative">
          <svg className="h-16 w-16 text-primary/40 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          {property.is_favorite && (
            <Star className="absolute top-3 left-3 h-5 w-5 text-warning fill-warning" />
          )}
        </div>
        <div className="p-4 space-y-3">
          <div className="flex items-center justify-between">
            <span className="font-bold text-lg">{property.code}</span>
            <Badge>{property.type_label}</Badge>
          </div>
          {(property.price || property.rent) && (
            <p className="text-primary font-semibold">
              {formatPrice(property.price || property.rent || 0)}
            </p>
          )}
          <div className="flex items-center gap-4 text-sm text-muted">
            {property.area && (
              <span className="flex items-center gap-1">
                <Maximize className="h-3.5 w-3.5" />
                {formatNumber(property.area)} متر
              </span>
            )}
            {property.rooms && (
              <span className="flex items-center gap-1">
                <Bed className="h-3.5 w-3.5" />
                {formatNumber(property.rooms)} خواب
              </span>
            )}
          </div>
          {property.city && (
            <p className="flex items-center gap-1 text-sm text-muted">
              <MapPin className="h-3.5 w-3.5" />
              {property.city}{property.district ? `، ${property.district}` : ''}
            </p>
          )}
          <div className="flex items-center justify-between pt-2 border-t border-card-border">
            {property.permission_label && <Badge variant="outline">{property.permission_label}</Badge>}
            {property.created_at_jalali && (
              <span className="text-xs text-muted">{property.created_at_jalali}</span>
            )}
          </div>
        </div>
      </Card>
    </Link>
  )
}
