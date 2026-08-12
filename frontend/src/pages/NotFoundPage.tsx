import { Link } from 'react-router-dom'
import { SeoHead } from '@/components/seo/SeoHead'
import { Button } from '@/components/ui/button'

export function NotFoundPage() {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center gap-4 px-6 text-center bg-background text-foreground">
      <SeoHead
        title="صفحه پیدا نشد"
        description="این آدرس در پوشه وجود ندارد."
        path="/404"
        noindex
      />
      <p className="text-6xl font-bold text-primary/40">۴۰۴</p>
      <h1 className="text-xl font-bold">صفحه پیدا نشد</h1>
      <p className="text-sm text-muted max-w-md">
        آدرس واردشده موجود نیست یا منتقل شده است. از منوی زیر یک صفحه معتبر را باز کنید.
      </p>
      <div className="flex flex-wrap gap-2 justify-center">
        <Button asChild><Link to="/">صفحه اصلی</Link></Button>
        <Button asChild variant="outline"><Link to="/blog">وبلاگ</Link></Button>
        <Button asChild variant="outline"><Link to="/register">ثبت‌نام</Link></Button>
      </div>
    </div>
  )
}
