import { Link } from 'react-router-dom'
import { ArrowRight, Mail, MessageSquare, Smartphone, Globe } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { SeoHead } from '@/components/seo/SeoHead'
import { SiteFooter } from '@/components/layout/SiteFooter'
import { SITE_CONTACT } from '@/constants/site'
import { getOrganizationJsonLd } from '@/lib/seo'
import { PRIMARY_KEYWORDS_STRING } from '@/constants/seo'

export function ContactPage() {
  return (
    <div className="min-h-screen bg-background text-foreground flex flex-col">
      <SeoHead
        title="تماس با پوشه — پشتیبانی نرم‌افزار املاک"
        description="تماس با تیم پوشه برای پشتیبانی فایلینگ املاک، CRM املاک و حسابداری املاک."
        keywords={`تماس پوشه, پشتیبانی نرم افزار املاک, ${PRIMARY_KEYWORDS_STRING}`}
        path="/contact"
        jsonLd={getOrganizationJsonLd()}
      />

      <header className="border-b border-card-border glass">
        <div className="container mx-auto flex h-16 max-w-4xl items-center justify-between px-4">
          <Link to="/" className="text-sm text-muted hover:text-primary">
            صفحه اصلی
          </Link>
          <Link to="/register">
            <Button size="sm">ثبت‌نام رایگان</Button>
          </Link>
        </div>
      </header>

      <main className="container mx-auto max-w-3xl px-4 py-12 space-y-8 flex-1">
        <div className="flex items-center gap-4">
          <Link to="/">
            <Button variant="ghost" size="icon">
              <ArrowRight className="h-5 w-5" />
            </Button>
          </Link>
          <div>
            <h1 className="text-2xl font-bold">تماس با ما</h1>
            <p className="text-sm text-muted mt-1">پاسخگویی در ساعات اداری — شنبه تا پنج‌شنبه</p>
          </div>
        </div>

        <div className="grid gap-6 md:grid-cols-2">
          <Card className="glass">
            <CardContent className="p-6 space-y-4">
              <div className="flex items-center gap-3">
                <Mail className="h-5 w-5 text-primary" />
                <h2 className="font-semibold">ایمیل</h2>
              </div>
              <p className="text-sm text-muted leading-relaxed">
                برای سوالات عمومی، فروش و همکاری:
              </p>
              <a
                href={`mailto:${SITE_CONTACT.email}`}
                className="text-primary font-medium hover:underline"
              >
                {SITE_CONTACT.email}
              </a>
              <p className="text-sm text-muted pt-2">پشتیبانی فنی:</p>
              <a
                href={`mailto:${SITE_CONTACT.supportEmail}`}
                className="text-primary font-medium hover:underline"
              >
                {SITE_CONTACT.supportEmail}
              </a>
            </CardContent>
          </Card>

          <Card className="glass">
            <CardContent className="p-6 space-y-4">
              <div className="flex items-center gap-3">
                <MessageSquare className="h-5 w-5 text-primary" />
                <h2 className="font-semibold">تیکت پشتیبانی</h2>
              </div>
              <p className="text-sm text-muted leading-relaxed">
                کاربران ثبت‌نام‌شده می‌توانند از بخش تیکت‌ها در پنل، درخواست خود را ثبت کنند.
              </p>
              <Link to="/login">
                <Button variant="outline" className="w-full">
                  ورود به پنل
                </Button>
              </Link>
            </CardContent>
          </Card>

          <Card className="glass md:col-span-2">
            <CardContent className="p-6 space-y-4">
              <div className="flex items-center gap-3">
                <Globe className="h-5 w-5 text-primary" />
                <h2 className="font-semibold">وب‌سایت و دانلود اپ</h2>
              </div>
              <p className="text-sm text-muted leading-relaxed">
                ثبت‌نام، تمدید اشتراک و دانلود نسخه اندروید از وب‌سایت رسمی انجام می‌شود.
              </p>
              <div className="flex flex-wrap gap-3">
                <Link to="/register">
                  <Button>شروع ۴۸ ساعت رایگان</Button>
                </Link>
                <Link to="/download">
                  <Button variant="outline">
                    <Smartphone className="h-4 w-4" />
                    دانلود اپ
                  </Button>
                </Link>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardContent className="p-6 text-sm text-muted leading-8">
            <p>
              <strong className="text-foreground">حریم خصوصی:</strong>{' '}
              برای درخواست حذف یا اصلاح داده‌های شخصی، ایمیل{' '}
              <a href={`mailto:${SITE_CONTACT.supportEmail}`} className="text-primary hover:underline">
                {SITE_CONTACT.supportEmail}
              </a>{' '}
              بزنید یا از تیکت پشتیبانی استفاده کنید. جزئیات در{' '}
              <Link to="/privacy" className="text-primary hover:underline">
                صفحه حریم خصوصی
              </Link>
              .
            </p>
          </CardContent>
        </Card>
      </main>

      <SiteFooter />
    </div>
  )
}
