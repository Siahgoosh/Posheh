import { useParams, Link } from 'react-router-dom'
import { useQuery, useMutation } from '@tanstack/react-query'
import { useRef, useState } from 'react'
import { ArrowRight, Trash2, Globe, BookOpen, Upload, Save } from 'lucide-react'
import api from '@/lib/api'
import { VirtualTourViewer } from '@/components/virtual-tour/VirtualTourViewer'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

export function VirtualTourEditorPage() {
  const { id } = useParams<{ id: string }>()
  const fileRef = useRef<HTMLInputElement>(null)
  const [sceneName, setSceneName] = useState('')
  const [phone, setPhone] = useState('')
  const [whatsapp, setWhatsapp] = useState('')
  const [uploading, setUploading] = useState(false)
  const [message, setMessage] = useState('')

  const { data: tour, refetch, isLoading } = useQuery({
    queryKey: ['virtual-tour', id],
    queryFn: async () => {
      const res = await api.get(`/virtual-tours/${id}`)
      const t = res.data.data
      setPhone(t.settings?.phone || '')
      setWhatsapp(t.settings?.whatsapp || '')
      return t
    },
    enabled: !!id,
  })

  const publishMutation = useMutation({
    mutationFn: async (status: string) => api.put(`/virtual-tours/${id}`, { status }),
    onSuccess: () => refetch(),
  })

  const saveSettings = async () => {
    await api.put(`/virtual-tours/${id}`, {
      settings: { phone, whatsapp },
    })
    setMessage('تنظیمات ذخیره شد')
    refetch()
  }

  const addSceneWithFile = async (file: File) => {
    if (!sceneName.trim()) {
      setMessage('ابتدا نام صحنه را وارد کنید')
      return
    }
    setUploading(true)
    setMessage('')
    try {
      const form = new FormData()
      form.append('name', sceneName.trim())
      form.append('panorama', file)
      await api.post(`/virtual-tours/${id}/scenes`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      setSceneName('')
      setMessage('صحنه با تصویر ۳۶۰ آپلود شد')
      refetch()
    } catch {
      setMessage('خطا در آپلود — فایل equirectangular با نسبت ۲:۱ (مثلاً ۴۰۹۶×۲۰۴۸) انتخاب کنید')
    } finally {
      setUploading(false)
    }
  }

  const replaceScenePanorama = async (sceneId: number, file: File) => {
    setUploading(true)
    try {
      const form = new FormData()
      form.append('_method', 'PUT')
      form.append('panorama', file)
      await api.post(`/virtual-tours/${id}/scenes/${sceneId}`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      setMessage('تصویر صحنه به‌روزرسانی شد')
      refetch()
    } catch {
      setMessage('خطا در به‌روزرسانی تصویر')
    } finally {
      setUploading(false)
    }
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
      <div className="flex flex-wrap items-center gap-3">
        <Link to="/virtual-tours"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
        <div className="flex-1 min-w-0">
          <h1 className="text-xl font-bold">{tour.title}</h1>
          <p className="text-xs text-muted">ویرایش تور مجازی ۳۶۰ درجه</p>
        </div>
        <Badge>{tour.status === 'published' ? 'منتشر شده' : 'پیش‌نویس'}</Badge>
        {tour.status !== 'published' ? (
          <Button onClick={() => publishMutation.mutate('published')}><Globe className="h-4 w-4" />انتشار</Button>
        ) : (
          <a href={`/tour/${tour.slug}`} target="_blank" rel="noreferrer">
            <Button variant="outline">مشاهده عمومی</Button>
          </a>
        )}
        <a href="/virtual-tour-guide.html" target="_blank" rel="noreferrer">
          <Button variant="ghost" size="sm"><BookOpen className="h-4 w-4" />راهنمای آپلود</Button>
        </a>
      </div>

      <Card className="p-4 bg-primary/5 border-primary/20 text-sm leading-relaxed">
        <p className="font-medium mb-1">چگونه عکس ۳۶۰ آپلود کنم؟</p>
        <ol className="list-decimal list-inside space-y-1 text-muted">
          <li>با دوربین ۳۶۰ یا اپ Insta360 / Google Street View عکس equirectangular بگیرید (نسبت ۲:۱)</li>
          <li>نام صحنه را بنویسید (مثلاً «پذیرایی»)</li>
          <li>دکمه «انتخاب و آپلود پانوراما» را بزنید و فایل JPG/PNG را انتخاب کنید</li>
          <li>برای جایگزینی تصویر، روی هر صحنه «تغییر تصویر» را بزنید</li>
        </ol>
      </Card>

      {message && <p className="text-sm text-primary">{message}</p>}

      <div className="grid lg:grid-cols-2 gap-6">
        <Card className="overflow-hidden" style={{ minHeight: 400 }}>
          {tour.scenes?.length > 0 ? (
            <VirtualTourViewer tour={tour} />
          ) : (
            <div className="flex items-center justify-center h-96 text-muted">صحنه‌ای با تصویر ۳۶۰ اضافه کنید</div>
          )}
        </Card>

        <div className="space-y-4">
          <Card className="p-4 space-y-3">
            <h2 className="font-semibold">افزودن صحنه جدید</h2>
            <Input placeholder="نام صحنه (مثلاً پذیرایی)" value={sceneName} onChange={(e) => setSceneName(e.target.value)} />
            <input
              ref={fileRef}
              type="file"
              accept="image/jpeg,image/png,image/webp"
              className="hidden"
              onChange={(e) => {
                const file = e.target.files?.[0]
                if (file) addSceneWithFile(file)
                e.target.value = ''
              }}
            />
            <Button className="w-full" disabled={uploading} onClick={() => fileRef.current?.click()}>
              <Upload className="h-4 w-4" />
              {uploading ? 'در حال آپلود…' : 'انتخاب و آپلود پانوراما ۳۶۰'}
            </Button>
          </Card>

          <Card className="p-4 space-y-3">
            <h2 className="font-semibold">صحنه‌های ثبت‌شده</h2>
            {tour.scenes?.length ? tour.scenes.map((s: { id: number; name: string }) => (
              <div key={s.id} className="flex items-center justify-between gap-2 p-2 rounded-lg bg-muted/10">
                <span>{s.name}</span>
                <div className="flex gap-1">
                  <label className="cursor-pointer inline-flex">
                    <input type="file" accept="image/*" className="hidden" onChange={(e) => {
                      const f = e.target.files?.[0]
                      if (f) replaceScenePanorama(s.id, f)
                      e.target.value = ''
                    }} />
                    <span className="inline-flex items-center justify-center rounded-lg border px-3 py-1.5 text-xs">تغییر تصویر</span>
                  </label>
                  <Button variant="ghost" size="icon" onClick={() => deleteScene(s.id)}><Trash2 className="h-4 w-4 text-danger" /></Button>
                </div>
              </div>
            )) : <p className="text-sm text-muted">هنوز صحنه‌ای ثبت نشده</p>}
          </Card>

          <Card className="p-4 space-y-3">
            <h2 className="font-semibold">تنظیمات تماس</h2>
            <Input placeholder="شماره تماس" value={phone} onChange={(e) => setPhone(e.target.value)} dir="ltr" />
            <Input placeholder="واتساپ" value={whatsapp} onChange={(e) => setWhatsapp(e.target.value)} dir="ltr" />
            <Button onClick={saveSettings}><Save className="h-4 w-4" />ذخیره تنظیمات</Button>
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
