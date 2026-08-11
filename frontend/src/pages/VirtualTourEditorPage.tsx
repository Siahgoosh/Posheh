import { useParams, Link } from 'react-router-dom'
import { useQuery, useMutation } from '@tanstack/react-query'
import { useEffect, useState } from 'react'
import { ArrowRight, Plus, Trash2, Globe, BookOpen } from 'lucide-react'
import api from '@/lib/api'
import { VirtualTourViewer } from '@/components/virtual-tour/VirtualTourViewer'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

export function VirtualTourEditorPage() {
  const { id } = useParams<{ id: string }>()
  const [phone, setPhone] = useState('')
  const [whatsapp, setWhatsapp] = useState('')
  const [actionError, setActionError] = useState('')

  const { data: tour, refetch, isLoading } = useQuery({
    queryKey: ['virtual-tour', id],
    queryFn: async () => (await api.get(`/virtual-tours/${id}`)).data.data,
    enabled: !!id,
  })

  useEffect(() => {
    if (!tour) return
    setPhone(tour.settings?.phone || '')
    setWhatsapp(tour.settings?.whatsapp || '')
  }, [tour?.id, tour?.settings?.phone, tour?.settings?.whatsapp])

  const publishMutation = useMutation({
    mutationFn: async (status: string) => api.put(`/virtual-tours/${id}`, { status }),
    onSuccess: () => {
      setActionError('')
      refetch()
    },
    onError: () => setActionError('انتشار تور ناموفق بود.'),
  })

  const settingsMutation = useMutation({
    mutationFn: async () => api.put(`/virtual-tours/${id}`, {
      settings: { phone, whatsapp },
    }),
    onSuccess: () => {
      setActionError('')
      refetch()
    },
    onError: () => setActionError('ذخیره تنظیمات ناموفق بود.'),
  })

  const addScene = async (file?: File | null) => {
    const name = prompt('نام صحنه (مثلاً پذیرایی):')
    if (!name) return
    setActionError('')
    try {
      const body = new FormData()
      body.append('name', name)
      if (file) {
        body.append('panorama', file)
      } else {
        body.append('panorama_path', 'demo/sphere.jpg')
      }
      await api.post(`/virtual-tours/${id}/scenes`, body, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      refetch()
    } catch {
      setActionError('افزودن صحنه ناموفق بود.')
    }
  }

  const deleteScene = async (sceneId: number) => {
    if (!confirm('حذف این صحنه؟')) return
    setActionError('')
    try {
      await api.delete(`/virtual-tours/${id}/scenes/${sceneId}`)
      refetch()
    } catch {
      setActionError('حذف صحنه ناموفق بود.')
    }
  }

  if (isLoading || !tour) {
    return <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
  }

  const canPublish = tour.status !== 'published' && (tour.scenes?.length ?? 0) > 0

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3 flex-wrap">
        <Link to="/virtual-tours"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
        <div className="flex-1">
          <h1 className="text-xl font-bold">{tour.title}</h1>
          <p className="text-xs text-muted">ویرایش تور مجازی ۳۶۰ درجه</p>
        </div>
        <Badge>{tour.status === 'published' ? 'منتشر شده' : 'پیش‌نویس'}</Badge>
        {tour.status !== 'published' ? (
          <Button
            onClick={() => publishMutation.mutate('published')}
            disabled={!canPublish || publishMutation.isPending}
            title={!canPublish ? 'ابتدا حداقل یک صحنه اضافه کنید' : undefined}
          >
            <Globe className="h-4 w-4" />انتشار
          </Button>
        ) : (
          <a href={`/tour/${tour.slug}`} target="_blank" rel="noreferrer">
            <Button variant="outline">مشاهده عمومی</Button>
          </a>
        )}
        <a href="/virtual-tour-guide.html" target="_blank" rel="noreferrer">
          <Button variant="ghost" size="sm"><BookOpen className="h-4 w-4" />راهنما</Button>
        </a>
      </div>

      {actionError && <p className="text-sm text-danger">{actionError}</p>}

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
            {tour.scenes?.map((s: { id: number; name: string }) => (
              <div key={s.id} className="flex items-center justify-between p-2 rounded-lg bg-muted/10">
                <span>{s.name}</span>
                <Button variant="ghost" size="icon" onClick={() => deleteScene(s.id)}><Trash2 className="h-4 w-4 text-danger" /></Button>
              </div>
            ))}
            <Button variant="outline" className="w-full" onClick={() => addScene()}>
              <Plus className="h-4 w-4" />افزودن صحنه نمونه
            </Button>
            <label className="block">
              <span className="sr-only">آپلود پانوراما</span>
              <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                className="block w-full text-sm text-muted file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-2 file:text-primary"
                onChange={(e) => {
                  const file = e.target.files?.[0]
                  e.target.value = ''
                  if (file) addScene(file)
                }}
              />
            </label>
          </Card>

          <Card className="p-4 space-y-3">
            <h2 className="font-semibold">تنظیمات</h2>
            <Input placeholder="شماره تماس" value={phone} onChange={(e) => setPhone(e.target.value)} />
            <Input placeholder="واتساپ" value={whatsapp} onChange={(e) => setWhatsapp(e.target.value)} />
            <Button
              variant="outline"
              className="w-full"
              onClick={() => settingsMutation.mutate()}
              disabled={settingsMutation.isPending}
            >
              ذخیره تنظیمات
            </Button>
            <p className="text-xs text-muted">برای آپلود پانورامای ۳۶۰ درجه واقعی، فایل equirectangular را انتخاب کنید.</p>
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
