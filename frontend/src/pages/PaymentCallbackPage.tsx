import { Link, useSearchParams } from 'react-router-dom'
import { CheckCircle2, XCircle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { SeoHead } from '@/components/seo/SeoHead'

export function PaymentCallbackPage() {
  const [params] = useSearchParams()
  const success = params.get('status') === 'success'

  return (
    <div className="min-h-screen flex items-center justify-center p-4">
      <SeoHead title="نتیجه پرداخت" path="/payment/callback" noindex />
      <Card className="max-w-md w-full">
        <CardContent className="p-8 text-center space-y-4">
          {success ? (
            <>
              <CheckCircle2 className="h-16 w-16 text-success mx-auto" />
              <h1 className="text-xl font-bold">پرداخت موفق</h1>
              <p className="text-muted">اشتراک شما فعال شد. کد پیگیری: {params.get('ref') || '—'}</p>
            </>
          ) : (
            <>
              <XCircle className="h-16 w-16 text-danger mx-auto" />
              <h1 className="text-xl font-bold">پرداخت ناموفق</h1>
              <p className="text-muted">{params.get('message') || 'پرداخت انجام نشد یا لغو شد.'}</p>
            </>
          )}
          <Link to={success ? '/dashboard' : '/renew'}>
            <Button className="w-full">{success ? 'رفتن به داشبورد' : 'تلاش مجدد'}</Button>
          </Link>
        </CardContent>
      </Card>
    </div>
  )
}
