import { Link } from 'react-router-dom'
import { ArrowRight } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { SeoHead } from '@/components/seo/SeoHead'
import { SiteFooter } from '@/components/layout/SiteFooter'
import { SITE_CONTACT } from '@/constants/site'
import { getOrganizationJsonLd } from '@/lib/seo'

export function AboutPage() {
  return (
    <div className="min-h-screen bg-background text-foreground flex flex-col">
      <SeoHead
        title="درباره پوشه"
        description="پوشه سامانه ابری مدیریت دفتر املاک است — فایلینگ، CRM، بازدید و ابزارهای دفتر در یک پنل."
        path="/about"
        jsonLd={getOrganizationJsonLd()}
      />
      <header className="border-b border-card-border glass">
        <div className="container mx-auto flex h-16 max-w-3xl items-center justify-between px-4">
          <Link to="/" className="text-sm text-muted hover:text-primary">صفحه اصلی</Link>
          <Link to="/contact"><Button size="sm" variant="outline">تماس</Button></Link>
        </div>
      </header>
      <main className="container mx-auto max-w-3xl px-4 py-12 space-y-6 flex-1">
        <div className="flex items-center gap-3">
          <Link to="/"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
          <h1 className="text-2xl font-bold">درباره پوشه</h1>
        </div>
        <p className="text-muted leading-relaxed">
          پوشه یک سامانه ابری برای مشاوران و دفاتر املاک است. هدف ما ساده‌کردن فایلینگ، پیگیری مشتری، بازدید و کارهای روزمره دفتر است — بدون ادعای «بهترین در ایران» و بدون آمار ساختگی.
        </p>
        <ul className="list-disc pr-5 space-y-2 text-sm text-muted">
          <li>ثبت و جستجوی ملک</li>
          <li>CRM و قیف فروش</li>
          <li>هماهنگی بازدید</li>
          <li>ماژول‌های مکمل مثل حسابداری دفتر (بسته به پلن)</li>
        </ul>
        <p className="text-sm text-muted">
          ارتباط: <a className="text-primary" href={`mailto:${SITE_CONTACT.email}`}>{SITE_CONTACT.email}</a>
        </p>
        <div className="flex gap-2">
          <Link to="/register"><Button>شروع آزمایشی</Button></Link>
          <Link to="/blog"><Button variant="outline">وبلاگ</Button></Link>
        </div>
      </main>
      <SiteFooter />
    </div>
  )
}
