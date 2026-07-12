import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { useQuery } from '@tanstack/react-query'
import {
  Building2,
  Smartphone,
  Monitor,
  Shield,
  Users,
  Search,
  BarChart3,
  Kanban,
  Calculator,
  ArrowLeft,
  CheckCircle2,
  Sparkles,
  BookOpen,
  BadgeCheck,
} from 'lucide-react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { SeoHead } from '@/components/seo/SeoHead'
import { getOrganizationJsonLd, getSoftwareJsonLd } from '@/lib/seo'

const features = [
  { icon: Building2, title: 'ثبت حرفه‌ای املاک', desc: 'کد یکتا، نوع معامله، موقعیت، تصاویر و جزئیات کامل هر ملک' },
  { icon: Search, title: 'جستجوی پیشرفته', desc: 'فیلتر قیمت، متراژ، محله و ذخیره جستجوهای پرتکرار' },
  { icon: Kanban, title: 'قیف فروش CRM', desc: 'مدیریت مخاطبین، معاملات و مراحل فروش در یک نگاه' },
  { icon: Calculator, title: 'حسابداری دفتر', desc: 'درآمد، هزینه و تراکنش‌های مالی دفتر املاک' },
  { icon: Users, title: 'تیم و دسترسی', desc: 'دعوت مشاوران، نقش‌ها و کنترل سطح دسترسی' },
  { icon: Shield, title: 'امنیت ابری', desc: 'ورود OTP، توکن امن و جداسازی داده هر دفتر' },
]

const platforms = [
  { icon: Monitor, label: 'نسخه وب', desc: 'مرورگر — بدون نصب', badge: 'web' },
  { icon: Smartphone, label: 'اندروید', desc: 'APK و انتشار کافه‌بازار', badge: 'mobile' },
  { icon: Monitor, label: 'ویندوز', desc: 'دسکتاپ برای دفتر', badge: 'desktop' },
]

const featureLabels: Record<string, string> = {
  filing: 'فایلینگ حرفه‌ای',
  accounting: 'حسابداری دفتر',
  team: 'مدیریت تیم',
  telegram_bot: 'ربات تلگرام',
  whatsapp_bot: 'ربات واتساپ',
  website_listing: 'نمایش در وبسایت',
  verified_badge: 'تیک وریفای',
  crm: 'CRM',
}

