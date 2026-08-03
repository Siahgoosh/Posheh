import { useMemo, useState } from 'react'
import { useParams } from 'react-router-dom'
import { useMutation, useQuery } from '@tanstack/react-query'
import {
  BadgeCheck, Building2, MapPin, Phone, MessageCircle, CalendarDays, Clock,
  X, ArrowLeft, Home, Newspaper, Users, Send, Maximize, BedDouble, Car,
  ArrowUpDown, Boxes, ChevronLeft, Eye, User2,
} from 'lucide-react'
import api from '@/lib/api'
import { toPersianDigits, toEnglishDigits, normalizeMobile, formatJalaliDate } from '@/lib/utils'
import { resolveSiteTheme } from '@/lib/officeSiteTheme'
import { SeoHead } from '@/components/seo/SeoHead'

interface Agent {
  name: string
  role_label: string
  mobile?: string
  avatar_url?: string
}

interface Post {
  id: number
  title: string
  slug: string
  excerpt?: string
  body?: string
  views?: number
  created_at?: string
}

interface SiteProperty {
  id: number
  code: string
  type?: string
  type_label?: string
  category_label?: string
  price?: number
  deposit?: number
  rent?: number
  area?: number
  rooms?: number
  floor?: number
  has_parking?: boolean
  has_elevator?: boolean
  has_storage?: boolean
  city?: string
  district?: string
  neighborhood?: string
  description?: string
  cover_url?: string
  created_at?: string
}

interface OfficeInfo {
  name: string
  brand_name: string
  brand_color: string
  subdomain: string
  city?: string
  address?: string
  phone?: string
  whatsapp?: string
  description?: string
  is_verified?: boolean
  logo_url?: string
  url: string
  theme?: {
    id: string
    brand_color: string
    hero_title?: string
    hero_subtitle?: string
    cta_text?: string
    show_stats?: boolean
    show_team?: boolean
    hero_style?: string
  }
  stats: { properties: number; posts: number; agents: number }
}

interface SiteData {
  office: OfficeInfo
  properties: SiteProperty[]
  posts: Post[]
  agents: Agent[]
}

function compactPrice(n?: number): string {
  if (!n || n <= 0) return 'توافقی'
  if (n >= 1_000_000_000) {
    const v = (n / 1_000_000_000).toFixed(n % 1_000_000_000 === 0 ? 0 : 1)
    return `${toPersianDigits(v)} میلیارد تومان`
  }
  if (n >= 1_000_000) {
    const v = (n / 1_000_000).toFixed(n % 1_000_000 === 0 ? 0 : 0)
    return `${toPersianDigits(v)} میلیون تومان`
  }
  return `${toPersianDigits(new Intl.NumberFormat('en-US').format(n))} تومان`
}

function priceLines(p: SiteProperty): { main: string; sub?: string } {
  if (p.type === 'rent' || p.type === 'mortgage') {
    return {
      main: p.deposit ? `ودیعه ${compactPrice(p.deposit)}` : 'رهن کامل',
      sub: p.rent ? `اجاره ${compactPrice(p.rent)}` : undefined,
    }
  }
  return { main: compactPrice(p.price) }
}

const TYPE_FILTERS = [
  { value: '', label: 'همه' },
  { value: 'sale', label: 'فروش' },
  { value: 'rent', label: 'اجاره' },
  { value: 'mortgage', label: 'رهن' },
  { value: 'pre_sale', label: 'پیش‌فروش' },
  { value: 'commercial', label: 'تجاری' },
  { value: 'land', label: 'زمین' },
]

const TIME_SLOTS = ['۹:۰۰', '۱۰:۰۰', '۱۱:۰۰', '۱۲:۰۰', '۱۴:۰۰', '۱۵:۰۰', '۱۶:۰۰', '۱۷:۰۰', '۱۸:۰۰']

