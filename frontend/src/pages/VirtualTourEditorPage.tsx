import { useRef, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { useQuery, useMutation } from '@tanstack/react-query'
import {
  ArrowRight, Plus, Trash2, Globe, BookOpen, Upload, Save, ImagePlus,
  MapPin, Link2, Info, Loader2,
} from 'lucide-react'
import api from '@/lib/api'
import { VirtualTourViewer, type TourHotspot } from '@/components/virtual-tour/VirtualTourViewer'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface SceneItem {
  id: number
  name: string
  panorama_url: string
  default_yaw?: number
  floor_plan_x?: number | null
  floor_plan_y?: number | null
  hotspots: TourHotspot[]
}

interface TourSettings {
  phone?: string
  whatsapp?: string
  brand_color?: string
  map_lat?: number
  map_lng?: number
  show_contact_form?: boolean
  show_gallery?: boolean
  show_floor_plan?: boolean
  enable_gyroscope?: boolean
}

interface TourPayload {
  id: number
  title: string
  slug: string
  status: string
  view_count: number
  settings?: TourSettings
  scenes: SceneItem[]
  gallery?: { id: number; url: string; title?: string }[]
}

const HOTSPOT_TYPES = [
  { value: 'scene', label: 'انتقال به صحنه', icon: MapPin },
  { value: 'info', label: 'اطلاعات', icon: Info },
  { value: 'link', label: 'لینک خارجی', icon: Link2 },
] as const

export function VirtualTourEditorPage() {
  const { id } = useParams<{ id: string }>()
  const fileInputRefs = useRef<Record<number, HTMLInputElement | null>>({})
  const galleryInputRef = useRef<HTMLInputElement>(null)

  const [selectedSceneId, setSelectedSceneId] = useState<number | null>(null)
  const [uploadingSceneId, setUploadingSceneId] = useState<number | null>(null)
  const [uploadingGallery, setUploadingGallery] = useState(false)
  const [settings, setSettings] = useState<TourSettings>({})
  const [settingsDirty, setSettingsDirty] = useState(false)
  const [hotspotDraft, setHotspotDraft] = useState({
    type: 'scene' as TourHotspot['type'],
    yaw: 0,
    pitch: 0,
    title: '',
    content: '',
    link_url: '',
    target_scene_id: null as number | null,
  })
  const [savingHotspots, setSavingHotspots] = useState(false)
  const [message, setMessage] = useState('')

  const { data: tour, refetch, isLoading } = useQuery({
    queryKey: ['virtual-tour', id],
    queryFn: async () => {
      const res = await api.get(`/virtual-tours/${id}`)
      const data = res.data.data as TourPayload
      if (!settingsDirty) {
        setSettings(data.settings || {})
      }
      if (!selectedSceneId && data.scenes?.length) {
        setSelectedSceneId(data.scenes[0].id)
      }
      return data
    },
    enabled: !!id,
  })

  const publishMutation = useMutation({
    mutationFn: async (status: string) => api.put(`/virtual-tours/${id}`, { status }),
    onSuccess: () => {
      setMessage('وضعیت تور به‌روز شد.')
      refetch()
    },
  })

  const saveSettings = async () => {
    await api.put(`/virtual-tours/${id}`, { settings })
    setSettingsDirty(false)
    setMessage('تنظیمات ذخیره شد.')
    refetch()
  }

  const addScene = async () => {
    const name = prompt('نام صحنه (مثلاً پذیرایی، آشپزخانه، اتاق خواب):')
    if (!name) return
    await api.post(`/virtual-tours/${id}/scenes`, { name, panorama_path: 'demo/sphere-small.jpg' })
    setMessage(`صحنه «${name}» اضافه شد. حالا پانورامای ۳۶۰ درجه را آپلود کنید.`)
    refetch()
  }

  const deleteScene = async (sceneId: number) => {
    if (!confirm('حذف این صحنه و هات‌اسپات‌های آن؟')) return
    await api.delete(`/virtual-tours/${id}/scenes/${sceneId}`)
    if (selectedSceneId === sceneId) setSelectedSceneId(null)
    refetch()
  }

  const uploadPanorama = async (sceneId: number, file: File) => {
    setUploadingSceneId(sceneId)
    setMessage('')
    try {
      const body = new FormData()
      body.append('panorama', file)
      await api.put(`/virtual-tours/${id}/scenes/${sceneId}`, body, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      setMessage('پانوراما با موفقیت آپلود شد.')
      refetch()
    } catch {
      setMessage('خطا در آپلود. فایل باید equirectangular با نسبت ۲:۱ باشد (JPG/PNG، حداکثر ۵۰ مگابایت).')
    } finally {
      setUploadingSceneId(null)
    }
  }

  const updateSceneMeta = async (sceneId: number, field: string, value: string | number) => {
    await api.put(`/virtual-tours/${id}/scenes/${sceneId}`, { [field]: value })
    refetch()
  }

  const saveHotspots = async (scene: SceneItem, hotspots: TourHotspot[]) => {
    setSavingHotspots(true)
    try {
      await api.put(`/virtual-tours/${id}/scenes/${scene.id}/hotspots`, {
        hotspots: hotspots.map((h) => ({
          type: h.type,
          yaw: h.yaw,
          pitch: h.pitch,
          title: h.title || null,
          content: h.content || null,
          link_url: h.link_url || null,
          target_scene_id: h.target_scene_id || null,
          icon: h.icon || (h.type === 'scene' ? 'arrow' : h.type === 'link' ? 'link' : 'info'),
        })),
      })
      setMessage('هات‌اسپات‌ها ذخیره شدند.')
      refetch()
    } finally {
      setSavingHotspots(false)
    }
  }

  const addHotspot = (scene: SceneItem) => {
    const newHotspot: TourHotspot = {
      id: Date.now(),
      type: hotspotDraft.type,
      yaw: hotspotDraft.yaw,
      pitch: hotspotDraft.pitch,
      title: hotspotDraft.title || undefined,
      content: hotspotDraft.content || undefined,
      link_url: hotspotDraft.link_url || undefined,
      target_scene_id: hotspotDraft.target_scene_id || undefined,
    }
    const updated = [...scene.hotspots, newHotspot]
    saveHotspots(scene, updated)
    setHotspotDraft({ type: 'scene', yaw: 0, pitch: 0, title: '', content: '', link_url: '', target_scene_id: null })
  }

  const removeHotspot = (scene: SceneItem, hotspotId: number) => {
    saveHotspots(scene, scene.hotspots.filter((h) => h.id !== hotspotId))
  }

  const uploadGallery = async (files: FileList | null) => {
    if (!files?.length) return
    setUploadingGallery(true)
    try {
      for (const file of Array.from(files)) {
        const body = new FormData()
        body.append('file', file)
        body.append('type', 'image')
        body.append('title', file.name.replace(/\.[^.]+$/, ''))
        await api.post(`/virtual-tours/${id}/media`, body, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
      }
      setMessage('تصاویر گالری آپلود شدند.')
      refetch()
    } catch {
      setMessage('خطا در آپلود گالری.')
    } finally {
      setUploadingGallery(false)
    }
  }

  if (isLoading || !tour) {
    return <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
  }

  const selectedScene = tour.scenes.find((s) => s.id === selectedSceneId) || tour.scenes[0]
  const otherScenes = tour.scenes.filter((s) => s.id !== selectedScene?.id)

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center gap-3">
        <Link to="/virtual-tours"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
        <div className="flex-1 min-w-[200px]">
          <h1 className="text-xl font-bold">{tour.title}</h1>
          <p className="text-xs text-muted">ویرایش تور مجازی ۳۶۰ درجه</p>
        </div>
        <Badge>{tour.status === 'published' ? 'منتشر شده' : 'پیش‌نویس'}</Badge>
        {tour.status !== 'published' ? (
          <Button onClick={() => publishMutation.mutate('published')} disabled={!tour.scenes?.length}>
            <Globe className="h-4 w-4" />انتشار
          </Button>
        ) : (
          <a href={`/tour/${tour.slug}`} target="_blank" rel="noreferrer">
            <Button variant="outline">مشاهده عمومی</Button>
          </a>
        )}
        <a href="/virtual-tour-guide.html" target="_blank" rel="noreferrer">
          <Button variant="ghost" size="sm"><BookOpen className="h-4 w-4" />راهنمای کامل</Button>
        </a>
      </div>

      {message && (
        <div className="text-sm px-4 py-2 rounded-lg bg-primary/10 text-primary border border-primary/20">{message}</div>
      )}

      <div className="grid lg:grid-cols-2 gap-6">
        <Card className="overflow-hidden" style={{ minHeight: 420 }}>
          {tour.scenes?.length > 0 ? (
            <VirtualTourViewer tour={tour} />
          ) : (
            <div className="flex flex-col items-center justify-center h-96 text-muted gap-3 p-6 text-center">
              <p>هنوز صحنه‌ای ندارید</p>
              <p className="text-xs">ابتدا صحنه بسازید، سپس پانورامای ۳۶۰ درجه هر اتاق را آپلود کنید</p>
              <Button onClick={addScene}><Plus className="h-4 w-4" />افزودن اولین صحنه</Button>
            </div>
          )}
        </Card>

        <div className="space-y-4">
          <Card className="p-4 space-y-3">
            <div className="flex items-center justify-between">
              <h2 className="font-semibold">صحنه‌ها ({tour.scenes?.length || 0})</h2>
              <Button variant="outline" size="sm" onClick={addScene}><Plus className="h-4 w-4" />صحنه جدید</Button>
            </div>

            {tour.scenes?.map((s) => (
              <div
                key={s.id}
                className={`p-3 rounded-xl border transition-colors ${
                  selectedScene?.id === s.id ? 'border-primary bg-primary/5' : 'border-card-border bg-muted/5'
                }`}
              >
                <div className="flex items-center gap-2 mb-2">
                  <button
                    type="button"
                    className="flex-1 text-right font-medium"
                    onClick={() => setSelectedSceneId(s.id)}
                  >
                    {s.name}
                    <span className="text-xs text-muted mr-2">({s.hotspots?.length || 0} هات‌اسپات)</span>
                  </button>
                  <Button variant="ghost" size="icon" onClick={() => deleteScene(s.id)}>
                    <Trash2 className="h-4 w-4 text-danger" />
                  </Button>
                </div>

                <div className="flex gap-2">
                  <input
                    ref={(el) => { fileInputRefs.current[s.id] = el }}
                    type="file"
                    accept="image/jpeg,image/png,image/jpg"
                    className="hidden"
                    onChange={(e) => {
                      const file = e.target.files?.[0]
                      if (file) uploadPanorama(s.id, file)
                      e.target.value = ''
                    }}
                  />
                  <Button
                    variant="outline"
                    size="sm"
                    className="flex-1"
                    disabled={uploadingSceneId === s.id}
                    onClick={() => fileInputRefs.current[s.id]?.click()}
                  >
                    {uploadingSceneId === s.id ? (
                      <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                      <Upload className="h-4 w-4" />
                    )}
                    آپلود پانوراما ۳۶۰
                  </Button>
                </div>
                <p className="text-[10px] text-muted mt-1.5">فرمت equirectangular — نسبت ۲:۱ — حداقل ۴۰۰۰×۲۰۰۰</p>
              </div>
            ))}
          </Card>

          {selectedScene && (
            <Card className="p-4 space-y-3">
              <h2 className="font-semibold">هات‌اسپات — {selectedScene.name}</h2>
              <p className="text-xs text-muted">
                در viewer بچرخید و زاویه yaw/pitch فعلی را یادداشت کنید، یا مقادیر تقریبی وارد کنید.
                هات‌اسپات «انتقال» برای رفتن بین اتاق‌ها است.
              </p>

              {selectedScene.hotspots?.map((h) => (
                <div key={h.id} className="flex items-center justify-between p-2 rounded-lg bg-muted/10 text-sm">
                  <span>
                    {h.type === 'scene' ? '🔗' : h.type === 'link' ? '🌐' : 'ℹ️'}{' '}
                    {h.title || h.type} — yaw:{h.yaw} pitch:{h.pitch}
                  </span>
                  <Button variant="ghost" size="icon" onClick={() => removeHotspot(selectedScene, h.id)}>
                    <Trash2 className="h-3 w-3 text-danger" />
                  </Button>
                </div>
              ))}

              <div className="grid grid-cols-2 gap-2">
                <select
                  className="col-span-2 rounded-lg border border-card-border bg-background px-3 py-2 text-sm"
                  value={hotspotDraft.type}
                  onChange={(e) => setHotspotDraft({ ...hotspotDraft, type: e.target.value as TourHotspot['type'] })}
                >
                  {HOTSPOT_TYPES.map((t) => (
                    <option key={t.value} value={t.value}>{t.label}</option>
                  ))}
                </select>
                <Input
                  type="number"
                  placeholder="Yaw (درجه)"
                  value={hotspotDraft.yaw}
                  onChange={(e) => setHotspotDraft({ ...hotspotDraft, yaw: Number(e.target.value) })}
                />
                <Input
                  type="number"
                  placeholder="Pitch (درجه)"
                  value={hotspotDraft.pitch}
                  onChange={(e) => setHotspotDraft({ ...hotspotDraft, pitch: Number(e.target.value) })}
                />
                <Input
                  className="col-span-2"
                  placeholder="عنوان (مثلاً رفتن به آشپزخانه)"
                  value={hotspotDraft.title}
                  onChange={(e) => setHotspotDraft({ ...hotspotDraft, title: e.target.value })}
                />
                {hotspotDraft.type === 'info' && (
                  <Input
                    className="col-span-2"
                    placeholder="متن توضیحات"
                    value={hotspotDraft.content}
                    onChange={(e) => setHotspotDraft({ ...hotspotDraft, content: e.target.value })}
                  />
                )}
                {hotspotDraft.type === 'link' && (
                  <Input
                    className="col-span-2"
                    placeholder="https://..."
                    value={hotspotDraft.link_url}
                    onChange={(e) => setHotspotDraft({ ...hotspotDraft, link_url: e.target.value })}
                  />
                )}
                {hotspotDraft.type === 'scene' && otherScenes.length > 0 && (
                  <select
                    className="col-span-2 rounded-lg border border-card-border bg-background px-3 py-2 text-sm"
                    value={hotspotDraft.target_scene_id ?? ''}
                    onChange={(e) => setHotspotDraft({ ...hotspotDraft, target_scene_id: Number(e.target.value) || null })}
                  >
                    <option value="">صحنه مقصد را انتخاب کنید</option>
                    {otherScenes.map((s) => (
                      <option key={s.id} value={s.id}>{s.name}</option>
                    ))}
                  </select>
                )}
              </div>
              <Button
                variant="outline"
                className="w-full"
                disabled={savingHotspots}
                onClick={() => addHotspot(selectedScene)}
              >
                {savingHotspots ? <Loader2 className="h-4 w-4 animate-spin" /> : <Plus className="h-4 w-4" />}
                افزودن هات‌اسپات
              </Button>

              <div className="grid grid-cols-2 gap-2 pt-2 border-t border-card-border">
                <Input
                  type="number"
                  placeholder="پلان X %"
                  defaultValue={selectedScene.floor_plan_x ?? ''}
                  onBlur={(e) => updateSceneMeta(selectedScene.id, 'floor_plan_x', Number(e.target.value))}
                />
                <Input
                  type="number"
                  placeholder="پلان Y %"
                  defaultValue={selectedScene.floor_plan_y ?? ''}
                  onBlur={(e) => updateSceneMeta(selectedScene.id, 'floor_plan_y', Number(e.target.value))}
                />
              </div>
            </Card>
          )}

          <Card className="p-4 space-y-3">
            <div className="flex items-center justify-between">
              <h2 className="font-semibold">تنظیمات تماس و نمایش</h2>
              <Button size="sm" onClick={saveSettings}><Save className="h-4 w-4" />ذخیره</Button>
            </div>
            <Input
              placeholder="شماره تماس"
              value={settings.phone || ''}
              onChange={(e) => { setSettings({ ...settings, phone: e.target.value }); setSettingsDirty(true) }}
            />
            <Input
              placeholder="واتساپ (با ۰۹...)"
              value={settings.whatsapp || ''}
              onChange={(e) => { setSettings({ ...settings, whatsapp: e.target.value }); setSettingsDirty(true) }}
            />
            <Input
              placeholder="رنگ برند (#6366f1)"
              value={settings.brand_color || ''}
              onChange={(e) => { setSettings({ ...settings, brand_color: e.target.value }); setSettingsDirty(true) }}
            />
            <div className="grid grid-cols-2 gap-2">
              <Input
                placeholder="عرض جغرافیایی"
                value={settings.map_lat ?? ''}
                onChange={(e) => { setSettings({ ...settings, map_lat: Number(e.target.value) || undefined }); setSettingsDirty(true) }}
              />
              <Input
                placeholder="طول جغرافیایی"
                value={settings.map_lng ?? ''}
                onChange={(e) => { setSettings({ ...settings, map_lng: Number(e.target.value) || undefined }); setSettingsDirty(true) }}
              />
            </div>
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={settings.show_contact_form !== false}
                onChange={(e) => { setSettings({ ...settings, show_contact_form: e.target.checked }); setSettingsDirty(true) }}
              />
              نمایش فرم درخواست بازدید
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={settings.show_gallery !== false}
                onChange={(e) => { setSettings({ ...settings, show_gallery: e.target.checked }); setSettingsDirty(true) }}
              />
              نمایش گالری تصاویر
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={settings.show_floor_plan !== false}
                onChange={(e) => { setSettings({ ...settings, show_floor_plan: e.target.checked }); setSettingsDirty(true) }}
              />
              نمایش پلان طبقه
            </label>
          </Card>

          <Card className="p-4 space-y-3">
            <div className="flex items-center justify-between">
              <h2 className="font-semibold">گالری تکمیلی</h2>
              <Button
                variant="outline"
                size="sm"
                disabled={uploadingGallery}
                onClick={() => galleryInputRef.current?.click()}
              >
                {uploadingGallery ? <Loader2 className="h-4 w-4 animate-spin" /> : <ImagePlus className="h-4 w-4" />}
                افزودن تصویر
              </Button>
              <input
                ref={galleryInputRef}
                type="file"
                accept="image/*"
                multiple
                className="hidden"
                onChange={(e) => uploadGallery(e.target.files)}
              />
            </div>
            {tour.gallery?.length ? (
              <div className="grid grid-cols-3 gap-2">
                {tour.gallery.map((g) => (
                  <img key={g.id} src={g.url} alt={g.title} className="rounded-lg aspect-square object-cover" />
                ))}
              </div>
            ) : (
              <p className="text-xs text-muted">تصاویر معمولی ملک (غیر ۳۶۰) برای نمایش در گالری تور</p>
            )}
          </Card>

          <Card className="p-4">
            <h2 className="font-semibold mb-2">آمار و لینک</h2>
            <p className="text-sm text-muted">بازدید: {tour.view_count}</p>
            <p className="text-sm text-muted mt-1">لینک عمومی: <code dir="ltr">/tour/{tour.slug}</code></p>
            <a href="/tour/demo-apartment-pasdaran" target="_blank" rel="noreferrer" className="text-xs text-primary mt-2 inline-block">
              مشاهده تور نمونه کامل →
            </a>
          </Card>
        </div>
      </div>
    </div>
  )
}
