import { Link, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowRight } from 'lucide-react'
import { tourApi } from '@/features/virtual-tour/api/tourApi'
import { UnifiedTourViewer } from '@/features/virtual-tour/engine/UnifiedTourViewer'
import { Button } from '@/components/ui/button'
import type { TourData } from '@/features/virtual-tour/types'

export function VirtualTourPreviewPage() {
  const { id } = useParams<{ id: string }>()

  const { data: tour, isLoading, error } = useQuery({
    queryKey: ['virtual-tour-preview', id],
    queryFn: async () => (await tourApi.get(id!)).data.data as TourData,
    enabled: !!id,
  })

  if (!id) return null

  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-[60vh]">
        <div className="h-10 w-10 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  if (error || !tour) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-3 p-6 text-center">
        <p className="text-danger">بارگذاری پیش‌نمایش ناموفق بود.</p>
        <Link to={`/virtual-tours/${id}/edit`}>
          <Button variant="outline">بازگشت به ویرایشگر</Button>
        </Link>
      </div>
    )
  }

  return (
    <div className="flex flex-col h-[calc(100dvh-4rem)] min-h-0">
      <div className="flex items-center gap-3 px-4 py-3 border-b border-card-border/50 bg-black/20">
        <Link to={`/virtual-tours/${id}/edit`}>
          <Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button>
        </Link>
        <div className="flex-1 min-w-0">
          <h1 className="text-lg font-bold truncate">پیش‌نمایش: {tour.title}</h1>
          <p className="text-[11px] text-muted">حالت مشاهده — بدون ویرایش</p>
        </div>
      </div>
      <div className="flex-1 min-h-0 bg-black">
        <UnifiedTourViewer tour={tour} className="h-full min-h-0" showControls showSceneName />
      </div>
    </div>
  )
}
