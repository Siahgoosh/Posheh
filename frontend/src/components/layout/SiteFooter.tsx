import { Link } from 'react-router-dom'
import { Building2, Mail } from 'lucide-react'
import { SITE_CONTACT } from '@/constants/site'
import { EnamadBadge } from '@/components/layout/EnamadBadge'

export function SiteFooter() {
  const year = new Date().getFullYear()

  return (
    <footer className="border-t border-card-border bg-background/80">
      <div className="container mx-auto max-w-6xl px-4 py-10 space-y-8">
        <div className="flex flex-col lg:flex-row items-center lg:items-start justify-between gap-8">
          <div className="text-center lg:text-right space-y-3 max-w-md">
            <div className="flex items-center justify-center lg:justify-start gap-2 text-foreground">
              <Building2 className="h-5 w-5 text-primary shrink-0" />
              <span className="font-medium">پوشه — سامانه ابری مدیریت املاک</span>
            </div>
            <p className="text-sm text-muted leading-relaxed">
              فایلینگ، CRM، حسابداری و اپ اندروید برای مشاوران و دفاتر املاک در ایران.
            </p>
            <a
              href={`mailto:${SITE_CONTACT.email}`}
              className="inline-flex items-center gap-2 text-sm text-muted hover:text-primary transition-colors"
            >
              <Mail className="h-4 w-4" />
              {SITE_CONTACT.email}
            </a>
          </div>

          <nav
            className="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm text-muted"
            aria-label="پیوندهای پاورقی"
          >
            <Link to="/blog" className="hover:text-primary transition-colors">
              وبلاگ
            </Link>
            <Link to="/download" className="hover:text-primary transition-colors">
              دانلود
            </Link>
            <Link to="/contact" className="hover:text-primary transition-colors">
              تماس با ما
            </Link>
            <Link to="/terms" className="hover:text-primary transition-colors">
              قوانین
            </Link>
            <Link to="/privacy" className="hover:text-primary transition-colors">
              حریم خصوصی
            </Link>
          </nav>

          <EnamadBadge className="mx-auto lg:mx-0" />
        </div>

        <div className="pt-6 border-t border-card-border text-center text-xs text-muted">
          © {year} {SITE_CONTACT.domain} — تمامی حقوق محفوظ است.
        </div>
      </div>
    </footer>
  )
}