export function LandingPage() {
  const { data: apiPlans } = useQuery({
    queryKey: ['plans'],
    queryFn: async () => {
      const res = await api.get('/plans')
      return res.data.data as {
        id: number
        slug: string
        name: string
        description?: string
        monthly_price: number
        trial_days: number
        max_users: number
        max_properties: number
        features: string[]
        panel_type: string
      }[]
    },
  })

  const { data: consultants } = useQuery({
    queryKey: ['consultants'],
    queryFn: async () => {
      const res = await api.get('/consultants')
      return res.data.data as {
        id: number
        name: string
        city?: string
        logo_url?: string
        is_verified: boolean
        description?: string
      }[]
    },
  })

  const displayPlans = apiPlans ?? []
  return (
    <div className="min-h-screen bg-background text-foreground overflow-x-hidden">
      <SeoHead
        title="سامانه ابری ثبت و مدیریت املاک"
        description="پوشه — نرم افزار CRM املاک برای مشاوران و آژانس‌ها. ثبت ملک، قیف فروش، حسابداری، مدیریت تیم. وب، اندروید و ویندوز."
        keywords="نرم افزار املاک, سامانه ثبت ملک, CRM املاک, مدیریت دفتر املاک, نرم افزار مشاور املاک"
        path="/"
        jsonLd={[getOrganizationJsonLd(), getSoftwareJsonLd()]}
      />
      <div className="pointer-events-none fixed inset-0 -z-10">
        <div className="absolute -top-32 right-0 h-[500px] w-[500px] rounded-full bg-primary/20 blur-[120px]" />
        <div className="absolute bottom-0 left-0 h-[400px] w-[400px] rounded-full bg-accent/15 blur-[100px]" />
      </div>

      <header className="sticky top-0 z-50 border-b border-card-border glass">
        <div className="container mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-accent">
              <Building2 className="h-5 w-5 text-white" />
            </div>
            <span className="text-lg font-bold gradient-text">پوشه</span>
          </div>
          <nav className="hidden md:flex items-center gap-6 text-sm text-muted">
            <a href="#features" className="hover:text-foreground transition-colors">امکانات</a>
            <a href="#platforms" className="hover:text-foreground transition-colors">پلتفرم‌ها</a>
            <a href="#pricing" className="hover:text-foreground transition-colors">تعرفه</a>
            <Link to="/blog" className="hover:text-foreground transition-colors">وبلاگ</Link>
            <Link to="/download" className="hover:text-foreground transition-colors">دانلود</Link>
          </nav>
          <div className="flex items-center gap-2">
            <Link to="/login">
              <Button variant="ghost" size="sm">ورود</Button>
            </Link>
            <Link to="/register">
              <Button size="sm">
                شروع رایگان
                <ArrowLeft className="h-4 w-4" />
              </Button>
            </Link>
          </div>
        </div>
      </header>

      <section className="container mx-auto max-w-6xl px-4 pt-20 pb-24 text-center">
        <motion.div initial={{ opacity: 0, y: 24 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5 }}>
          <div className="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-4 py-1.5 text-sm text-primary mb-6">
            <Sparkles className="h-4 w-4" />
            سامانه ابری مدیریت املاک
          </div>
          <h1 className="text-4xl md:text-6xl font-bold leading-tight mb-6">
            <span className="gradient-text">پوشه</span>
            <br />
            <span className="text-foreground">مدیریت هوشمند دفتر املاک شما</span>
          </h1>
          <p className="text-lg text-muted max-w-2xl mx-auto mb-10 leading-relaxed">
            ثبت ملک، جستجو، CRM، حسابداری و مدیریت تیم — همه در یک پلتفرم ابری امن.
            برای مشاوران املاک، مدیران دفتر و آژانس‌های حرفه‌ای.
          </p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <Link to="/register">
              <Button size="lg" className="min-w-[200px]">
                ثبت‌نام رایگان
                <ArrowLeft className="h-4 w-4" />
              </Button>
            </Link>
            <a href="#features">
              <Button variant="outline" size="lg" className="min-w-[200px]">
                مشاهده امکانات
              </Button>
            </a>
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 40 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2, duration: 0.6 }}
          className="mt-16 mx-auto max-w-4xl rounded-2xl border border-card-border glass p-2 shadow-2xl shadow-primary/10"
        >
          <div className="rounded-xl bg-card p-6 md:p-8 text-right border border-card-border">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
              {[
                { label: 'کل املاک', value: '۱۲۴' },
                { label: 'فعال', value: '۹۸' },
                { label: 'معامله باز', value: '۱۵' },
                { label: 'اعضای تیم', value: '۸' },
              ].map((s) => (
                <div key={s.label} className="rounded-xl bg-white/5 p-4 border border-card-border">
                  <p className="text-2xl font-bold text-primary">{s.value}</p>
                  <p className="text-xs text-muted mt-1">{s.label}</p>
                </div>
              ))}
            </div>
            <div className="space-y-2">
              {['ملک ۱۲۴۰ — فروش آپارتمان ولنجک', 'ملک ۱۲۳۸ — اجاره دفتر ونک', 'ملک ۱۲۳۵ — رهن مغازه سعادت‌آباد'].map((item) => (
                <div key={item} className="flex items-center justify-between rounded-lg bg-white/5 px-4 py-3 text-sm">
                  <span>{item}</span>
                  <span className="text-xs text-success">فعال</span>
                </div>
              ))}
            </div>
          </div>
        </motion.div>
      </section>

      <section id="features" className="container mx-auto max-w-6xl px-4 py-20">
        <div className="text-center mb-12">
          <h2 className="text-3xl font-bold mb-3">همه‌چیز برای دفتر املاک مدرن</h2>
          <p className="text-muted">ابزارهایی که هر روز به آن‌ها نیاز دارید</p>
        </div>
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
          {features.map((f, i) => (
            <motion.div
              key={f.title}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.05 }}
            >
              <Card className="h-full p-6 glass-hover">
                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/15 text-primary mb-4">
                  <f.icon className="h-6 w-6" />
                </div>
                <h3 className="font-semibold text-lg mb-2">{f.title}</h3>
                <p className="text-sm text-muted leading-relaxed">{f.desc}</p>
              </Card>
            </motion.div>
          ))}
        </div>
      </section>

      <section id="platforms" className="container mx-auto max-w-6xl px-4 py-20">
        <div className="text-center mb-12">
          <h2 className="text-3xl font-bold mb-3">هر جا که هستید</h2>
          <p className="text-muted">وب، موبایل و دسکتاپ — یک حساب، همه دستگاه‌ها</p>
        </div>
        <div className="grid md:grid-cols-3 gap-6">
          {platforms.map((p) => (
            <Card key={p.label} className="p-6 text-center glass-hover">
              <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/20 to-accent/20">
                <p.icon className="h-7 w-7 text-primary" />
              </div>
              <h3 className="font-semibold text-lg">{p.label}</h3>
              <p className="text-sm text-muted mt-1">{p.desc}</p>
              {p.badge !== 'web' && (
                <Link to="/download" className="inline-block mt-4 text-sm text-primary hover:underline">
                  دانلود
                </Link>
              )}
            </Card>
          ))}
        </div>
        <div className="text-center mt-8">
          <Link to="/download">
            <Button variant="outline">صفحه دانلود همه نسخه‌ها</Button>
          </Link>
        </div>
      </section>

      <section id="pricing" className="container mx-auto max-w-6xl px-4 py-20">
        <div className="text-center mb-12">
          <h2 className="text-3xl font-bold mb-3">سه پنل، یک سامانه</h2>
          <p className="text-muted">۳ روز تست رایگان برای همه پلن‌ها</p>
        </div>
        <div className="grid md:grid-cols-3 gap-6">
          {displayPlans.map((plan, idx) => (
            <Card
              key={plan.id}
              className={`p-6 flex flex-col ${idx === 1 ? 'border-primary/50 ring-1 ring-primary/30' : ''}`}
            >
              {idx === 1 && (
                <span className="self-start text-xs font-medium text-primary bg-primary/15 px-2 py-1 rounded-full mb-3">
                  پرطرفدار
                </span>
              )}
              <h3 className="text-xl font-bold">{plan.name}</h3>
              <p className="text-2xl font-bold mt-2 gradient-text">{formatPrice(plan.monthly_price)}</p>
              <p className="text-sm text-muted mb-2">ماهانه · {plan.trial_days} روز رایگان</p>
              <p className="text-sm text-muted mb-4">{plan.description}</p>
              <ul className="space-y-2 flex-1">
                <li className="flex items-center gap-2 text-sm">
                  <CheckCircle2 className="h-4 w-4 text-success shrink-0" />
                  تا {plan.max_users} کاربر · {plan.max_properties} ملک
                </li>
                {plan.features.slice(0, 4).map((f) => (
                  <li key={f} className="flex items-center gap-2 text-sm">
                    <CheckCircle2 className="h-4 w-4 text-success shrink-0" />
                    {featureLabels[f] || f}
                  </li>
                ))}
              </ul>
              <Link to="/register" className="mt-6">
                <Button className="w-full" variant={idx === 1 ? 'default' : 'outline'}>
                  شروع رایگان
                </Button>
              </Link>
            </Card>
          ))}
        </div>
      </section>

      {consultants && consultants.length > 0 && (
        <section id="consultants" className="container mx-auto max-w-6xl px-4 py-20">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-3">مشاورین املاک ما</h2>
            <p className="text-muted">دفاتر حرفه‌ای با تیک وریفای در پوشه</p>
          </div>
          <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {consultants.map((c) => (
              <Card key={c.id} className="p-5 text-center glass-hover">
                {c.logo_url ? (
                  <img src={c.logo_url} alt="" className="h-14 w-14 rounded-xl mx-auto mb-3 object-cover" />
                ) : (
                  <div className="h-14 w-14 rounded-xl bg-primary/15 mx-auto mb-3 flex items-center justify-center">
                    <Building2 className="h-6 w-6 text-primary" />
                  </div>
                )}
                <h3 className="font-semibold flex items-center justify-center gap-1">
                  {c.name}
                  {c.is_verified && <BadgeCheck className="h-4 w-4 text-primary" />}
                </h3>
                <p className="text-xs text-muted mt-1">{c.city}</p>
                {c.description && <p className="text-xs text-muted mt-2 line-clamp-2">{c.description}</p>}
              </Card>
            ))}
          </div>
        </section>
      )}

      <section className="container mx-auto max-w-6xl px-4 py-16">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h2 className="text-2xl font-bold flex items-center gap-2">
              <BookOpen className="h-6 w-6 text-primary" />
              وبلاگ تخصصی املاک
            </h2>
            <p className="text-muted mt-1">راهنماها برای رشد دفتر املاک شما</p>
          </div>
          <Link to="/blog"><Button variant="outline">همه مقالات</Button></Link>
        </div>
        <div className="grid md:grid-cols-3 gap-4">
          {[
            { title: 'بهترین CRM املاک ایران', slug: 'best-real-estate-crm-software-iran' },
            { title: 'نکات ثبت حرفه‌ای ملک', slug: 'property-filing-tips-for-agents' },
            { title: 'سامانه ابری یا اکسل؟', slug: 'cloud-vs-excel-real-estate-management' },
          ].map((item) => (
            <Link key={item.slug} to={`/blog/${item.slug}`}>
              <Card className="p-5 h-full glass-hover">
                <h3 className="font-semibold">{item.title}</h3>
                <p className="text-sm text-primary mt-2">مطالعه مقاله ←</p>
              </Card>
            </Link>
          ))}
        </div>
      </section>

      <section className="container mx-auto max-w-6xl px-4 py-20">
        <Card className="p-10 md:p-14 text-center bg-gradient-to-br from-primary/10 to-accent/10 border-primary/20">
          <BarChart3 className="h-12 w-12 text-primary mx-auto mb-4" />
          <h2 className="text-2xl md:text-3xl font-bold mb-3">آماده تحول دفتر املاک خود هستید؟</h2>
          <p className="text-muted max-w-xl mx-auto mb-8">
            همین حالا وارد شوید و در کمتر از ۵ دقیقه اولین ملک خود را ثبت کنید.
          </p>
          <Link to="/register">
            <Button size="lg">
              ثبت‌نام رایگان
              <ArrowLeft className="h-4 w-4" />
            </Button>
          </Link>
        </Card>
      </section>

      <footer className="border-t border-card-border py-10">
        <div className="container mx-auto max-w-6xl px-4 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-muted">
          <div className="flex items-center gap-2">
            <Building2 className="h-5 w-5 text-primary" />
            <span>پوشه — سامانه ابری ثبت و مدیریت املاک</span>
          </div>
          <div className="flex items-center gap-4">
            <Link to="/terms" className="hover:text-foreground transition-colors">قوانین</Link>
            <Link to="/privacy" className="hover:text-foreground transition-colors">حریم خصوصی</Link>
          </div>
          <p>© {new Date().getFullYear()} تمامی حقوق محفوظ است.</p>
        </div>
      </footer>
    </div>
  )
}
