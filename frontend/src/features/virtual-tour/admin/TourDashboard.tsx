import { useState, useRef } from 'react'
import { Link } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import {
  Plus, Eye, Users, ExternalLink, Box, BookOpen, Archive, Copy,
  Upload, Download, Search, LayoutDashboard, Globe, FileJson, Footprints,
} from 'lucide-react'
import { tourApi, downloadBlob, type TourDashboardStats, type TourListItem } from '@/features/virtual-tour/api/tourApi'
import { CreateTourWizard } from '@/features/virtual-tour/admin/CreateTourWizard'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'

type FilterStatus = 'all' | 'published' | 'draft' | 'archived'

const STATUS_LABELS: Record<string, string> = {
  published: 'منتشر شده',
  draft: 'پیش‌نویس',
  archived: 'بایگانی',
}

export function TourDashboard() {
  const queryClient = useQueryClient()
  const [filter, setFilter] = useState<FilterStatus>('all')
  const [search, setSearch] = useState('')
  const [wizardOpen, setWizardOpen] = useState(false)
  const importRef = useRef<HTMLInputElement>(null)

  const { data: stats } = useQuery({
    queryKey: ['virtual-tours-dashboard'],
    queryFn: async () => (await tourApi.dashboard()).data.data as TourDashboardStats,
  })

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['virtual-tours', filter, search],
    queryFn: async () => (await tourApi.list({ status: filter === 'all' ? undefined : filter, search: search || undefined })).data.data,
  })

  const tours: TourListItem[] = data?.data || []

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['virtual-tours'] })
    queryClient.invalidateQueries({ queryKey: ['virtual-tours-dashboard'] })
    refetch()
  }

  const createTour = () => setWizardOpen(true)

  const action = (fn: (id: number) => Promise<unknown>) => async (id: number) => {
    await fn(id)
    invalidate()
  }

  const handleImport = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return
    await tourApi.importTour(file)
    invalidate()
    e.target.value = ''
  }

  const statCards = [
    { label: 'کل تورها', value: stats?.total ?? 0, icon: Box },
    { label: 'منتشر شده', value: stats?.published ?? 0, icon: Globe },
    { label: 'پیش‌نویس', value: stats?.draft ?? 0, icon: FileJson },
    { label: 'بازدید کل', value: stats?.total_views ?? 0, icon: Eye },
    { label: 'سرنخ‌ها', value: stats?.total_leads ?? 0, icon: Users },
    { label: 'بایگانی', value: stats?.archived ?? 0, icon: Archive },
  ]

  return (
    <div className="min-w-0 w-full space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <LayoutDashboard className="h-6 w-6 text-primary" />
            تور مجازی — Smart Walk & 360
          </h1>
          <p className="text-muted text-sm mt-1">دو سیستم مستقل: Smart Walk (عکس موبایل) و تور ۳۶۰ درجه</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <input ref={importRef} type="file" accept=".json" className="hidden" onChange={handleImport} />
          <Button variant="outline" onClick={() => importRef.current?.click()}><Upload className="h-4 w-4" />Import</Button>
          <a href="/virtual-tour-guide.html" target="_blank" rel="noreferrer">
            <Button variant="outline"><BookOpen className="h-4 w-4" />راهنما</Button>
          </a>
          <Button onClick={createTour}><Plus className="h-4 w-4" />تور جدید</Button>
        </div>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
        {statCards.map(({ label, value, icon: Icon }) => (
          <Card key={label} className="p-4 glass-hover">
            <div className="flex items-center gap-2 text-muted mb-1">
              <Icon className="h-4 w-4" />
              <span className="text-[11px]">{label}</span>
            </div>
            <p className="text-2xl font-bold">{value.toLocaleString('fa-IR')}</p>
          </Card>
        ))}
      </div>

      <div className="flex flex-wrap gap-3 items-center">
        <div className="relative flex-1 min-w-[200px] max-w-sm">
          <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted" />
          <Input placeholder="جستجو..." value={search} onChange={(e) => setSearch(e.target.value)} className="pr-9" />
        </div>
        <div className="flex gap-1.5">
          {(['all', 'published', 'draft', 'archived'] as FilterStatus[]).map((s) => (
            <button
              key={s}
              type="button"
              onClick={() => setFilter(s)}
              className={`px-3 py-1.5 rounded-lg text-xs border transition-all ${
                filter === s ? 'bg-primary/20 border-primary/40 text-primary' : 'bg-black/20 border-card-border/50 text-muted'
              }`}
            >
              {s === 'all' ? 'همه' : STATUS_LABELS[s]}
            </button>
          ))}
        </div>
      </div>

      {isLoading ? (
        <div className="flex justify-center py-20">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {tours.map((t) => (
            <Card key={t.id} className="p-5 glass-hover group relative overflow-hidden min-w-0">
              <div className="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />
              <div className="flex items-start justify-between gap-2 mb-3 relative min-w-0">
                <div className="min-w-0 flex-1">
                  <h2 className="font-semibold truncate">{t.title}</h2>
                  <p className="text-[10px] text-muted font-mono mt-0.5 truncate">v{t.version ?? 1} · /tour/{t.slug}</p>
                </div>
                <div className="flex flex-wrap gap-1 shrink-0 justify-end max-w-[45%]">
                  <Badge variant={t.status === 'published' ? 'default' : 'outline'}>
                    {STATUS_LABELS[t.status] || t.status}
                  </Badge>
                  {t.tour_type === 'smart_walk' && (
                    <Badge variant="outline" className="text-[9px] gap-1">
                      <Footprints className="h-3 w-3" />Smart Walk
                    </Badge>
                  )}
                </div>
              </div>

              {t.scenes?.[0]?.thumbnail_url && (
                <img src={t.scenes[0].thumbnail_url} alt="" className="w-full h-24 object-cover rounded-lg mb-3 border border-white/10" loading="lazy" />
              )}

              <div className="flex gap-4 text-xs text-muted mb-4">
                <span className="flex items-center gap-1"><Eye className="h-3 w-3" />{t.view_count || 0}</span>
                <span className="flex items-center gap-1"><Users className="h-3 w-3" />{t.leads_count || 0}</span>
                <span>{t.scenes_count ?? t.scenes?.length ?? 0} صحنه</span>
                {t.visibility === 'private' && <Badge variant="outline" className="text-[9px]">خصوصی</Badge>}
              </div>

              <div className="flex flex-wrap gap-1.5">
                <Link to={`/virtual-tours/${t.id}/edit`} className="flex-1 min-w-[80px]">
                  <Button variant="outline" size="sm" className="w-full">ویرایش</Button>
                </Link>
                {t.status === 'published' ? (
                  <a href={`/tour/${t.slug}`} target="_blank" rel="noreferrer">
                    <Button size="sm" variant="ghost"><ExternalLink className="h-4 w-4" /></Button>
                  </a>
                ) : (
                  <Button size="sm" variant="ghost" onClick={() => action(tourApi.publish)(t.id)} title="انتشار"><Globe className="h-4 w-4" /></Button>
                )}
                <Button size="sm" variant="ghost" onClick={() => action(tourApi.duplicate)(t.id)} title="کپی"><Copy className="h-4 w-4" /></Button>
                <Button size="sm" variant="ghost" onClick={async () => {
                  const res = await tourApi.exportZip(t.id)
                  downloadBlob(res.data, `tour-${t.slug}.zip`)
                }} title="Export ZIP"><Download className="h-4 w-4" /></Button>
                {t.status !== 'archived' ? (
                  <Button size="sm" variant="ghost" onClick={() => action(tourApi.archive)(t.id)} title="بایگانی"><Archive className="h-4 w-4" /></Button>
                ) : (
                  <Button size="sm" variant="ghost" onClick={() => action(tourApi.unarchive)(t.id)} title="بازیابی"><Archive className="h-4 w-4" /></Button>
                )}
              </div>
            </Card>
          ))}
          {!tours.length && (
            <Card className="p-12 col-span-full text-center text-muted">
              <Box className="h-12 w-12 mx-auto mb-4 opacity-30" />
              <p>توری یافت نشد</p>
              <Button className="mt-4" onClick={createTour}>ساخت اولین تور</Button>
            </Card>
          )}
        </div>
      )}
      <CreateTourWizard open={wizardOpen} onClose={() => setWizardOpen(false)} onCreated={invalidate} />
    </div>
  )
}