export function OfficeSitePage({ subdomain: subdomainProp }: { subdomain?: string } = {}) {
  const params = useParams<{ subdomain: string }>()
  const subdomain = subdomainProp ?? params.subdomain
  const [typeFilter, setTypeFilter] = useState('')
  const [booking, setBooking] = useState<SiteProperty | null>(null)
  const [reading, setReading] = useState<Post | null>(null)

  const { data, isLoading, isError } = useQuery<SiteData>({
    queryKey: ['office-site', subdomain],
    queryFn: async () => (await api.get(`/sites/${subdomain}`, { timeout: 15000 })).data.data,
    enabled: !!subdomain,
    retry: 1,
  })

  const brand = data?.office.theme?.brand_color || data?.office.brand_color || '#0f766e'
  const theme = resolveSiteTheme(data?.office.theme, brand)
  const isClassicHero = data?.office.theme?.hero_style === 'solid' || theme.id === 'classic'

  const filtered = useMemo(() => {
    if (!data) return []
    if (!typeFilter) return data.properties
    return data.properties.filter((p) => p.type === typeFilter)
  }, [data, typeFilter])

  if (isLoading) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center bg-slate-50 text-slate-500 gap-3">
        <div className="h-9 w-9 animate-spin rounded-full border-2 border-slate-300 border-t-slate-600" />
        <span className="text-sm">در حال بارگذاری…</span>
      </div>
    )
  }

  if (isError || !data) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center bg-slate-50 text-slate-600 gap-3 px-6 text-center">
        <Building2 className="h-12 w-12 text-slate-300" />
        <p className="font-semibold">این وبسایت در دسترس نیست</p>
        <p className="text-sm text-slate-400">ممکن است هنوز منتشر نشده یا آدرس اشتباه باشد.</p>
      </div>
    )
  }

  const office = data.office

  return (
    <div dir="rtl" className={`min-h-screen ${theme.page}`}>
      <SeoHead title={`${office.name} | املاک`} description={office.description || `دفتر املاک ${office.name}`} path={`/site/${subdomain}`} />

      {/* Header */}
      <header className={`sticky top-0 z-30 backdrop-blur border-b ${theme.header}`}>
        <div className="mx-auto max-w-6xl px-4 h-16 flex items-center justify-between gap-4">
          <div className="flex items-center gap-3 min-w-0">
            {office.logo_url ? (
              <img src={office.logo_url} alt="" className="h-10 w-10 rounded-xl object-cover" />
            ) : (
              <div className="h-10 w-10 rounded-xl flex items-center justify-center text-white shrink-0" style={{ background: brand }}>
                <Building2 className="h-5 w-5" />
              </div>
            )}
            <div className="min-w-0">
              <p className={`font-bold truncate flex items-center gap-1 ${theme.headerText}`}>
                {office.brand_name}
                {office.is_verified && <BadgeCheck className="h-4 w-4" style={{ color: brand }} />}
              </p>
              {office.city && <p className={`text-xs truncate ${theme.muted}`}>{office.city}</p>}
            </div>
          </div>
          <nav className={`hidden md:flex items-center gap-6 text-sm ${theme.nav}`}>
            <a href="#listings" className={theme.navHover}>املاک</a>
            {data.posts.length > 0 && <a href="#blog" className={theme.navHover}>اخبار و مقالات</a>}
            {data.agents.length > 0 && <a href="#agents" className={theme.navHover}>مشاوران</a>}
            <a href="#contact" className={theme.navHover}>تماس</a>
          </nav>
          {office.phone && (
            <a href={`tel:${office.phone}`} className="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium text-white shadow-sm" style={{ background: brand }}>
              <Phone className="h-4 w-4" />
              <span className="hidden sm:inline" dir="ltr">{toPersianDigits(office.phone)}</span>
              <span className="sm:hidden">تماس</span>
            </a>
          )}
        </div>
      </header>

      {/* Hero */}
      <section className="relative overflow-hidden">
        <div className="absolute inset-0 -z-10" style={{ background: isClassicHero ? theme.heroOverlay : theme.heroOverlay }} />
        <div className="mx-auto max-w-6xl px-4 py-16 md:py-20">
          <div className="max-w-2xl">
            <span className="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium" style={{ borderColor: `${brand}44`, color: brand, background: `${brand}0d` }}>
              <Home className="h-3.5 w-3.5" /> دفتر مشاور املاک
            </span>
            <h1 className={`mt-5 text-3xl md:text-5xl font-black leading-tight ${theme.hero}`}>
              {office.theme?.hero_title || office.name}
            </h1>
            {(office.theme?.hero_subtitle || office.description) && (
              <p className={`mt-4 leading-relaxed text-lg ${theme.body}`}>{office.theme?.hero_subtitle || office.description}</p>
            )}
            <div className="mt-7 flex flex-wrap gap-3">
              <a href="#listings" className="inline-flex items-center gap-2 rounded-xl px-5 py-3 font-semibold text-white shadow-lg" style={{ background: brand }}>
                {office.theme?.cta_text || 'مشاهده املاک'} <ArrowLeft className="h-4 w-4" />
              </a>
              <a href="#contact" className={`inline-flex items-center gap-2 rounded-xl border px-5 py-3 font-semibold transition-colors ${theme.chipInactive}`}>
                رزرو بازدید
              </a>
            </div>
            {(office.theme?.show_stats !== false) && (
              <div className="mt-10 grid grid-cols-3 gap-4 max-w-md">
                {[
                  { label: 'فایل ملک', value: office.stats.properties, icon: Home },
                  { label: 'مقاله', value: office.stats.posts, icon: Newspaper },
                  { label: 'مشاور', value: office.stats.agents, icon: Users },
                ].map((s) => (
                  <div key={s.label} className={`rounded-2xl p-4 text-center shadow-sm border ${theme.card}`}>
                    <s.icon className="h-5 w-5 mx-auto mb-1.5" style={{ color: brand }} />
                    <p className={`text-2xl font-bold ${theme.heading}`}>{toPersianDigits(String(s.value))}</p>
                    <p className={`text-xs ${theme.muted}`}>{s.label}</p>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </section>

      {/* Listings */}
      <section id="listings" className="mx-auto max-w-6xl px-4 py-14">
        <div className="flex items-end justify-between gap-4 mb-6">
          <div>
            <h2 className={`text-2xl font-bold ${theme.heading}`}>املاک ما</h2>
            <p className={`text-sm mt-1 ${theme.muted}`}>جدیدترین فایل‌های دفتر</p>
          </div>
        </div>
        <div className="flex gap-2 overflow-x-auto pb-2 mb-6">
          {TYPE_FILTERS.map((tf) => {
            const active = typeFilter === tf.value
            return (
              <button
                key={tf.value}
                onClick={() => setTypeFilter(tf.value)}
                className="shrink-0 rounded-full px-4 py-1.5 text-sm font-medium border transition-colors"
                style={active
                  ? { background: brand, color: '#fff', borderColor: brand }
                  : { background: 'transparent', color: theme.muted, borderColor: theme.cardBorder }}
              >
                {tf.label}
              </button>
            )
          })}
        </div>

        {filtered.length === 0 ? (
          <div className="text-center py-20 text-slate-400">
            <Home className="h-12 w-12 mx-auto mb-3 text-slate-200" />
            <p>ملکی در این دسته یافت نشد</p>
          </div>
        ) : (
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {filtered.map((p) => {
              const pl = priceLines(p)
              return (
                <article key={p.id} className={`group rounded-2xl border overflow-hidden flex flex-col transition-shadow ${theme.card} ${theme.cardHover}`}>
                  <div className={`relative h-48 overflow-hidden ${theme.id === 'luxury' ? 'bg-neutral-800' : 'bg-slate-100'}`}>
                    {p.cover_url ? (
                      <img src={p.cover_url} alt={p.code} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center" style={{ background: `linear-gradient(135deg, ${brand}18, ${brand}08)` }}>
                        <Building2 className="h-14 w-14" style={{ color: `${brand}66` }} />
                      </div>
                    )}
                    <div className="absolute top-3 right-3 flex gap-1.5">
                      {p.type_label && (
                        <span className="rounded-full px-2.5 py-1 text-[11px] font-semibold text-white shadow" style={{ background: brand }}>{p.type_label}</span>
                      )}
                      {p.category_label && (
                        <span className="rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-medium text-slate-700 shadow">{p.category_label}</span>
                      )}
                    </div>
                  </div>
                  <div className="p-4 flex flex-col flex-1">
                    <div className="flex items-baseline justify-between gap-2">
                      <p className="font-bold text-lg" style={{ color: brand }}>{pl.main}</p>
                      <span className="text-xs text-slate-400">کد {toPersianDigits(p.code)}</span>
                    </div>
                    {pl.sub && <p className="text-sm text-slate-500 mt-0.5">{pl.sub}</p>}

                    {(p.city || p.district || p.neighborhood) && (
                      <p className="mt-2 flex items-center gap-1 text-sm text-slate-500">
                        <MapPin className="h-3.5 w-3.5 shrink-0" />
                        {[p.city, p.district, p.neighborhood].filter(Boolean).join('، ')}
                      </p>
                    )}

                    <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-slate-600">
                      {!!p.area && <span className="flex items-center gap-1"><Maximize className="h-4 w-4 text-slate-400" />{toPersianDigits(String(p.area))} متر</span>}
                      {!!p.rooms && <span className="flex items-center gap-1"><BedDouble className="h-4 w-4 text-slate-400" />{toPersianDigits(String(p.rooms))} خواب</span>}
                      {p.has_parking && <span className="flex items-center gap-1"><Car className="h-4 w-4 text-slate-400" />پارکینگ</span>}
                      {p.has_elevator && <span className="flex items-center gap-1"><ArrowUpDown className="h-4 w-4 text-slate-400" />آسانسور</span>}
                      {p.has_storage && <span className="flex items-center gap-1"><Boxes className="h-4 w-4 text-slate-400" />انباری</span>}
                    </div>

                    {p.description && (
                      <p className="mt-3 text-sm text-slate-500 line-clamp-2 leading-relaxed">{p.description}</p>
                    )}

                    <button
                      onClick={() => setBooking(p)}
                      className="mt-4 w-full inline-flex items-center justify-center gap-2 rounded-xl py-2.5 font-semibold text-white transition-opacity hover:opacity-90"
                      style={{ background: brand }}
                    >
                      <CalendarDays className="h-4 w-4" /> رزرو زمان بازدید
                    </button>
                  </div>
                </article>
              )
            })}
          </div>
        )}
      </section>

      {/* Blog */}
      {data.posts.length > 0 && (
        <section id="blog" className={`border-y ${theme.sectionAlt}`}>
          <div className="mx-auto max-w-6xl px-4 py-14">
            <h2 className={`text-2xl font-bold ${theme.heading}`}>اخبار و مقالات</h2>
            <p className={`text-sm mt-1 mb-6 ${theme.muted}`}>تازه‌ترین مطالب دفتر</p>
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
              {data.posts.map((post) => (
                <button
                  key={post.id}
                  onClick={() => setReading(post)}
                  className={`text-right rounded-2xl border p-5 transition-shadow flex flex-col ${theme.card} ${theme.cardHover}`}
                >
                  <span className="inline-flex items-center gap-1 text-xs text-slate-400 mb-2">
                    <CalendarDays className="h-3.5 w-3.5" />
                    {post.created_at ? formatJalaliDate(post.created_at) : ''}
                  </span>
                  <h3 className={`font-bold leading-snug ${theme.heading}`}>{post.title}</h3>
                  {(post.excerpt || post.body) && (
                    <p className={`mt-2 text-sm line-clamp-3 leading-relaxed flex-1 ${theme.body}`}>{post.excerpt || post.body}</p>
                  )}
                  <span className="mt-3 inline-flex items-center gap-1 text-sm font-medium" style={{ color: brand }}>
                    ادامه مطلب <ChevronLeft className="h-4 w-4" />
                  </span>
                </button>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Agents */}
      {data.agents.length > 0 && office.theme?.show_team !== false && (
        <section id="agents" className="mx-auto max-w-6xl px-4 py-14">
          <h2 className={`text-2xl font-bold ${theme.heading}`}>مشاوران ما</h2>
          <p className={`text-sm mt-1 mb-6 ${theme.muted}`}>برای مشاوره مستقیم تماس بگیرید</p>
          <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            {data.agents.map((a, i) => (
              <div key={i} className={`rounded-2xl border p-5 text-center ${theme.card}`}>
                {a.avatar_url ? (
                  <img src={a.avatar_url} alt={a.name} className="h-20 w-20 rounded-full object-cover mx-auto" />
                ) : (
                  <div className="h-20 w-20 rounded-full mx-auto flex items-center justify-center text-2xl font-bold text-white" style={{ background: brand }}>
                    {a.name?.charAt(0) || <User2 className="h-8 w-8" />}
                  </div>
                )}
                <p className={`mt-3 font-bold ${theme.heading}`}>{a.name}</p>
                <p className={`text-xs ${theme.muted}`}>{a.role_label}</p>
                {a.mobile && (
                  <div className="mt-3 flex justify-center gap-2">
                    <a href={`tel:${a.mobile}`} className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-600 hover:border-slate-300" title="تماس">
                      <Phone className="h-4 w-4" />
                    </a>
                    <a href={`https://wa.me/98${toEnglishDigits(a.mobile).replace(/^0/, '')}`} target="_blank" rel="noreferrer" className="inline-flex h-9 w-9 items-center justify-center rounded-full text-white" style={{ background: '#25D366' }} title="واتساپ">
                      <MessageCircle className="h-4 w-4" />
                    </a>
                  </div>
                )}
              </div>
            ))}
          </div>
        </section>
      )}

      {/* Contact + Visit form */}
      <section id="contact" className={`border-t ${theme.sectionAlt}`}>
        <div className="mx-auto max-w-6xl px-4 py-14 grid lg:grid-cols-2 gap-10">
          <div>
            <h2 className={`text-2xl font-bold ${theme.heading}`}>تماس با ما</h2>
            <p className={`mt-2 leading-relaxed ${theme.body}`}>برای بازدید ملک یا مشاوره رایگان با ما در ارتباط باشید.</p>
            <div className="mt-6 space-y-4">
              {office.address && (
                <div className="flex items-start gap-3">
                  <span className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl" style={{ background: `${brand}14`, color: brand }}><MapPin className="h-4 w-4" /></span>
                  <div><p className="text-sm text-slate-400">آدرس</p><p className="font-medium">{office.address}</p></div>
                </div>
              )}
              {office.phone && (
                <a href={`tel:${office.phone}`} className="flex items-start gap-3">
                  <span className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl" style={{ background: `${brand}14`, color: brand }}><Phone className="h-4 w-4" /></span>
                  <div><p className="text-sm text-slate-400">تلفن</p><p className="font-medium" dir="ltr">{toPersianDigits(office.phone)}</p></div>
                </a>
              )}
              {office.whatsapp && (
                <a href={`https://wa.me/98${toEnglishDigits(office.whatsapp).replace(/^0/, '')}`} target="_blank" rel="noreferrer" className="flex items-start gap-3">
                  <span className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl text-white" style={{ background: '#25D366' }}><MessageCircle className="h-4 w-4" /></span>
                  <div><p className="text-sm text-slate-400">واتساپ</p><p className="font-medium" dir="ltr">{toPersianDigits(office.whatsapp)}</p></div>
                </a>
              )}
            </div>
          </div>

          <div className={`rounded-2xl border p-6 ${theme.card}`}>
            <h3 className={`font-bold flex items-center gap-2 ${theme.heading}`}><CalendarDays className="h-5 w-5" style={{ color: brand }} /> درخواست بازدید عمومی</h3>
            <p className={`text-sm mt-1 ${theme.muted}`}>اطلاعات خود را وارد کنید تا با شما تماس بگیریم.</p>
            <div className="mt-4">
              <button onClick={() => setBooking({ id: 0, code: '' } as SiteProperty)} className="w-full inline-flex items-center justify-center gap-2 rounded-xl py-3 font-semibold text-white" style={{ background: brand }}>
                <Send className="h-4 w-4" /> ثبت درخواست بازدید
              </button>
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className={`border-t ${theme.footer}`}>
        <div className="mx-auto max-w-6xl px-4 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm">
          <div className="flex items-center gap-2">
            <Building2 className="h-5 w-5" style={{ color: brand }} />
            <span>{office.name}</span>
          </div>
          <p className={theme.footerMuted}>© {toPersianDigits(String(new Date().getFullYear()))} — تمامی حقوق محفوظ است</p>
          <a href="https://posheapp.ir" target="_blank" rel="noreferrer" className={`${theme.footerMuted} hover:opacity-80`}>قدرت گرفته از پوشه</a>
        </div>
      </footer>

      {booking && (
        <VisitBookingModal
          subdomain={subdomain!}
          property={booking}
          brand={brand}
          onClose={() => setBooking(null)}
        />
      )}

      {reading && (
        <PostReaderModal post={reading} brand={brand} onClose={() => setReading(null)} />
      )}
    </div>
  )
}

function nextDays(count: number): { value: string; weekday: string; day: string }[] {
  const days: { value: string; weekday: string; day: string }[] = []
  for (let i = 0; i < count; i++) {
    const d = new Date()
    d.setDate(d.getDate() + i)
    const weekday = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { weekday: 'short' }).format(d)
    const dayMonth = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { day: 'numeric', month: 'long' }).format(d)
    const label = i === 0 ? 'امروز' : i === 1 ? 'فردا' : weekday
    days.push({ value: `${label} ${dayMonth}`, weekday: label, day: dayMonth })
  }
  return days
}

function VisitBookingModal({ subdomain, property, brand, onClose }: {
  subdomain: string
  property: SiteProperty
  brand: string
  onClose: () => void
}) {
  const days = useMemo(() => nextDays(7), [])
  const [form, setForm] = useState({ name: '', mobile: '', email: '', message: '' })
  const [date, setDate] = useState('')
  const [time, setTime] = useState('')
  const [error, setError] = useState('')
  const [done, setDone] = useState(false)

  const mutation = useMutation({
    mutationFn: () => api.post(`/sites/${subdomain}/visit-request`, {
      name: form.name,
      mobile: normalizeMobile(form.mobile),
      email: form.email || undefined,
      property_id: property.id ? property.id : undefined,
      preferred_date: date || undefined,
      preferred_time: time || undefined,
      message: form.message || undefined,
    }),
    onSuccess: () => setDone(true),
    onError: () => setError('ثبت درخواست ناموفق بود. دوباره تلاش کنید.'),
  })

  const submit = () => {
    setError('')
    if (!form.name.trim()) return setError('نام را وارد کنید')
    const m = normalizeMobile(form.mobile)
    if (!/^09\d{9}$/.test(m)) return setError('شماره موبایل معتبر نیست')
    mutation.mutate()
  }

  return (
    <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4" onClick={onClose}>
      <div className="w-full sm:max-w-lg max-h-[92vh] overflow-y-auto rounded-t-3xl sm:rounded-2xl bg-white text-slate-800" onClick={(e) => e.stopPropagation()}>
        <div className="sticky top-0 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-4">
          <h3 className="font-bold flex items-center gap-2"><CalendarDays className="h-5 w-5" style={{ color: brand }} /> رزرو زمان بازدید</h3>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-700"><X className="h-5 w-5" /></button>
        </div>

        {done ? (
          <div className="p-8 text-center">
            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full" style={{ background: `${brand}18`, color: brand }}>
              <BadgeCheck className="h-7 w-7" />
            </div>
            <p className="font-bold text-lg text-slate-900">درخواست شما ثبت شد</p>
            <p className="text-slate-500 mt-2 text-sm">به‌زودی برای هماهنگی بازدید با شما تماس می‌گیریم.</p>
            <button onClick={onClose} className="mt-6 rounded-xl px-6 py-2.5 font-semibold text-white" style={{ background: brand }}>بستن</button>
          </div>
        ) : (
          <div className="p-5 space-y-4">
            {property.id ? (
              <div className="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3 text-sm">
                <span className="text-slate-400">ملک: </span>
                <span className="font-semibold">کد {toPersianDigits(property.code)}</span>
                {property.type_label && <span className="text-slate-500"> — {property.type_label}</span>}
              </div>
            ) : null}

            <div>
              <label className="block text-sm font-medium text-slate-600 mb-2 flex items-center gap-1"><CalendarDays className="h-4 w-4" /> روز بازدید</label>
              <div className="flex gap-2 overflow-x-auto pb-1">
                {days.map((d) => {
                  const active = date === d.value
                  return (
                    <button key={d.value} onClick={() => setDate(d.value)}
                      className="shrink-0 rounded-xl border px-3 py-2 text-center transition-colors"
                      style={active ? { background: brand, color: '#fff', borderColor: brand } : { borderColor: '#e2e8f0', background: '#fff' }}>
                      <span className="block text-xs font-semibold">{d.weekday}</span>
                      <span className="block text-[11px] opacity-80">{d.day}</span>
                    </button>
                  )
                })}
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-600 mb-2 flex items-center gap-1"><Clock className="h-4 w-4" /> ساعت</label>
              <div className="flex flex-wrap gap-2">
                {TIME_SLOTS.map((t) => {
                  const active = time === t
                  return (
                    <button key={t} onClick={() => setTime(t)}
                      className="rounded-lg border px-3 py-1.5 text-sm transition-colors"
                      style={active ? { background: brand, color: '#fff', borderColor: brand } : { borderColor: '#e2e8f0', background: '#fff' }}>
                      {t}
                    </button>
                  )
                })}
              </div>
            </div>

            <div className="grid sm:grid-cols-2 gap-3">
              <input className="rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400" placeholder="نام و نام خانوادگی" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
              <input className="rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400" placeholder="شماره موبایل" dir="ltr" value={form.mobile} onChange={(e) => setForm((f) => ({ ...f, mobile: e.target.value }))} />
            </div>
            <input className="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400" placeholder="ایمیل (اختیاری)" dir="ltr" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} />
            <textarea className="w-full min-h-[80px] rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-slate-400" placeholder="توضیحات (اختیاری)" value={form.message} onChange={(e) => setForm((f) => ({ ...f, message: e.target.value }))} />

            {error && <p className="text-sm text-red-500">{error}</p>}

            <button onClick={submit} disabled={mutation.isPending}
              className="w-full inline-flex items-center justify-center gap-2 rounded-xl py-3 font-semibold text-white disabled:opacity-60" style={{ background: brand }}>
              <Send className="h-4 w-4" /> {mutation.isPending ? 'در حال ارسال…' : 'ثبت درخواست بازدید'}
            </button>
          </div>
        )}
      </div>
    </div>
  )
}

function PostReaderModal({ post, brand, onClose }: { post: Post; brand: string; onClose: () => void }) {
  return (
    <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4" onClick={onClose}>
      <div className="w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto rounded-t-3xl sm:rounded-2xl bg-white text-slate-800" onClick={(e) => e.stopPropagation()}>
        <div className="sticky top-0 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-4">
          <h3 className="font-bold flex items-center gap-2"><Newspaper className="h-5 w-5" style={{ color: brand }} /> مقاله</h3>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-700"><X className="h-5 w-5" /></button>
        </div>
        <article className="p-6">
          <div className="flex items-center gap-4 text-xs text-slate-400 mb-3">
            {post.created_at && <span className="flex items-center gap-1"><CalendarDays className="h-3.5 w-3.5" />{formatJalaliDate(post.created_at)}</span>}
            {typeof post.views === 'number' && <span className="flex items-center gap-1"><Eye className="h-3.5 w-3.5" />{toPersianDigits(String(post.views))} بازدید</span>}
          </div>
          <h1 className="text-2xl font-black text-slate-900 leading-snug">{post.title}</h1>
          {post.excerpt && <p className="mt-3 text-slate-500 leading-relaxed">{post.excerpt}</p>}
          {post.body && (
            <div className="mt-4 text-slate-700 leading-8 whitespace-pre-line">{post.body}</div>
          )}
        </article>
      </div>
    </div>
  )
}
