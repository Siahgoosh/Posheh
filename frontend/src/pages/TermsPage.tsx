import { Link } from 'react-router-dom'
import { ArrowRight } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'

export function TermsPage() {
  return (
    <div className="min-h-screen bg-background">
      <div className="container mx-auto max-w-3xl px-4 py-12 space-y-8">
        <div className="flex items-center gap-4">
          <Link to="/">
            <Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button>
          </Link>
          <h1 className="text-2xl font-bold">قوانین و مقررات استفاده</h1>
        </div>

        <Card>
          <CardContent className="p-8 space-y-6 text-sm leading-8 text-muted">
            <section>
              <h2 className="text-foreground font-semibold mb-2">۱. پذیرش شرایط</h2>
              <p>با ثبت‌نام و استفاده از سامانه پوشه، شما این قوانین را می‌پذیرید. پوشه یک سامانه ابری مدیریت و ثبت املاک برای دفاتر املاک و مشاوران است.</p>
            </section>
            <section>
              <h2 className="text-foreground font-semibold mb-2">۲. حساب کاربری</h2>
              <p>مسئولیت صحت اطلاعات ثبت‌نام، حفظ امنیت حساب و فعالیت‌های انجام‌شده تحت حساب شما بر عهده کاربر است. ورود با شماره موبایل و کد یکبارمصرف انجام می‌شود.</p>
            </section>
            <section>
              <h2 className="text-foreground font-semibold mb-2">۳. اشتراک و پرداخت</h2>
              <p>خدمات بر اساس پلن انتخابی (انفرادی، دفتر، پریمیوم) ارائه می‌شود. دوره آزمایشی رایگان پس از اتمام نیازمند تمدید اشتراک است. پرداخت از طریق درگاه‌های معتبر انجام می‌شود.</p>
            </section>
            <section>
              <h2 className="text-foreground font-semibold mb-2">۴. محتوا و داده‌ها</h2>
              <p>اطلاعات املاک، تصاویر و داده‌های واردشده متعلق به دفتر شماست. شما مسئول صحت اطلاعات و رعایت قوانین جمهوری اسلامی ایران در انتشار آگهی‌ها هستید.</p>
            </section>
            <section>
              <h2 className="text-foreground font-semibold mb-2">۵. محدودیت مسئولیت</h2>
              <p>پوشه ابزار مدیریت است و در معاملات املاک میان کاربران نهایی دخالتی ندارد. مسئولیت معاملات، قراردادها و اختلافات بر عهده طرفین است.</p>
            </section>
            <section>
              <h2 className="text-foreground font-semibold mb-2">۶. تغییرات</h2>
              <p>پوشه حق به‌روزرسانی قوانین و قیمت‌ها را با اطلاع‌رسانی در سایت محفوظ می‌دارد. ادامه استفاده به منزله پذیرش تغییرات است.</p>
            </section>
            <p className="text-xs pt-4 border-t border-card-border">آخرین به‌روزرسانی: تیر ۱۴۰۵</p>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
