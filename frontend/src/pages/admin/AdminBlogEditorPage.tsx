import { useEffect, useMemo, useRef, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  ArrowRight, Plus, Save, Trash2, Eye, CheckCircle2, Clock, Send,
  Sparkles, RotateCcw, AlertTriangle,
} from 'lucide-react'
import api from '@/lib/api'
import { adminPath } from '@/lib/adminPaths'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { RichTextEditor } from '@/components/admin/RichTextEditor'
import { SeoScorePanel, type SeoAnalysis } from '@/components/admin/SeoScorePanel'
import { ImageUploadField } from '@/components/admin/ImageUploadField'
import {
  CONTENT_TYPE_FA,
  DEVICE_FA,
  FUNNEL_STAGE_FA,
  REVIEW_STATUS_FA,
  SEARCH_INTENT_FA,
  labelFa,
} from '@/lib/blogLabelsFa'
import { extractApiError } from '@/lib/apiError'

interface FaqItem { question: string; answer: string }
interface SourceItem { title: string; url: string; publisher?: string; published_date?: string; access_date?: string }
interface CategoryOption { slug: string; label: string; id?: number }
interface VersionRow { id: number; version: number; note?: string; created_by?: string; created_at?: string }
interface ChecklistResult {
  passed: boolean
  blocking: string[]
  checks: { id: string; label: string; ok: boolean; blocking: boolean; message: string }[]
  note?: string
}

const INTENTS = ['informational', 'commercial', 'transactional', 'navigational', 'local', 'mixed'] as const
const CONTENT_TYPES = ['guide', 'news', 'analysis', 'list', 'how_to', 'comparison', 'faq', 'case_study', 'local', 'product_led'] as const

const emptyForm = {
  title: '',
  slug: '',
  excerpt: '',
  content: '',
  cover_image: '',
  meta_title: '',
  meta_description: '',
  keywords: '',
  focus_keyword: '',
  category_slug: '',
  category_label: '',
  pillar_slug: '',
  faq: [] as FaqItem[],
  related_slugs: [] as string[],
  related_slugs_text: '',
  sources: [] as SourceItem[],
  cta_text: 'آشنایی با پوشه',
  cta_url: '/register',
  cro_cta_key: '',
  author_name: 'تیم پوشه',
  reading_time: 5,
  word_count: 0,
  is_published: false,
  search_intent: 'informational',
  content_type: 'guide',
  schema_type: 'Article',
  funnel_stage: 'awareness',
  canonical_url: '',
  robots_directive: 'index,follow',
  og_title: '',
  og_description: '',
  og_image: '',
  scheduled_at: '',
  review_status: 'draft',
}

type FormState = typeof emptyForm

const LOCAL_DRAFT_KEY = (id: string) => `posheh-blog-draft-${id}`

function wordCount(html: string) {
  const plain = html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
  if (!plain) return 0
  return plain.split(/\s+/).length
}

