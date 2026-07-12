import { Link } from 'react-router-dom'
import { ArrowRight } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'

export function PrivacyPage() {
  return (
    <div className="min-h-screen bg-background">
      <div className="container mx-auto max-w-3xl px-4 py-12 space-y-8">
        <div className="flex items-center gap-4">
          <Link to="/">
            <Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button>
          </Link>
          <h1 className="text-2xl font-bold">حریم خصوصی</h1>
        </div>

        <Card>
          <CardContent className="p-8 space-y-6 text-sm leading-8 text-muted">
            <section>
              <h2 className="text-foreground font-semibold mb-2">۱. داده‌های جمع‌آوری‌شده</h2>
              <p>شماره موبایل، نام، اطلاعات دفتر، املاک ثبت‌شده، تصاویر، سوابق فعالیت و داده‌های فنی (IP، مرورگر) برای ارائه خدمات جمع‌آوری می‌شود.</p>
            </section>
            <section>
              <h2 className="text-foreground font-semibold mb-2">۲. نحوه استفاده</h2>
              <p>داده‌ها برای احراز هویت، مدیریت اشتراک، پشتیبانی، بهبود سرویس و ارسال پیامک‌های ضروری (OTP، یادآور اشتراک) استفاده می‌شود.</p>
            </section>
            <section>
              <h2 className="text-foreground font-semibold mb-2">۳. اشتراک‌گذاری</h2>
              <p>اطلاعات شخصی شما به اشخاص ثالث فروخته نمی‌شود. داده‌ها فقط در صورت الزام قانونی یا با ارائه‌دهندگان زیرساخت (میزبانی، SMS، درگاه پرداخت) به حداقل لازم به اشتراک گذاشته می‌شود.</p>
            </section>
            <section>
              <h2 className="text-foreground font-semibold mb-2">۴. امنیت</h2>
              <p>از اقدامات فنی مناسب برای محافظت از داده‌ها استفاده می‌شود. با این حال هیچ سیستمی ۱۰۰٪ امن نیست؛ رمز و دسترسی حساب را محرمانه نگه دارید.</p>
            </section>
            <section>
              <h2 className="text-foreground font-semibold mb-2">۵. حقوق شما</h2>
              <p>می‌توانید درخواست اصلاح یا حذف اطلاعات خود را از طریق تیکت پشتیبانی ارسال کنید. پس از لغو اشتراک، داده‌ها طبق سیاست نگهداری ما مدیریت می‌شود.</p>
            </section>
            <section>
              <h2 className="text-foreground font-semibold mb-2">۶. تماس</h2>
              <p>برای سوالات حریم خصوصی از بخش تیکت‌های پشتیبانی در پنل یا ایمیل رسمی پوشه استفاده کنید.</p>
            </section>
            <p className="text-xs pt-4 border-t border-card-border">آخرین به‌روزرسانی: تیر ۱۴۰۵</p>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
