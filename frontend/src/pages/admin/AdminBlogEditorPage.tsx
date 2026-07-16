import { useEffect, useMemo, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery } from '@tanstack/react-query'
import { ArrowRight, Plus, Save, Trash2 } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { RichTextEditor } from '@/components/admin/RichTextEditor'
import { SeoScorePanel, type SeoAnalysis } from '@/components/admin/SeoScorePanel'
import { ImageUploadField } from '@/components/admin/ImageUploadField'

interface FaqItem {
  question: string
  answer: string
}

interface CategoryOption {
  slug: string
  label: string
}

const emptyForm = {
  title: '',
  slug: '',
  excerpt: '',
  content: '',
  cover_image: '',
  meta_title: '',
  meta_description: '',
  keywords: '',
  category_slug: '',
  category_label: '',
  pillar_slug: '',
  faq: [] as FaqItem[],
  related_slugs: [] as string[],
  related_slugs_text: '',
  cta_text: 'شروع ۴۸ ساعت رایگان',
  cta_url: '/register',
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

  const { data: categories } = useQuery({
    queryKey: ['admin-blog-categories'],
    queryFn: async () => (await api.get('/admin/blog/categories')).data.data as CategoryOption[],
  })

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
      const related = post.related_slugs ?? []
      setForm({
        title: post.title ?? '',
        slug: post.slug ?? '',
        excerpt: post.excerpt ?? '',
        content: post.content ?? '',
        cover_image: post.cover_image ?? '',
        meta_title: post.meta_title ?? '',
        meta_description: post.meta_description ?? '',
        keywords: post.keywords ?? '',
        category_slug: post.category_slug ?? '',
        category_label: post.category_label ?? '',
        pillar_slug: post.pillar_slug ?? '',
        faq: post.faq ?? [],
        related_slugs: related,
        related_slugs_text: related.join(', '),
        cta_text: post.cta_text ?? 'شروع ۴۸ ساعت رایگان',
        cta_url: post.cta_url ?? '/register',
        author_name: post.author_name ?? 'تیم پوشه',
        reading_time: post.reading_time ?? 5,
        is_published: !!post.is_published,
      })
    }
  }, [post])

  const seoPayload = useMemo(() => {
    const { related_slugs_text: _, ...rest } = form
    return {
      ...rest,
      related_slugs: form.related_slugs_text
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean),
    }
  }, [form])

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
      const payload = {
        ...seoPayload,
        category_label: categories?.find((c) => c.slug === form.category_slug)?.label ?? form.category_label,
      }
      if (isNew) return api.post('/admin/blog', payload)
      return api.put(`/admin/blog/${id}`, payload)
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

  const update = <K extends keyof typeof form>(key: K, value: (typeof form)[K]) =>
    setForm((f) => ({ ...f, [key]: value }))

  const updateFaq = (index: number, field: keyof FaqItem, value: string) => {
    setForm((f) => {
      const faq = [...f.faq]
      faq[index] = { ...faq[index], [field]: value }
      return { ...f, faq }
    })
  }

  const addFaq = () => update('faq', [...form.faq, { question: '', answer: '' }])

  const removeFaq = (index: number) =>
    update('faq', form.faq.filter((_, i) => i !== index))

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
            <CardHeader><CardTitle>دسته‌بندی و خوشه محتوا</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              <div>
                <label className="text-sm text-muted mb-1 block">دسته وبلاگ</label>
                <select
                  className="w-full rounded-xl border border-card-border bg-background/50 p-3 text-sm"
                  value={form.category_slug}
                  onChange={(e) => update('category_slug', e.target.value)}
                >
                  <option value="">— انتخاب دسته —</option>
                  {categories?.map((c) => (
                    <option key={c.slug} value={c.slug}>{c.label}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">صفحه پیلار (slug انگلیسی)</label>
                <Input
                  value={form.pillar_slug}
                  onChange={(e) => update('pillar_slug', e.target.value)}
                  dir="ltr"
                  placeholder="pillar-crm"
                />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">مقالات مرتبط (slug با ویرگول)</label>
                <Input
                  value={form.related_slugs_text}
                  onChange={(e) => update('related_slugs_text', e.target.value)}
                  dir="ltr"
                  placeholder="property-filing-tips, real-estate-crm-guide"
                />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle>سوالات متداول (FAQ Schema)</CardTitle>
              <Button type="button" variant="outline" size="sm" onClick={addFaq}>
                <Plus className="h-4 w-4" />
                افزودن
              </Button>
            </CardHeader>
            <CardContent className="space-y-4">
              {form.faq.length === 0 && (
                <p className="text-sm text-muted">برای نمایش FAQ در گوگل، حداقل یک سوال اضافه کنید.</p>
              )}
              {form.faq.map((item, index) => (
                <div key={index} className="rounded-xl border border-card-border p-4 space-y-3">
                  <div className="flex justify-between items-center">
                    <span className="text-sm font-medium">سوال {index + 1}</span>
                    <Button type="button" variant="ghost" size="icon" onClick={() => removeFaq(index)}>
                      <Trash2 className="h-4 w-4 text-danger" />
                    </Button>
                  </div>
                  <Input
                    value={item.question}
                    onChange={(e) => updateFaq(index, 'question', e.target.value)}
                    placeholder="سوال"
                  />
                  <textarea
                    className="w-full min-h-[72px] rounded-xl border border-card-border bg-background/50 p-3 text-sm"
                    value={item.answer}
                    onChange={(e) => updateFaq(index, 'answer', e.target.value)}
                    placeholder="پاسخ"
                  />
                </div>
              ))}
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
              <div className="grid sm:grid-cols-2 gap-4">
                <div>
                  <label className="text-sm text-muted mb-1 block">متن CTA</label>
                  <Input value={form.cta_text} onChange={(e) => update('cta_text', e.target.value)} />
                </div>
                <div>
                  <label className="text-sm text-muted mb-1 block">لینک CTA</label>
                  <Input value={form.cta_url} onChange={(e) => update('cta_url', e.target.value)} dir="ltr" />
                </div>
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