export function AdminBlogEditorPage() {
  const { id } = useParams<{ id: string }>()
  const isNew = !id || id === 'new'
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [form, setForm] = useState<FormState>(emptyForm)
  const [seo, setSeo] = useState<SeoAnalysis | null>(null)
  const [seoLoading, setSeoLoading] = useState(false)
  const [checklist, setChecklist] = useState<ChecklistResult | null>(null)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')
  const [aiBusy, setAiBusy] = useState(false)
  const [aiResult, setAiResult] = useState<unknown>(null)
  const [cannibal, setCannibal] = useState<{ risk?: string; matches?: unknown[]; recommendation?: string } | null>(null)
  const [linkSuggestions, setLinkSuggestions] = useState<{ slug: string; title: string; reason?: string; suggested_anchor?: string }[]>([])
  const [versions, setVersions] = useState<VersionRow[]>([])
  const [recoverBanner, setRecoverBanner] = useState(false)
  const [previewDevice, setPreviewDevice] = useState<'desktop' | 'tablet' | 'mobile'>('desktop')
  const [previewUrl, setPreviewUrl] = useState('')
  const [scheduleAt, setScheduleAt] = useState('')
  const autosaveTimer = useRef<ReturnType<typeof setTimeout> | null>(null)
  const lastLocalSave = useRef('')

  const { data: categories } = useQuery({
    queryKey: ['admin-blog-categories'],
    queryFn: async () => (await api.get('/admin/blog/categories')).data.data as CategoryOption[],
  })

  const { data: postBundle, isLoading } = useQuery({
    queryKey: ['admin-blog', id],
    queryFn: async () => {
      const res = await api.get(`/admin/blog/${id}`)
      return res.data as {
        data: FormState & { id: number; has_autosave?: boolean; autosave_payload?: { data?: Partial<FormState>; saved_at?: string } }
        checklist?: ChecklistResult
        versions?: VersionRow[]
      }
    },
    enabled: !isNew,
  })

  useEffect(() => {
    if (!postBundle?.data) return
    const post = postBundle.data
    const related = post.related_slugs ?? []
    setForm({
      ...emptyForm,
      title: post.title ?? '',
      slug: post.slug ?? '',
      excerpt: post.excerpt ?? '',
      content: post.content ?? '',
      cover_image: post.cover_image ?? '',
      meta_title: post.meta_title ?? '',
      meta_description: post.meta_description ?? '',
      keywords: post.keywords ?? '',
      focus_keyword: (post as { focus_keyword?: string }).focus_keyword ?? '',
      category_slug: post.category_slug ?? '',
      category_label: post.category_label ?? '',
      pillar_slug: post.pillar_slug ?? '',
      faq: post.faq ?? [],
      related_slugs: related,
      related_slugs_text: related.join(', '),
      sources: (post as { sources?: SourceItem[] }).sources ?? [],
      cta_text: post.cta_text ?? 'آشنایی با پوشه',
      cta_url: post.cta_url ?? '/register',
      cro_cta_key: (post as { cro_cta_key?: string }).cro_cta_key ?? '',
      author_name: post.author_name ?? 'تیم پوشه',
      reading_time: post.reading_time ?? 5,
      word_count: (post as { word_count?: number }).word_count ?? wordCount(post.content ?? ''),
      is_published: !!post.is_published,
      search_intent: post.search_intent || 'informational',
      content_type: (post as { content_type?: string }).content_type || 'guide',
      schema_type: (post as { schema_type?: string }).schema_type || 'Article',
      funnel_stage: (post as { funnel_stage?: string }).funnel_stage || 'awareness',
      canonical_url: post.canonical_url ?? '',
      robots_directive: post.robots_directive || 'index,follow',
      og_title: post.og_title ?? '',
      og_description: post.og_description ?? '',
      og_image: post.og_image ?? '',
      scheduled_at: (post as { scheduled_at?: string }).scheduled_at?.slice(0, 16) ?? '',
      review_status: post.review_status || 'draft',
    })
    setVersions(postBundle.versions ?? [])
    setChecklist(postBundle.checklist ?? null)
    if (post.has_autosave || post.autosave_payload?.data) setRecoverBanner(true)
    api.post(`/admin/blog/${id}/lock`).catch(() => undefined)
    return () => {
      api.delete(`/admin/blog/${id}/lock`).catch(() => undefined)
    }
  }, [postBundle, id])

  // Local draft recovery for new articles
  useEffect(() => {
    if (!isNew) return
    try {
      const raw = localStorage.getItem(LOCAL_DRAFT_KEY('new'))
      if (raw) {
        const parsed = JSON.parse(raw) as FormState
        if (parsed?.title || parsed?.content) {
          setForm((f) => ({ ...f, ...parsed }))
          setRecoverBanner(true)
        }
      }
    } catch { /* ignore */ }
  }, [isNew])

  const seoPayload = useMemo(() => {
    const { related_slugs_text: _, ...rest } = form
    return {
      ...rest,
      related_slugs: form.related_slugs_text.split(',').map((s) => s.trim()).filter(Boolean),
      word_count: wordCount(form.content),
    }
  }, [form])

  useEffect(() => {
    const timer = setTimeout(async () => {
      if (!seoPayload.title && !seoPayload.content) return
      setSeoLoading(true)
      try {
        const [seoRes, checkRes] = await Promise.all([
          api.post('/admin/blog/analyze-seo', seoPayload),
          api.post('/admin/blog/publish-checklist', seoPayload),
        ])
        setSeo(seoRes.data.data as SeoAnalysis)
        setChecklist(checkRes.data.data as ChecklistResult)
      } catch {
        setSeo(null)
      } finally {
        setSeoLoading(false)
      }
    }, 700)
    return () => clearTimeout(timer)
  }, [seoPayload])

  // Autosave: localStorage + server
  useEffect(() => {
    if (autosaveTimer.current) clearTimeout(autosaveTimer.current)
    autosaveTimer.current = setTimeout(() => {
      const key = LOCAL_DRAFT_KEY(isNew ? 'new' : String(id))
      const serialized = JSON.stringify(seoPayload)
      if (serialized === lastLocalSave.current) return
      lastLocalSave.current = serialized
      try { localStorage.setItem(key, serialized) } catch { /* ignore */ }
      if (!isNew && id) {
        api.post(`/admin/blog/${id}/autosave`, { payload: seoPayload }).catch(() => undefined)
      }
    }, 4000)
    return () => {
      if (autosaveTimer.current) clearTimeout(autosaveTimer.current)
    }
  }, [seoPayload, isNew, id])

  const saveMutation = useMutation({
    mutationFn: async (opts?: { confirmSlug?: boolean }) => {
      const payload = {
        ...seoPayload,
        category_label: categories?.find((c) => c.slug === form.category_slug)?.label ?? form.category_label,
        confirm_slug_change: opts?.confirmSlug ? 1 : 0,
      }
      if (isNew) return api.post('/admin/blog', payload)
      return api.put(`/admin/blog/${id}`, payload)
    },
    onSuccess: (res) => {
      setError('')
      setSuccess(res.data?.message || (isNew ? 'پیش‌نویس ذخیره شد.' : 'مقاله ذخیره شد.'))
      setSeo(res.data.seo as SeoAnalysis)
      setChecklist(res.data.checklist as ChecklistResult)
      localStorage.removeItem(LOCAL_DRAFT_KEY(isNew ? 'new' : String(id)))
      if (isNew && res.data?.data?.id) {
        navigate(adminPath(`blog/${res.data.data.id}/edit`), { replace: true })
      } else {
        queryClient.invalidateQueries({ queryKey: ['admin-blog', id] })
      }
    },
    onError: (err: unknown) => {
      setSuccess('')
      const axiosErr = err as { response?: { data?: { message?: string; code?: string; errors?: Record<string, string[]> }; status?: number } }
      if (axiosErr.response?.data?.code === 'slug_change_protected') {
        if (confirm(`${axiosErr.response.data.message}\n\nآیا ۳۰۱ ساخته و ادامه داده شود؟`)) {
          saveMutation.mutate({ confirmSlug: true })
        }
        return
      }
      if (axiosErr.response?.status === 409) {
        setError(axiosErr.response.data?.message || 'Conflict Warning — ویرایش همزمان')
        return
      }
      setError(extractApiError(err, 'خطا در ذخیره مقاله'))
    },
  })

  const generateCover = useMutation({
    mutationFn: async () => {
      if (isNew || !id) throw new Error('ابتدا مقاله را ذخیره کنید.')
      const res = await api.post('/admin/blog-images/jobs', {
        blog_post_id: Number(id),
        force: true,
        run_now: true,
      })
      return res.data
    },
    onSuccess: (data) => {
      setError('')
      const url = data?.data?.public_url as string | undefined
      if (url) {
        update('cover_image', url)
        setSuccess('تصویر شاخص ساخته و اعمال شد.')
      } else {
        setSuccess(data?.message || 'کار تصویر ساخته شد — از صفحه تصاویر تأیید کنید.')
      }
      queryClient.invalidateQueries({ queryKey: ['admin-blog', id] })
    },
    onError: (err) => {
      setSuccess('')
      setError(extractApiError(err, 'ساخت تصویر ناموفق بود'))
    },
  })

  const runAi = async (action: string, extra: Record<string, unknown> = {}) => {
    setAiBusy(true)
    setError('')
    try {
      const res = await api.post('/admin/blog/ai/assist', {
        action,
        payload: {
          ...seoPayload,
          id: isNew ? undefined : Number(id),
          exclude_id: isNew ? 0 : Number(id),
          ...extra,
        },
      })
      setAiResult(res.data.data)
      const result = res.data.data?.result
      if (action === 'titles' && Array.isArray(result) && result[0]) {
        // Suggest only — do not auto-apply publish fields
      }
      if (action === 'cannibalization_check') setCannibal(result)
      if (action === 'internal_links' && Array.isArray(result)) setLinkSuggestions(result)
      return result
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string } } }
      setError(axiosErr.response?.data?.message || 'AI Assistant Temporarily Unavailable — ادامه کار دستی ممکن است.')
      return null
    } finally {
      setAiBusy(false)
    }
  }

  const applyAiField = (field: keyof FormState, value: string) => update(field, value as never)

  const workflow = async (action: 'submit-review' | 'approve' | 'publish' | 'unpublish' | 'archive') => {
    if (isNew) {
      setError('ابتدا مقاله را ذخیره کنید.')
      return
    }
    try {
      if (action === 'publish' && checklist && !checklist.passed) {
        if (!confirm(`چک‌لیست انتشار کامل نیست:\n${checklist.blocking.join('\n')}\n\nادامه؟`)) return
      }
      await api.post(`/admin/blog/${id}/${action}`)
      queryClient.invalidateQueries({ queryKey: ['admin-blog', id] })
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string; checklist?: ChecklistResult; gate?: unknown } } }
      setChecklist(axiosErr.response?.data?.checklist ?? checklist)
      setError(axiosErr.response?.data?.message || 'خطا در گردش کار')
    }
  }

  const doSchedule = async () => {
    if (isNew || !scheduleAt) return
    try {
      await api.post(`/admin/blog/${id}/schedule`, { scheduled_at: scheduleAt, timezone: 'Asia/Tehran' })
      queryClient.invalidateQueries({ queryKey: ['admin-blog', id] })
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string } } }
      setError(axiosErr.response?.data?.message || 'زمان‌بندی ناموفق')
    }
  }

  const openPreview = async () => {
    if (isNew) return
    const res = await api.post(`/admin/blog/${id}/preview-token`)
    setPreviewUrl(res.data.data.url as string)
    window.open(res.data.data.url, '_blank')
  }

  const restoreVersion = async (versionId: number) => {
    if (!confirm('بازنشانی این Revision؟ وضعیت فعلی قبل از restore ذخیره می‌شود.')) return
    await api.post(`/admin/blog/${id}/versions/${versionId}/restore`)
    queryClient.invalidateQueries({ queryKey: ['admin-blog', id] })
  }

  const recoverDraft = () => {
    const server = postBundle?.data?.autosave_payload?.data
    if (server) {
      setForm((f) => ({ ...f, ...server, related_slugs_text: (server.related_slugs || []).join(', ') } as FormState))
    } else {
      try {
        const raw = localStorage.getItem(LOCAL_DRAFT_KEY(isNew ? 'new' : String(id)))
        if (raw) setForm((f) => ({ ...f, ...JSON.parse(raw) }))
      } catch { /* ignore */ }
    }
    setRecoverBanner(false)
  }

  const uploadImage = async (file: File, alt: string) => {
    const body = new FormData()
    body.append('image', file)
    if (alt) body.append('alt', alt)
    const res = await api.post('/admin/blog/upload-image', body, { headers: { 'Content-Type': 'multipart/form-data' } })
    return res.data.data.html as string
  }

  const uploadCover = async (file: File) => {
    const body = new FormData()
    body.append('image', file)
    const res = await api.post('/admin/blog/upload-cover', body, { headers: { 'Content-Type': 'multipart/form-data' } })
    return res.data.data.url as string
  }

  const update = <K extends keyof FormState>(key: K, value: FormState[K]) =>
    setForm((f) => ({ ...f, [key]: value }))

  const updateFaq = (index: number, field: keyof FaqItem, value: string) => {
    setForm((f) => {
      const faq = [...f.faq]
      faq[index] = { ...faq[index], [field]: value }
      return { ...f, faq }
    })
  }

  const wc = wordCount(form.content)
  const readingApprox = Math.max(1, Math.ceil(wc / 200))

  if (!isNew && isLoading) {
    return <div className="p-8 text-center text-muted">در حال بارگذاری…</div>
  }

  const serpTitle = form.meta_title || form.title || 'عنوان نمونه'
  const serpDesc = form.meta_description || form.excerpt || 'توضیح نمونه…'
  const serpUrl = `https://posheapp.ir/blog/${form.slug || 'slug'}`

  return (
    <div className="space-y-6 animate-fade-in max-w-7xl mx-auto">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Link to={adminPath('blog')}>
            <Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button>
          </Link>
          <div>
            <h1 className="text-2xl font-bold">{isNew ? 'مقاله جدید' : 'ویرایش مقاله'}</h1>
            <p className="text-xs text-muted">
              وضعیت: {labelFa(REVIEW_STATUS_FA, form.review_status)} · حدود {readingApprox} دقیقه مطالعه · {wc} کلمه (شاخص کیفیت داخلی است)
            </p>
          </div>
        </div>
        <div className="flex flex-wrap gap-2">
          {!isNew && (
            <>
              <Button variant="outline" size="sm" onClick={() => workflow('submit-review')}><Send className="h-4 w-4" /> ارسال برای بررسی</Button>
              <Button variant="outline" size="sm" onClick={() => workflow('approve')}><CheckCircle2 className="h-4 w-4" /> تأیید</Button>
              <Button variant="outline" size="sm" onClick={openPreview}><Eye className="h-4 w-4" /> پیش‌نمایش</Button>
              <Button variant="outline" size="sm" onClick={() => workflow('publish')}>انتشار</Button>
              <Button variant="outline" size="sm" onClick={() => workflow('unpublish')}>لغو انتشار</Button>
              <Button variant="outline" size="sm" onClick={() => workflow('archive')}>آرشیو</Button>
            </>
          )}
          <Button onClick={() => saveMutation.mutate({})} disabled={saveMutation.isPending}>
            <Save className="h-4 w-4" />
            {saveMutation.isPending ? 'ذخیره…' : 'ذخیره پیش‌نویس'}
          </Button>
        </div>
      </div>

      {recoverBanner && (
        <div className="rounded-xl border border-amber-500/40 bg-amber-500/10 p-3 flex flex-wrap items-center justify-between gap-3 text-sm">
          <span className="flex items-center gap-2"><AlertTriangle className="h-4 w-4" /> پیش‌نویس بازیابی‌پذیر موجود است</span>
          <Button size="sm" variant="outline" onClick={recoverDraft}><RotateCcw className="h-4 w-4" /> بازیابی</Button>
        </div>
      )}

      {error && (
        <div className="rounded-xl border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-200 whitespace-pre-wrap" role="alert">
          {error}
        </div>
      )}
      {success && (
        <div className="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-200" role="status">
          {success}
        </div>
      )}

      {cannibal && cannibal.risk !== 'low' && (
        <div className="rounded-xl border border-amber-500/40 bg-amber-500/10 p-3 text-sm">
          <strong>هشدار — احتمال رقابت داخلی محتوا ({cannibal.risk === 'high' ? 'بالا' : cannibal.risk === 'medium' ? 'متوسط' : cannibal.risk})</strong>
          <p className="mt-1">{cannibal.recommendation}</p>
          <p className="text-xs text-muted mt-1">پیشنهاد: به‌روزرسانی مقاله موجود یا مقاله جدید با نیت جستجوی متفاوت.</p>
        </div>
      )}

      <div className="grid lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader><CardTitle>محتوا</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              <div>
                <label className="text-sm text-muted mb-1 block">عنوان *</label>
                <Input value={form.title} onChange={(e) => update('title', e.target.value)} onBlur={() => runAi('cannibalization_check')} />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">نامک انگلیسی (Slug) — تغییر روی مقاله منتشرشده نیاز به ریدایرکت ۳۰۱ دارد</label>
                <Input value={form.slug} onChange={(e) => update('slug', e.target.value)} dir="ltr" placeholder="my-seo-post" />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">خلاصه</label>
                <textarea className="w-full min-h-[80px] rounded-xl border border-card-border bg-background/50 p-3 text-sm" value={form.excerpt} onChange={(e) => update('excerpt', e.target.value)} />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">متن مقاله *</label>
                <RichTextEditor editorKey={id ?? 'new'} value={form.content} onChange={(html) => update('content', html)} onUploadImage={uploadImage} placeholder="متن مقاله را بنویسید…" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="flex items-center gap-2"><Sparkles className="h-4 w-4" /> دستیار نگارش هوشمند</CardTitle>
              <span className="text-[10px] text-muted">بدون تأیید انسان منتشر نمی‌شود</span>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex flex-wrap gap-2">
                {[
                  ['brief', 'بریف محتوا'],
                  ['outline', 'ساختار'],
                  ['titles', 'پیشنهاد عنوان'],
                  ['meta_description', 'متا توضیحات'],
                  ['excerpt', 'خلاصه'],
                  ['slug', 'نامک'],
                  ['intro', 'مقدمه'],
                  ['conclusion', 'جمع‌بندی'],
                  ['faq', 'سوالات متداول'],
                  ['cta', 'فراخوان اقدام'],
                  ['simplify', 'ساده‌سازی'],
                  ['expand', 'گسترش'],
                  ['normalize_persian', 'نرمال‌سازی فارسی'],
                  ['internal_links', 'لینک داخلی'],
                  ['intent_suggest', 'نیت جستجو'],
                  ['cannibalization_check', 'رقابت داخلی'],
                ].map(([action, label]) => (
                  <Button key={action} type="button" size="sm" variant="outline" disabled={aiBusy} onClick={() => runAi(action)}>
                    {label}
                  </Button>
                ))}
              </div>
              {aiBusy && <p className="text-xs text-muted">دستیار در حال کار…</p>}
              {aiResult != null && (
                <pre className="text-xs overflow-auto max-h-48 rounded-lg border border-card-border p-3 bg-background/40 whitespace-pre-wrap" dir="rtl">
                  {JSON.stringify(aiResult, null, 2)}
                </pre>
              )}
              <div className="flex flex-wrap gap-2">
                <Button type="button" size="sm" variant="secondary" disabled={aiBusy} onClick={async () => {
                  const r = await runAi('meta_description')
                  if (r?.text) applyAiField('meta_description', r.text)
                }}>اعمال متا پیشنهادی</Button>
                <Button type="button" size="sm" variant="secondary" disabled={aiBusy} onClick={async () => {
                  const r = await runAi('excerpt')
                  if (r?.text) applyAiField('excerpt', r.text)
                }}>اعمال خلاصه</Button>
                <Button type="button" size="sm" variant="secondary" disabled={aiBusy} onClick={async () => {
                  const r = await runAi('slug')
                  if (r?.slug) applyAiField('slug', r.slug)
                }}>اعمال نامک</Button>
                <Button type="button" size="sm" variant="secondary" disabled={aiBusy} onClick={async () => {
                  const r = await runAi('titles')
                  if (Array.isArray(r) && r[0]) applyAiField('meta_title', r[0])
                }}>اعمال عنوان پیشنهادی</Button>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle>دسته‌بندی / نیت جستجو / نوع محتوا</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              <div>
                <label className="text-sm text-muted mb-1 block">دسته</label>
                <select className="w-full rounded-xl border border-card-border bg-background/50 p-3 text-sm" value={form.category_slug} onChange={(e) => update('category_slug', e.target.value)}>
                  <option value="">— انتخاب دسته —</option>
                  {categories?.map((c) => <option key={c.slug} value={c.slug}>{c.label}</option>)}
                </select>
              </div>
              <div className="grid sm:grid-cols-2 gap-4">
                <div>
                  <label className="text-sm text-muted mb-1 block">نیت جستجو</label>
                  <select className="w-full rounded-xl border border-card-border bg-background/50 p-3 text-sm" value={form.search_intent} onChange={(e) => update('search_intent', e.target.value)}>
                    {INTENTS.map((i) => <option key={i} value={i}>{SEARCH_INTENT_FA[i] || i}</option>)}
                  </select>
                </div>
                <div>
                  <label className="text-sm text-muted mb-1 block">نوع محتوا</label>
                  <select className="w-full rounded-xl border border-card-border bg-background/50 p-3 text-sm" value={form.content_type} onChange={(e) => update('content_type', e.target.value)}>
                    {CONTENT_TYPES.map((i) => <option key={i} value={i}>{CONTENT_TYPE_FA[i] || i}</option>)}
                  </select>
                </div>
              </div>
              <div className="grid sm:grid-cols-2 gap-4">
                <div>
                  <label className="text-sm text-muted mb-1 block">کلمه کلیدی / موضوع اصلی</label>
                  <Input value={form.focus_keyword} onChange={(e) => update('focus_keyword', e.target.value)} />
                </div>
                <div>
                  <label className="text-sm text-muted mb-1 block">نوع اسکیما (Schema)</label>
                  <Input value={form.schema_type} onChange={(e) => update('schema_type', e.target.value)} dir="ltr" />
                </div>
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">مرحله قیف</label>
                <select className="w-full rounded-xl border border-card-border bg-background/50 p-3 text-sm" value={form.funnel_stage} onChange={(e) => update('funnel_stage', e.target.value)}>
                  {Object.entries(FUNNEL_STAGE_FA).map(([value, label]) => (
                    <option key={value} value={value}>{label}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">مقالات مرتبط (نامک با ویرگول)</label>
                <Input value={form.related_slugs_text} onChange={(e) => update('related_slugs_text', e.target.value)} dir="ltr" />
              </div>
              {!isNew && (
                <Button type="button" size="sm" variant="outline" onClick={async () => {
                  const res = await api.get(`/admin/blog/${id}/suggest-links`)
                  setLinkSuggestions(res.data.data)
                }}>پیشنهاد لینک داخلی</Button>
              )}
              {linkSuggestions.length > 0 && (
                <ul className="space-y-2 text-sm">
                  {linkSuggestions.map((s) => (
                    <li key={s.slug} className="rounded-lg border border-card-border p-2 flex justify-between gap-2">
                      <div>
                        <p className="font-medium">{s.title}</p>
                        <p className="text-xs text-muted">{s.reason} · انکر پیشنهادی: {s.suggested_anchor}</p>
                      </div>
                      <Button type="button" size="sm" variant="ghost" onClick={() => {
                        const next = form.related_slugs_text ? `${form.related_slugs_text}, ${s.slug}` : s.slug
                        update('related_slugs_text', next)
                      }}>پذیرش</Button>
                    </li>
                  ))}
                </ul>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle>پرسش‌وپاسخ</CardTitle>
              <Button type="button" variant="outline" size="sm" onClick={() => update('faq', [...form.faq, { question: '', answer: '' }])}>
                <Plus className="h-4 w-4" /> افزودن
              </Button>
            </CardHeader>
            <CardContent className="space-y-4">
              {form.faq.map((item, index) => (
                <div key={index} className="rounded-xl border border-card-border p-4 space-y-3">
                  <div className="flex justify-between">
                    <span className="text-sm">سوال {index + 1}</span>
                    <Button type="button" variant="ghost" size="icon" onClick={() => update('faq', form.faq.filter((_, i) => i !== index))}>
                      <Trash2 className="h-4 w-4 text-danger" />
                    </Button>
                  </div>
                  <Input value={item.question} onChange={(e) => updateFaq(index, 'question', e.target.value)} placeholder="سوال" />
                  <textarea className="w-full min-h-[72px] rounded-xl border border-card-border bg-background/50 p-3 text-sm" value={item.answer} onChange={(e) => updateFaq(index, 'answer', e.target.value)} placeholder="پاسخ" />
                </div>
              ))}
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle>منابع (راستی‌آزمایی)</CardTitle>
              <Button type="button" variant="outline" size="sm" onClick={() => update('sources', [...form.sources, { title: '', url: '' }])}>
                <Plus className="h-4 w-4" /> منبع
              </Button>
            </CardHeader>
            <CardContent className="space-y-3">
              <p className="text-xs text-muted">برای آمار/حقوقی/مالی: منبع لازم است — جعل داده ممنوع.</p>
              {form.sources.map((s, i) => (
                <div key={i} className="grid sm:grid-cols-2 gap-2">
                  <Input placeholder="عنوان منبع" value={s.title} onChange={(e) => {
                    const sources = [...form.sources]; sources[i] = { ...sources[i], title: e.target.value }; update('sources', sources)
                  }} />
                  <Input placeholder="آدرس منبع" dir="ltr" value={s.url} onChange={(e) => {
                    const sources = [...form.sources]; sources[i] = { ...sources[i], url: e.target.value }; update('sources', sources)
                  }} />
                </div>
              ))}
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle>سئو / اشتراک‌گذاری / ربات‌ها / کانونیکال</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              <div>
                <label className="text-sm text-muted mb-1 block">عنوان سئو</label>
                <Input value={form.meta_title} onChange={(e) => update('meta_title', e.target.value)} />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">توضیحات متا</label>
                <textarea className="w-full min-h-[80px] rounded-xl border border-card-border bg-background/50 p-3 text-sm" value={form.meta_description} onChange={(e) => update('meta_description', e.target.value)} />
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">آدرس کانونیکال (خالی = خود صفحه)</label>
                <Input value={form.canonical_url} onChange={(e) => update('canonical_url', e.target.value)} dir="ltr" placeholder="فقط در صورت نیاز و با احتیاط" />
                {form.canonical_url && <p className="text-xs text-amber-400 mt-1">هشدار: کانونیکال سفارشی فعال است.</p>}
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">دستور ربات‌ها</label>
                <select className="w-full rounded-xl border border-card-border bg-background/50 p-3 text-sm" value={form.robots_directive} onChange={(e) => update('robots_directive', e.target.value)}>
                  <option value="index,follow">ایندکس + دنبال کردن لینک</option>
                  <option value="noindex,follow">بدون ایندکس + دنبال کردن لینک</option>
                  <option value="noindex,nofollow">بدون ایندکس + بدون دنبال کردن</option>
                  <option value="index,nofollow">ایندکس + بدون دنبال کردن</option>
                </select>
              </div>
              <div className="grid sm:grid-cols-2 gap-4">
                <div>
                  <label className="text-sm text-muted mb-1 block">عنوان اشتراک‌گذاری (OG)</label>
                  <Input value={form.og_title} onChange={(e) => update('og_title', e.target.value)} />
                </div>
                <div>
                  <label className="text-sm text-muted mb-1 block">تصویر اشتراک‌گذاری (OG)</label>
                  <Input value={form.og_image} onChange={(e) => update('og_image', e.target.value)} dir="ltr" />
                </div>
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">توضیح اشتراک‌گذاری (OG)</label>
                <textarea className="w-full min-h-[60px] rounded-xl border border-card-border bg-background/50 p-3 text-sm" value={form.og_description} onChange={(e) => update('og_description', e.target.value)} />
              </div>
              <div className="grid sm:grid-cols-2 gap-4">
                <div>
                  <label className="text-sm text-muted mb-1 block">متن فراخوان اقدام</label>
                  <Input value={form.cta_text} onChange={(e) => update('cta_text', e.target.value)} />
                </div>
                <div>
                  <label className="text-sm text-muted mb-1 block">لینک فراخوان اقدام</label>
                  <Input value={form.cta_url} onChange={(e) => update('cta_url', e.target.value)} dir="ltr" />
                </div>
              </div>
              <div>
                <label className="text-sm text-muted mb-1 block">کلید فراخوان تبدیل (اختیاری)</label>
                <Input value={form.cro_cta_key} onChange={(e) => update('cro_cta_key', e.target.value)} dir="ltr" placeholder="کلید اختیاری" />
              </div>
              <ImageUploadField label="تصویر شاخص" value={form.cover_image} onChange={(url) => update('cover_image', url)} onUpload={uploadCover} hint="متن جایگزین و ابعاد را در کتابخانه رسانه بررسی کنید." />
              {!isNew && (
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  className="mt-2"
                  disabled={generateCover.isPending}
                  onClick={() => generateCover.mutate()}
                >
                  <Sparkles className="h-4 w-4" />
                  {generateCover.isPending ? 'در حال ساخت تصویر…' : 'ساخت تصویر شاخص (AI)'}
                </Button>
              )}
              <div>
                <label className="text-sm text-muted mb-1 block">نویسنده</label>
                <Input value={form.author_name} onChange={(e) => update('author_name', e.target.value)} />
              </div>
            </CardContent>
          </Card>

          {!isNew && (
            <Card>
              <CardHeader><CardTitle>زمان‌بندی / پیش‌نمایش دستگاه</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                <div className="flex flex-wrap gap-2 items-end">
                  <div className="flex-1 min-w-[200px]">
                    <label className="text-sm text-muted mb-1 block">زمان انتشار (Asia/Tehran)</label>
                    <Input type="datetime-local" value={scheduleAt || form.scheduled_at} onChange={(e) => setScheduleAt(e.target.value)} dir="ltr" />
                  </div>
                  <Button type="button" onClick={doSchedule}><Clock className="h-4 w-4" /> زمان‌بندی</Button>
                </div>
                <div className="flex gap-2">
                  {(['desktop', 'tablet', 'mobile'] as const).map((d) => (
                    <Button key={d} type="button" size="sm" variant={previewDevice === d ? 'default' : 'outline'} onClick={() => setPreviewDevice(d)}>{DEVICE_FA[d]}</Button>
                  ))}
                  <Button type="button" size="sm" variant="outline" onClick={openPreview}>باز کردن پیش‌نمایش</Button>
                </div>
                {previewUrl && <p className="text-xs text-muted" dir="ltr">{previewUrl} · {DEVICE_FA[previewDevice]}</p>}
              </CardContent>
            </Card>
          )}

          {!isNew && versions.length > 0 && (
            <Card>
              <CardHeader><CardTitle>نسخه‌ها</CardTitle></CardHeader>
              <CardContent className="space-y-2">
                {versions.map((v) => (
                  <div key={v.id} className="flex justify-between gap-2 text-sm rounded-lg border border-card-border px-3 py-2">
                    <span>نسخه {v.version} — {v.note} — {v.created_by} — {v.created_at}</span>
                    <Button type="button" size="sm" variant="ghost" onClick={() => restoreVersion(v.id)}>بازیابی</Button>
                  </div>
                ))}
              </CardContent>
            </Card>
          )}
        </div>

        <div className="space-y-4">
          <SeoScorePanel analysis={seo} loading={seoLoading} />

          <Card>
            <CardHeader><CardTitle className="text-base">چک‌لیست انتشار</CardTitle></CardHeader>
            <CardContent className="space-y-2 text-sm">
              <p className="text-[10px] text-muted">{checklist?.note || 'راهنمای داخلی — نمره گوگل نیست'}</p>
              {checklist?.checks?.map((c) => (
                <div key={c.id} className={`rounded-lg border px-2 py-1.5 text-xs ${c.ok ? 'border-emerald-500/30' : c.blocking ? 'border-red-500/40' : 'border-amber-500/30'}`}>
                  {c.ok ? '✓' : '✗'} {c.label} — {c.message}
                </div>
              ))}
              {!checklist && <p className="text-muted text-xs">پس از نوشتن محتوا محاسبه می‌شود…</p>}
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle className="text-base">پیش‌نمایش نتایج جستجو <span className="text-[10px] text-muted">(تخمینی)</span></CardTitle></CardHeader>
            <CardContent className="space-y-1 text-sm" dir="ltr">
              <p className="text-blue-400 text-base truncate">{serpTitle}</p>
              <p className="text-emerald-600 text-xs truncate">{serpUrl}</p>
              <p className="text-muted text-xs line-clamp-2">{serpDesc}</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle className="text-base">پیش‌نمایش اشتراک‌گذاری <span className="text-[10px] text-muted">(تخمینی)</span></CardTitle></CardHeader>
            <CardContent className="text-sm space-y-2">
              {(form.og_image || form.cover_image) && (
                <img src={form.og_image || form.cover_image} alt="" className="w-full h-28 object-cover rounded-lg" />
              )}
              <p className="font-medium">{form.og_title || form.meta_title || form.title || '—'}</p>
              <p className="text-xs text-muted">{form.og_description || form.meta_description || form.excerpt || '—'}</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  )
}
