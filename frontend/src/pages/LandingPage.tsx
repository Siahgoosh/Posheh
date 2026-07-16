import { useState } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { useQuery } from '@tanstack/react-query'
import {
  Building2, Smartphone, Shield, Users, Search, BarChart3, Kanban, Calculator,
  ArrowLeft, CheckCircle2, Sparkles, Calendar, QrCode, UserCircle, Target,
  FileText, MessageSquare, Zap, Cloud, Headphones, ChevronDown,
  Wallet, Bot, Globe, Star,
} from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { SeoHead } from '@/components/seo/SeoHead'
import { getOrganizationJsonLd, getSoftwareJsonLd } from '@/lib/seo'
import { PLAN_FEATURE_LABELS, trialBadgeForPlan } from '@/constants/plans'

const stats = [
  { value: '+۸۰', label: 'دفتر فعال' },
  { value: '۹۹.۹٪', label: 'پایداری' },
  { value: '<۳ دقیقه', label: 'راه‌اندازی' },
  { value: '۲۴/۷', label: 'پشتیبانی' },
]

const categories = [
  { icon: Building2, title: 'مدیریت املاک', count: 6, color: 'from-emerald-500/20 to-emerald-600/5' },
  { icon: Kanban, title: 'CRM و فروش', count: 5, color: 'from-amber-500/20 to-amber-600/5' },
  { icon: Wallet, title: 'مالی و کمیسیون', count: 4, color: 'from-teal-500/20 to-teal-600/5' },
  { icon: Zap, title: 'هوش و اتوماسیون', count: 5, color: 'from-lime-500/20 to-lime-600/5' },
]

const allFeatures = [
  { icon: Building2, title: 'فایلینگ ۳۰+ فیلد', desc: 'ثبت ملک حرفه‌ای با گالری، نقشه و QR' },
  { icon: UserCircle, title: 'بانک مالکین', desc: 'پروفایل مالک، کدملی و تاریخچه فایل‌ها' },
  { icon: Users, title: 'مدیریت مشتریان', desc: 'پروفایل VIP، یادداشت و تاریخچه بازدید' },
  { icon: Calendar, title: 'تقویم شمسی بازدید', desc: 'زمان‌بندی بازدید با یادآور SMS' },
  { icon: Kanban, title: 'پایپ‌لاین کانبان', desc: 'مدیریت معاملات با درگ‌اند‌دراپ' },
  { icon: Target, title: 'تطبیق هوشمند', desc: 'Match ملک و مشتری بر اساس بودجه و منطقه' },
  { icon: FileText, title: 'قرارداد PDF فارسی', desc: 'مبایعه‌نامه و اجاره‌نامه با تاریخ شمسی' },
  { icon: Calculator, title: 'حسابداری دفتر', desc: 'درآمد، هزینه و گزارش مالی' },
  { icon: QrCode, title: 'کد QR رهگیری', desc: 'QR اختصاصی برای هر فایل ملک' },
  { icon: MessageSquare, title: 'اشتراک واتساپ/تلگرام', desc: 'ارسال فایل ملک برای شماره مشخص با یک کلیک' },
  { icon: Sparkles, title: 'کپی آگهی هوشمند', desc: 'تولید متن آماده انتشار برای دیوار و شبکه‌ها' },
  { icon: Star, title: 'امتیاز کیفیت فایل', desc: 'نمره‌دهی خودکار کامل بودن اطلاعات ملک' },
  { icon: Bot, title: 'ربات تلگرام/واتساپ', desc: 'پاسخگویی خودکار به مشتریان' },
  { icon: Globe, title: 'سایت اختصاصی دفتر', desc: 'صفحه عمومی املاک با URL اختصاصی' },
  { icon: BarChart3, title: 'گزارش KPI', desc: 'نمودار درآمد و عملکرد مشاوران' },
  { icon: Shield, title: 'نقش و دسترسی', desc: 'کنترل دقیق سطح دسترسی هر مشاور' },
  { icon: Search, title: 'جستجوی پیشرفته', desc: 'فیلتر قیمت، متراژ، محله و نوع' },
  { icon: Cloud, title: 'ابری و امن', desc: 'بک‌آپ، OTP و جداسازی داده هر دفتر' },
]

