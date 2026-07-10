import { useEffect, useMemo, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery } from '@tanstack/react-query'
import { ArrowRight, Save } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { RichTextEditor } from '@/components/admin/RichTextEditor'
import { SeoScorePanel, type SeoAnalysis } from '@/components/admin/SeoScorePanel'
import { ImageUploadField } from '@/components/admin/ImageUploadField'

const emptyForm = {
  title: '',
  slug: '',
  excerpt: '',
  content: '',
  cover_image: '',
  meta_title: '',
  meta_description: '',
  keywords: '',
  author_name: 'تیم پوشه',
  reading_time: 5,
  is_published: false,
}

export function AdminBlogEditorPage() {
  const { id } = useParams<{ id: string }>()
  const isNew = !id || id === 'new'
  const navigate = useNavigate()
  const [form, setForm] = useState(emptyForm)
  const [seo, setSeo] = useState<SeoAnalysis | null>(null)
  const [seoLoading, setSeoLoading] = useState(false)
  const [error, setError] = useState('')

  const { data: post, isLoading } = useQuery({
    queryKey: ['admin-blog', id],
    queryFn: async () => {
      const res = await api.get(`/admin/blog/${id}`)
      return res.data.data as typeof emptyForm & { id: number }
    },
    enabled: !isNew,
  })

  useEffect(() => {
    if (post) {
      setForm({
        title: post.title ?? '',
        slug: post.slug ?? '',
        excerpt: post.excerpt ?? '',
        content: post.content ?? '',
        cover_image: post.cover_image ?? '',
        meta_title: post.meta_title ?? '',
        meta_description: post.meta_description ?? '',
        keywords: post.keywords ?? '',
        author_name: post.author_name ?? 'تیم پوشه',
        reading_time: post.reading_time ?? 5,
        is_published: !!post.is_published,
      })
    }
  }, [post])

  const seoPayload = useMemo(() => form, [form])

  useEffect(() => {
    const timer = setTimeout(async () => {
      if (!seoPayload.title && !seoPayload.content) return
      setSeoLoading(true)
      try {
        const res = await api.post('/admin/blog/analyze-seo', seoPayload)
        setSeo(res.data.data as SeoAnalysis)
      } catch {
        setSeo(null)
      } finally {
        setSeoLoading(false)
      }
    }, 600)
    return () => clearTimeout(timer)
  }, [seoPayload])

  const saveMutation = useMutation({
    mutationFn: async () => {
      if (isNew) return api.post('/admin/blog', form)
      return api.put(`/admin/blog/${id}`, form)
    },
    onSuccess: (res) => {
      setSeo(res.data.seo as SeoAnalysis)
      if (isNew) navigate(`/admin/blog/${res.data.data.id}/edit`, { replace: true })
    },
    onError: (err: unknown) => {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      setError(
        Object.values(axiosErr.response?.data?.errors ?? {}).flat().join('، ')
          || axiosErr.response?.data?.message
          || 'خطا در ذخیره'
      )
    },
  })

  const uploadImage = async (file: File, alt: string) => {
    const body = new FormData()
    body.append('image', file)
    if (alt) body.append('alt', alt)
    const res = await api.post('/admin/blog/upload-image', body, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return res.data.data.html as string
  }

  const uploadCover = async (file: File) => {
    const body = new FormData()
    body.append('image', file)
    const res = await api.post('/admin/blog/upload-cover', body, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return res.data.data.url as string
  }

  const update = (key: keyof typeof form, value: string | number | boolean) =>
    setForm((f) => ({ ...f, [key]: value }))

  if (!isNew && isLoading) {
    return <div className="p-8 text-center text-muted">در حال بارگذاری…</div>
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-6xl mx-auto">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Link to="/admin/blog">
            <Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button>
          </Link>
          <h1 className="text-2xl font-bold">{isNew ? 'مقاله جدید' : 'ویرایش مقاله'}</h1>
        </div>
        <Button onClick={() => saveMutation.mutate()} disabled={saveMutation.isPending}>
          <Save className="h-4 w-4" />
          {saveMutation.isPending ? 'ذخیره…' : 'ذخیره'}
        </Button>
      </div>

      {error && <p className="text-sm text-danger">{error}</p>}

      <div className="grid lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader><CardTitle>محتوا</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              <div>
                <label className="text-sm text-muted mb-1 block">عنوان *</label>
                <Input value={form.title} onChange={(e) => update('title', e.target.value)} />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">نامک URL (انگلیسی)</label>
                <Input value={form.slug} onChange={(e) => update('slug', e.target.value)} dir="ltr" placeholder="my-seo-post" />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">خلاصه</label>
                <textarea
                  className="w-full min-h-[80px] rounded-xl border border-card-border bg-background/50 p-3 text-sm"
                  value={form.excerpt}
                  onChange={(e) => update('excerpt', e.target.value)}
                />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">متن مقاله *</label>
                <RichTextEditor
                  editorKey={id ?? 'new'}
                  value={form.content}
                  onChange={(html) => update('content', html)}
                  onUploadImage={uploadImage}
                  placeholder="متن مقاله را بنویسید…"
                />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle>سئو و انتشار</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              <div>
                <label className="text-sm text-muted mb-1 block">عنوان سئو (Title Tag)</label>
                <Input value={form.meta_title} onChange={(e) => update('meta_title', e.target.value)} />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">توضیحات متا</label>
                <textarea
                  className="w-full min-h-[80px] rounded-xl border border-card-border bg-background/50 p-3 text-sm"
                  value={form.meta_description}
                  onChange={(e) => update('meta_description', e.target.value)}
                />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">کلمات کلیدی (با ویرگول)</label>
                <Input value={form.keywords} onChange={(e) => update('keywords', e.target.value)} />
              </div>
              <ImageUploadField
                label="تصویر شاخص"
                value={form.cover_image}
                onChange={(url) => update('cover_image', url)}
                onUpload={uploadCover}
                hint="تصویر از سیستم انتخاب و روی سرور ذخیره می‌شود."
              />
              <label className="flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={form.is_published}
                  onChange={(e) => update('is_published', e.target.checked)}
                />
                انتشار عمومی در وبلاگ
              </label>
            </CardContent>
          </Card>
        </div>

        <div>
          <SeoScorePanel analysis={seo} loading={seoLoading} />
        </div>
      </div>
    </div>
  )
}
