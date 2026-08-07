import { useParams, Link } from 'react-router-dom'
import { useQuery, useMutation } from '@tanstack/react-query'
import { ArrowRight, Plus, Trash2, Globe, BookOpen } from 'lucide-react'
import api from '@/lib/api'
import { publicTourUrl, virtualTourGuideUrl } from '@/lib/tourUrls'
import { VirtualTourViewer } from '@/components/virtual-tour/VirtualTourViewer'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

export function VirtualTourEditorPage() {
  const { id } = useParams<{ id: string }>()

  const { data: tour, refetch, isLoading } = useQuery({
    queryKey: ['virtual-tour', id],
    queryFn: async () => (await api.get(`/virtual-tours/${id}`)).data.data,
    enabled: !!id,
  })

  const publishMutation = useMutation({
    mutationFn: async (status: string) => api.put(`/virtual-tours/${id}`, { status }),
    onSuccess: () => refetch(),
  })

  const addScene = async () => {
    const name = prompt('نام صحنه (مثلاً پذیرایی):')
    if (!name) return
    await api.post(`/virtual-tours/${id}/scenes`, { name, panorama_path: 'demo/sphere.jpg' })
    refetch()
  }

  const deleteScene = async (sceneId: number) => {
    if (!confirm('حذف این صحنه؟')) return
    await api.delete(`/virtual-tours/${id}/scenes/${sceneId}`)
    refetch()
  }

  if (isLoading || !tour) {
    return <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Link to="/virtual-tours"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
        <div className="flex-1">
          <h1 className="text-xl font-bold">{tour.title}</h1>
          <p className="text-xs text-muted">ویرایش تور ۳۶۰ — اسمارت‌واک بین صحنه‌ها با لینک hotspot</p>
        </div>
        <Badge>{tour.status === 'published' ? 'منتشر شده' : 'پیش‌نویس'}</Badge>
        {tour.status !== 'published' ? (
          <Button onClick={() => publishMutation.mutate('published')}><Globe className="h-4 w-4" />انتشار</Button>
        ) : (
          <a href={publicTourUrl(tour.slug)} target="_blank" rel="noreferrer">
            <Button variant="outline">مشاهده عمومی</Button>
          </a>
        )}
        <a href={virtualTourGuideUrl()} target="_blank" rel="noreferrer">
          <Button variant="ghost" size="sm"><BookOpen className="h-4 w-4" />راهنما</Button>
        </a>
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        <Card className="overflow-hidden" style={{ minHeight: 400 }}>
          {tour.scenes?.length > 0 ? (
            <VirtualTourViewer tour={tour} />
          ) : (
            <div className="flex items-center justify-center h-96 text-muted">صحنه‌ای اضافه کنید</div>
          )}
        </Card>

        <div className="space-y-4">
          <Card className="p-4 space-y-3">
            <h2 className="font-semibold">صحنه‌ها</h2>
            {tour.scenes?.map((s: { id: number; name: string; hotspots?: { length?: number }[] }) => (
              <div key={s.id} className="flex items-center justify-between p-2 rounded-lg bg-muted/10">
                <div>
                  <span>{s.name}</span>
                  <p className="text-[10px] text-muted">
                    {(s.hotspots?.length ?? 0) > 0
                      ? `${s.hotspots?.length} لینک اسمارت‌واک`
                      : 'بدون لینک بین صحنه‌ها'}
                  </p>
                </div>
                <Button variant="ghost" size="icon" onClick={() => deleteScene(s.id)}><Trash2 className="h-4 w-4 text-danger" /></Button>
              </div>
            ))}
            <Button variant="outline" className="w-full" onClick={addScene}><Plus className="h-4 w-4" />افزودن صحنه</Button>
          </Card>

          <Card className="p-4 space-y-3">
            <h2 className="font-semibold">تنظیمات</h2>
            <Input placeholder="شماره تماس" defaultValue={tour.settings?.phone || ''} />
            <Input placeholder="واتساپ" defaultValue={tour.settings?.whatsapp || ''} />
            <p className="text-xs text-muted">برای آپلود پانورامای ۳۶۰ درجه واقعی، فایل equirectangular را از بخش صحنه‌ها آپلود کنید.</p>
          </Card>

          <Card className="p-4">
            <h2 className="font-semibold mb-2">آمار</h2>
            <p className="text-sm text-muted">بازدید: {tour.view_count} | لینک: /tour/{tour.slug}</p>
          </Card>
        </div>
      </div>
    </div>
  )
}