const steps = [
  { n: '۱', title: 'ثبت‌نام و انتخاب پلن', desc: 'پنل فردی: ۴۸ ساعت رایگان — بدون کارت بانکی' },
  { n: '۲', title: 'راه‌اندازی خودکار', desc: 'پنل اختصاصی دفتر شما در کمتر از ۳ دقیقه آماده می‌شود' },
  { n: '۳', title: 'شروع فایلینگ', desc: 'مشاوران را دعوت کنید و اولین ملک را ثبت کنید' },
]

const testimonials = [
  { name: 'ر. کریمی', role: 'مدیر دفتر، تهران', text: 'قبلاً همه‌چیز روی اکسل بود. حالا فایل‌ها، مشتری‌ها و قراردادها یکجاست.' },
  { name: 'س. موسوی', role: 'مشاور ارشد، مشهد', text: 'تطبیق هوشمند ملک و مشتری واقعاً وقت ما را نصف کرد.' },
  { name: 'م. احمدی', role: 'مدیر آژانس، اصفهان', text: 'تقویم بازدید و QR کد برای کار روزانه مشاوران عالی است.' },
]

const faqs = [
  { q: 'بعد از دوره آزمایشی خودکار پول کم می‌شود؟', a: 'خیر. تا زمانی که خودتان پلن بخرید، هیچ پرداختی انجام نمی‌شود.' },
  { q: 'چقدر طول می‌کشد پنل آماده شود؟', a: 'معمولاً کمتر از ۳ دقیقه پس از ثبت‌نام.' },
  { q: 'نیاز به نصب دارد؟', a: 'خیر. کاملاً ابری و تحت‌وب — از مرورگر و موبایل.' },
  { q: 'چند مشاور هم‌زمان کار می‌کنند؟', a: 'بسته به پلن: ۱، ۵ یا ۱۰ مشاور با امکانات متفاوت.' },
]

