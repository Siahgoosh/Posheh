import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation } from '@tanstack/react-query'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { captureUtmFromUrl, getFirstTouch, getStoredUtm, rememberFirstTouch, trackCro } from '@/lib/cro'

type Cta = {
  id?: number | null
  key?: string
  title?: string
  description?: string
  button_text?: string
  url?: string
  type?: string
}

type Props = {
  variant?: 'short' | 'specialized'
  source?: string
  articleSlug?: string
  categorySlug?: string
  intent?: string
  cta?: Cta | null
  showSticky?: boolean
}

const REQUEST_TYPES = [
  { value: 'DEMO', label: 'آشنایی با پوشه / دمو' },
  { value: 'BUY', label: 'خرید ملک' },
  { value: 'SELL', label: 'فروش ملک' },
  { value: 'RENT', label: 'رهن / اجاره' },
  { value: 'SUPPORT', label: 'پشتیبانی' },
  { value: 'OTHER', label: 'سایر' },
]

export function LeadCaptureBlock({
  variant = 'short',
  source = 'BLOG',
  articleSlug,
  categorySlug,
  intent,
  cta,
  showSticky = false,
}: Props) {
  const [name, setName] = useState('')
  const [mobile, setMobile] = useState('')
  const [requestType, setRequestType] = useState(intent === 'commercial' ? 'DEMO' : 'OTHER')
  const [city, setCity] = useState('')
  const [budget, setBudget] = useState('')
  const [message, setMessage] = useState('')
  const [consent, setConsent] = useState(false)
  const [honeypot, setHoneypot] = useState('')
  const [done, setDone] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    captureUtmFromUrl()
    rememberFirstTouch(window.location.pathname)
    trackCro('cta_view', { article_slug: articleSlug, cro_cta_id: cta?.id, meta: { cta_key: cta?.key } })
    trackCro('form_start', { article_slug: articleSlug })
  }, [articleSlug, cta?.id, cta?.key])

  const mutation = useMutation({
    mutationFn: async () => {
      const utm = getStoredUtm()
      const res = await api.post('/cro/leads', {
        name: name || undefined,
        mobile,
        request_type: requestType,
        city: city || undefined,
        budget: budget || undefined,
        message: message || undefined,
        consent,
        source,
        article_slug: articleSlug,
        article_url: articleSlug ? `/blog/${articleSlug}` : undefined,
        category_slug: categorySlug,
        landing_page: window.location.pathname,
        first_touch_path: getFirstTouch(),
        last_touch_path: window.location.pathname,
        conversion_page: window.location.pathname,
        intent,
        form_variant: variant,
        website: honeypot || undefined,
        ...utm,
      })
      return res.data
    },
    onSuccess: () => {
      setDone(true)
      setError('')
    },
    onError: (err: unknown) => {
      const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message
      setError(msg || 'ثبت درخواست ناموفق بود. شماره را بررسی کنید.')
    },
  })

  const ctaUrl = cta?.url || '/contact#lead-form'
  const isInternal = ctaUrl.startsWith('/')

  return (
    <div className="space-y-4">
      <div className="p-6 rounded-2xl bg-primary/10 border border-primary/20 text-center space-y-3">
        <p className="font-medium">{cta?.title || 'اگر سوالی دارید، درخواست خود را ثبت کنید'}</p>
        {cta?.description && <p className="text-sm text-muted">{cta.description}</p>}
        {isInternal ? (
          <Link
            to={ctaUrl}
            onClick={() => trackCro('cta_click', { article_slug: articleSlug, cro_cta_id: cta?.id })}
          >
            <Button type="button">{cta?.button_text || 'ادامه'}</Button>
          </Link>
        ) : (
          <a
            href={ctaUrl}
            onClick={() => trackCro('cta_click', { article_slug: articleSlug, cro_cta_id: cta?.id })}
            target="_blank"
            rel="noreferrer"
          >
            <Button type="button">{cta?.button_text || 'ادامه'}</Button>
          </a>
        )}
      </div>

      <div id="lead-form" className="p-5 rounded-2xl border border-card-border space-y-3">
        <h3 className="font-semibold text-sm">فرم کوتاه درخواست</h3>
        <p className="text-xs text-muted">فقط اطلاعات لازم — بدون اسپم. با ارسال، با <Link to="/privacy" className="underline">حریم خصوصی</Link> موافقت می‌کنید.</p>
        {done ? (
          <p className="text-sm text-primary">درخواست ثبت شد. به‌زودی پیگیری می‌شود.</p>
        ) : (
          <form
            className="space-y-3"
            onSubmit={(e) => {
              e.preventDefault()
              if (!consent) {
                setError('لطفاً موافقت با حریم خصوصی را علامت بزنید.')
                return
              }
              mutation.mutate()
            }}
          >
            {/* honeypot */}
            <input
              tabIndex={-1}
              autoComplete="off"
              className="hidden"
              aria-hidden
              value={honeypot}
              onChange={(e) => setHoneypot(e.target.value)}
            />
            <input
              className="w-full rounded-lg border border-card-border bg-background px-3 py-2 text-sm"
              placeholder="نام (اختیاری)"
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
            <input
              required
              className="w-full rounded-lg border border-card-border bg-background px-3 py-2 text-sm"
              placeholder="موبایل *"
              value={mobile}
              onChange={(e) => setMobile(e.target.value)}
              inputMode="tel"
            />
            <select
              className="w-full rounded-lg border border-card-border bg-background px-3 py-2 text-sm"
              value={requestType}
              onChange={(e) => setRequestType(e.target.value)}
            >
              {REQUEST_TYPES.map((t) => (
                <option key={t.value} value={t.value}>{t.label}</option>
              ))}
            </select>
            {variant === 'specialized' && (
              <>
                <input
                  className="w-full rounded-lg border border-card-border bg-background px-3 py-2 text-sm"
                  placeholder="شهر"
                  value={city}
                  onChange={(e) => setCity(e.target.value)}
                />
                <input
                  className="w-full rounded-lg border border-card-border bg-background px-3 py-2 text-sm"
                  placeholder="بودجه تقریبی (اختیاری)"
                  value={budget}
                  onChange={(e) => setBudget(e.target.value)}
                />
                <textarea
                  className="w-full rounded-lg border border-card-border bg-background px-3 py-2 text-sm"
                  placeholder="توضیح کوتاه"
                  rows={3}
                  value={message}
                  onChange={(e) => setMessage(e.target.value)}
                />
              </>
            )}
            <label className="flex items-start gap-2 text-xs text-muted">
              <input type="checkbox" checked={consent} onChange={(e) => setConsent(e.target.checked)} />
              <span>اطلاعات من فقط برای پیگیری همین درخواست استفاده شود.</span>
            </label>
            {error && <p className="text-xs text-red-500">{error}</p>}
            <Button type="submit" disabled={mutation.isPending} className="w-full">
              {mutation.isPending ? 'در حال ارسال…' : 'ثبت درخواست'}
            </Button>
          </form>
        )}
      </div>

      {showSticky && (
        <div className="fixed bottom-0 inset-x-0 z-40 md:hidden border-t border-card-border bg-background/95 backdrop-blur p-3">
          <a href="#lead-form" className="block">
            <Button className="w-full" size="sm">درخواست مشاوره</Button>
          </a>
        </div>
      )}
    </div>
  )
}
