import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Globe, Eye, Users, FileImage, Box, ArrowRight } from 'lucide-react'
import api from '@/lib/api'
import { publicTourUrl } from '@/lib/tourUrls'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { formatNumber } from '@/lib/utils'

interface TourStats {
  total_tours: number
  published_tours: number
  draft_tours: number
  total_views: number
  total_leads: number
  top_tours?: { id: number; title: string; slug: string; view_count: number }[]
}

export function AdminVirtualToursStatsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-virtual-tour-stats'],
    queryFn: async () => (await api.get('/admin/virtual-tour-stats')).data.data as TourStats,
  })

  if (isLoading || !data) {
    return <div className="p-8 text-center text-muted">بارگذاری آمار تور مجازی…</div>
  }

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <AdminPageHeader
          title="آمار تور مجازی پلتفرم"
          description="تورهای ۳۶۰ درجه، بازدید و سرنخ‌های جمع‌آوری‌شده"
        />
        <Link to="/virtual-tours">
          <Button size="sm" className="gap-2 shrink-0">
            <Box className="h-4 w-4" />
            مدیریت تورها و اسمارت‌واک
            <ArrowRight className="h-4 w-4" />
          </Button>
        </Link>
      </div>

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card><CardContent className="pt-6 flex gap-3"><FileImage className="h-8 w-8 text-primary" /><div><p className="text-sm text-muted">کل تورها</p><p className="text-2xl font-bold">{formatNumber(data.total_tours)}</p></div></CardContent></Card>
        <Card><CardContent className="pt-6 flex gap-3"><Globe className="h-8 w-8 text-success" /><div><p className="text-sm text-muted">منتشرشده</p><p className="text-2xl font-bold">{formatNumber(data.published_tours)}</p></div></CardContent></Card>
        <Card><CardContent className="pt-6 flex gap-3"><Eye className="h-8 w-8 text-accent" /><div><p className="text-sm text-muted">بازدید کل</p><p className="text-2xl font-bold">{formatNumber(data.total_views)}</p></div></CardContent></Card>
        <Card><CardContent className="pt-6 flex gap-3"><Users className="h-8 w-8 text-warning" /><div><p className="text-sm text-muted">سرنخ</p><p className="text-2xl font-bold">{formatNumber(data.total_leads)}</p></div></CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>پربازدیدترین تورها</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {(data.top_tours ?? []).length === 0 ? (
            <p className="text-sm text-muted">هنوز تور منتشرشده‌ای نیست.</p>
          ) : (
            data.top_tours?.map((tour) => (
              <div key={tour.id} className="flex justify-between text-sm border-b border-card-border pb-2">
                <a href={publicTourUrl(tour.slug)} target="_blank" rel="noreferrer" className="hover:text-primary">{tour.title}</a>
                <span className="text-muted">{formatNumber(tour.view_count)} بازدید</span>
              </div>
            ))
          )}
        </CardContent>
      </Card>

      <p className="text-xs text-muted">
        دموی پلتفرم:{' '}
        <a href={publicTourUrl('demo-apartment-pasdaran')} className="text-primary" target="_blank" rel="noreferrer">
          demo-apartment-pasdaran
        </a>
      </p>
    </div>
  )
}