export function LandingPage() {
  const [openFaq, setOpenFaq] = useState<number | null>(0)

  const { data: apiPlans } = useQuery({
    queryKey: ['plans'],
    queryFn: async () => {
      const res = await api.get('/plans')
      return res.data.data as {
        id: number; slug: string; name: string; description?: string
        monthly_price: number; trial_days: number; max_users: number
        max_properties: number; features: string[]
      }[]
    },
  })

  return (
    <div className="min-h-screen bg-background text-foreground overflow-x-hidden">
      <SeoHead
        title="پوشه — سامانه ابری مدیریت دفتر املاک"
        description="فایلینگ، CRM، تطبیق هوشمند، تقویم بازدید، QR و حسابداری — همه در یک پنل ابری برای مشاوران املاک ایران."
        keywords="نرم افزار املاک, CRM املاک, فایلینگ املاک, پوشه, سامانه مشاور املاک"
        path="/"
        jsonLd={[getOrganizationJsonLd(), getSoftwareJsonLd()]}
      />

      <div className="landing-glow pointer-events-none fixed inset-0 -z-10" />
      <div className="pointer-events-none fixed inset-0 -z-10">
        <div className="absolute top-20 left-10 h-72 w-72 rounded-full bg-accent/10 blur-[100px]" />
        <div className="absolute bottom-20 right-10 h-96 w-96 rounded-full bg-primary/10 blur-[120px]" />
      </div>

      <header className="sticky top-0 z-50 border-b border-card-border glass">
        <div className="container mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-accent shadow-lg shadow-primary/20">
              <Building2 className="h-5 w-5 text-primary-foreground" />
            </div>
            <span className="text-xl font-bold gradient-text">پوشه</span>
          </div>
          <nav className="hidden md:flex items-center gap-6 text-sm text-muted">
            <a href="#features" className="hover:text-primary transition-colors">امکانات</a>
            <a href="#steps" className="hover:text-primary transition-colors">شروع سریع</a>
            <a href="#pricing" className="hover:text-primary transition-colors">تعرفه</a>
            <a href="#faq" className="hover:text-primary transition-colors">سوالات</a>
            <Link to="/blog" className="hover:text-primary transition-colors">وبلاگ</Link>
          </nav>
          <div className="flex items-center gap-2">
            <Link to="/login"><Button variant="ghost" size="sm">ورود</Button></Link>
            <Link to="/register">
              <Button size="sm" className="shadow-lg shadow-primary/25">
                شروع رایگان <ArrowLeft className="h-4 w-4" />
              </Button>
            </Link>
          </div>
        </div>
      </header>

      {/* Hero */}
      <section className="container mx-auto max-w-6xl px-4 pt-16 pb-20">
        <div className="grid lg:grid-cols-2 gap-12 items-center">
          <motion.div initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }}>
            <div className="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-4 py-1.5 text-sm text-primary mb-6">
              <Sparkles className="h-4 w-4" />
              راه‌اندازی در کمتر از ۳ دقیقه
            </div>
            <h1 className="text-4xl md:text-5xl font-bold leading-tight mb-5">
              با <span className="gradient-text">پوشه</span> بیشتر بفروشید و دفترتان را حرفه‌ای مدیریت کنید
            </h1>
            <p className="text-muted text-lg leading-relaxed mb-8">
              فایلینگ هوشمند، CRM کانبان، تطبیق ملک↔مشتری، تقویم بازدید شمسی، QR و قرارداد PDF —
              بدون نصب و بدون برنامه‌نویس.
            </p>
            <div className="flex flex-wrap gap-3 mb-6">
              <Link to="/register">
                <Button size="lg" className="shadow-lg shadow-primary/30">شروع ۴۸ ساعت رایگان</Button>
              </Link>
              <a href="#pricing"><Button variant="outline" size="lg">مشاهده پلن‌ها</Button></a>
            </div>
            <div className="flex flex-wrap gap-4 text-xs text-muted">
              {['بدون کارت بانکی', 'لغو هر زمان', 'پشتیبانی فارسی'].map((t) => (
                <span key={t} className="flex items-center gap-1"><CheckCircle2 className="h-3.5 w-3.5 text-primary" />{t}</span>
              ))}
            </div>
          </motion.div>

          <motion.div initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.15 }}
            className="rounded-2xl border border-primary/20 glass p-4 shadow-2xl shadow-primary/10">
            <div className="rounded-xl bg-background/80 border border-card-border p-5 space-y-4">
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted">posheapp.ir/dashboard</span>
                <span className="text-primary text-xs">● آنلاین</span>
              </div>
              <div className="grid grid-cols-2 gap-3">
                {[
                  { l: 'فایل فعال', v: '۲۴۸' }, { l: 'سرنخ ماه', v: '۳۱' },
                  { l: 'بازدید امروز', v: '۸' }, { l: 'قرارداد', v: '۱۲' },
                ].map((s) => (
                  <div key={s.l} className="rounded-lg bg-primary/5 border border-primary/10 p-3">
                    <p className="text-xl font-bold text-primary">{s.v}</p>
                    <p className="text-[10px] text-muted">{s.l}</p>
                  </div>
                ))}
              </div>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between rounded-lg bg-accent/10 border border-accent/20 px-3 py-2">
                  <span>تطبیق هوشمند</span><span className="text-accent text-xs">۳ ملک منطبق</span>
                </div>
                <div className="flex justify-between rounded-lg bg-primary/5 px-3 py-2">
                  <span>بازدید ۱۴:۳۰ — آپارتمان سعادت‌آباد</span>
                  <Calendar className="h-4 w-4 text-primary" />
                </div>
              </div>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Stats */}
      <section className="border-y border-card-border bg-primary/5">
        <div className="container mx-auto max-w-6xl px-4 py-10 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
          {stats.map((s) => (
            <div key={s.label}>
              <p className="text-2xl md:text-3xl font-bold gradient-text">{s.value}</p>
              <p className="text-sm text-muted mt-1">{s.label}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Categories */}
      <section id="features" className="container mx-auto max-w-6xl px-4 py-20">
        <div className="text-center mb-12">
          <p className="text-primary text-sm font-medium mb-2">امکانات کامل</p>
          <h2 className="text-3xl font-bold">هر چه برای دفتر مدرن لازم است — در یک پنل</h2>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-12">
          {categories.map((c) => (
            <Card key={c.title} className={`p-6 bg-gradient-to-br ${c.color} glass-hover`}>
              <c.icon className="h-8 w-8 text-primary mb-3" />
              <h3 className="font-semibold">{c.title}</h3>
              <p className="text-xs text-muted mt-1">{c.count} امکان</p>
            </Card>
          ))}
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {allFeatures.map((f, i) => (
            <motion.div key={f.title} initial={{ opacity: 0, y: 12 }} whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }} transition={{ delay: i * 0.03 }}>
              <Card className="p-5 h-full glass-hover">
                <f.icon className="h-5 w-5 text-primary mb-2" />
                <h3 className="font-medium text-sm mb-1">{f.title}</h3>
                <p className="text-xs text-muted leading-relaxed">{f.desc}</p>
              </Card>
            </motion.div>
          ))}
        </div>
      </section>

      {/* Steps */}
      <section id="steps" className="container mx-auto max-w-6xl px-4 py-20">
        <div className="text-center mb-12">
          <h2 className="text-3xl font-bold">فقط ۳ دقیقه تا پنل آماده</h2>
        </div>
        <div className="grid md:grid-cols-3 gap-6">
          {steps.map((s) => (
            <Card key={s.n} className="p-6 text-center glass-hover relative">
              <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-primary to-accent text-primary-foreground font-bold text-lg">
                {s.n}
              </div>
              <h3 className="font-semibold mb-2">{s.title}</h3>
              <p className="text-sm text-muted">{s.desc}</p>
            </Card>
          ))}
        </div>
      </section>

      {/* Mobile */}
      <section className="container mx-auto max-w-6xl px-4 py-16">
        <div className="grid lg:grid-cols-2 gap-10 items-center">
          <div>
            <h2 className="text-2xl font-bold mb-4 flex items-center gap-2">
              <Smartphone className="h-6 w-6 text-primary" /> اپ موبایل و PWA
            </h2>
            <ul className="space-y-3 text-sm text-muted">
              {['ورود با OTP', 'همگام با پنل وب', 'فایلینگ و بازدید از موبایل', 'بدون محدودیت نصب'].map((t) => (
                <li key={t} className="flex items-center gap-2"><CheckCircle2 className="h-4 w-4 text-primary shrink-0" />{t}</li>
              ))}
            </ul>
            <div className="flex flex-wrap gap-3 mt-6">
              <Link to="/download"><Button><Smartphone className="h-4 w-4 ml-1" /> دانلود اندروید و ویندوز</Button></Link>
            </div>
          </div>
          <Card className="p-6 max-w-xs mx-auto glass border-primary/20">
            <div className="text-center space-y-4">
              <div className="text-xs text-muted">پوشه موبایل</div>
              <div className="grid grid-cols-3 gap-2 text-center">
                {[{ l: 'فایل', v: '۲۴' }, { l: 'بازدید', v: '۸' }, { l: 'CRM', v: '۵' }].map((x) => (
                  <div key={x.l} className="rounded-lg bg-primary/10 p-2">
                    <p className="font-bold text-primary">{x.v}</p>
                    <p className="text-[10px] text-muted">{x.l}</p>
                  </div>
                ))}
              </div>
              <div className="text-left space-y-1 text-xs">
                <p className="text-muted">بازدیدهای امروز</p>
                <p>۰۹:۳۰ — آپارتمان ولنجک</p>
                <p>۱۴:۰۰ — ویلا زعفرانیه</p>
              </div>
            </div>
          </Card>
        </div>
      </section>

      {/* Pricing */}
      <section id="pricing" className="container mx-auto max-w-6xl px-4 py-20">
        <div className="text-center mb-12">
          <h2 className="text-3xl font-bold mb-2">پلن مناسب خود را انتخاب کنید</h2>
          <p className="text-muted">پنل مشاور مستقل: ۴۸ ساعت رایگان — پلن‌های دفتر از ابتدا پرداختی</p>
        </div>
        <div className="grid md:grid-cols-3 gap-6">
          {(apiPlans ?? []).map((plan, idx) => (
            <Card key={plan.id} className={`p-6 flex flex-col ${idx === 1 ? 'border-primary/50 ring-2 ring-primary/20 scale-[1.02]' : ''}`}>
              {idx === 1 && <span className="self-start text-xs bg-accent/20 text-accent px-2 py-0.5 rounded-full mb-2">★ محبوب</span>}
              <h3 className="text-xl font-bold">{plan.name}</h3>
              <p className="text-2xl font-bold mt-2 gradient-text">{plan.monthly_price ? formatPrice(plan.monthly_price) : 'رایگان'}</p>
              <p className="text-sm text-muted mb-4">{plan.description}</p>
              <ul className="space-y-2 flex-1 text-sm">
                <li className="flex gap-2"><CheckCircle2 className="h-4 w-4 text-primary shrink-0" />تا {plan.max_users} کاربر · {plan.max_properties} ملک</li>
                {trialBadgeForPlan(plan.slug) && (
                  <li className="flex gap-2 text-warning"><CheckCircle2 className="h-4 w-4 shrink-0" />{trialBadgeForPlan(plan.slug)}</li>
                )}
                {plan.features.slice(0, 4).map((f) => (
                  <li key={f} className="flex gap-2"><CheckCircle2 className="h-4 w-4 text-primary shrink-0" />{PLAN_FEATURE_LABELS[f] || f}</li>
                ))}
              </ul>
              <Link to="/register" className="mt-6"><Button className="w-full" variant={idx === 1 ? 'default' : 'outline'}>شروع رایگان</Button></Link>
            </Card>
          ))}
        </div>
      </section>

      {/* Testimonials */}
      <section className="container mx-auto max-w-6xl px-4 py-16">
        <h2 className="text-2xl font-bold text-center mb-10">تجربه مشاوران و مدیران</h2>
        <div className="grid md:grid-cols-3 gap-6">
          {testimonials.map((t) => (
            <Card key={t.name} className="p-6 glass-hover">
              <p className="text-sm text-muted leading-relaxed mb-4">«{t.text}»</p>
              <p className="font-semibold text-sm">{t.name}</p>
              <p className="text-xs text-primary">{t.role}</p>
            </Card>
          ))}
        </div>
      </section>

      {/* FAQ */}
      <section id="faq" className="container mx-auto max-w-3xl px-4 py-16">
        <h2 className="text-2xl font-bold text-center mb-8">سوالات پرتکرار</h2>
        <div className="space-y-3">
          {faqs.map((f, i) => (
            <Card key={i} className="overflow-hidden">
              <button type="button" className="w-full flex items-center justify-between p-4 text-right text-sm font-medium"
                onClick={() => setOpenFaq(openFaq === i ? null : i)}>
                {f.q}
                <ChevronDown className={`h-4 w-4 shrink-0 transition-transform ${openFaq === i ? 'rotate-180' : ''}`} />
              </button>
              {openFaq === i && <p className="px-4 pb-4 text-sm text-muted">{f.a}</p>}
            </Card>
          ))}
        </div>
      </section>

      {/* CTA */}
      <section className="container mx-auto max-w-6xl px-4 py-16">
        <Card className="p-10 md:p-14 text-center bg-gradient-to-br from-primary/15 to-accent/10 border-primary/30">
          <Headphones className="h-10 w-10 text-primary mx-auto mb-4" />
          <h2 className="text-2xl md:text-3xl font-bold mb-3">همین امروز دفترتان را حرفه‌ای کنید</h2>
          <p className="text-muted max-w-lg mx-auto mb-8">ثبت‌نام رایگان — راه‌اندازی زیر ۳ دقیقه — پشتیبانی فارسی</p>
          <Link to="/register"><Button size="lg" className="shadow-lg shadow-primary/30">شروع رایگان <ArrowLeft className="h-4 w-4" /></Button></Link>
        </Card>
      </section>

      <footer className="border-t border-card-border py-10">
        <div className="container mx-auto max-w-6xl px-4 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-muted">
          <div className="flex items-center gap-2">
            <Building2 className="h-5 w-5 text-primary" />
            <span>پوشه — سامانه ابری مدیریت املاک</span>
          </div>
          <div className="flex gap-4">
            <Link to="/terms" className="hover:text-primary">قوانین</Link>
            <Link to="/privacy" className="hover:text-primary">حریم خصوصی</Link>
            <Link to="/download" className="hover:text-primary">دانلود</Link>
          </div>
          <p>© {new Date().getFullYear()} تمامی حقوق محفوظ است.</p>
        </div>
      </footer>
    </div>
  )
}
