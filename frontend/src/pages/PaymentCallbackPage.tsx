import { Link, useSearchParams } from 'react-router-dom'
import { CheckCircle2, XCircle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'

export function PaymentCallbackPage() {
  const [params] = useSearchParams()
  const type = params.get('type')
  const success = params.get('status') === 'success'
  const isDomain = type === 'domain_order'

  return (
    <div className="min-h-screen flex items-center justify-center p-4">
      <Card className="max-w-md w-full">
        <CardContent className="p-8 text-center space-y-4">
          {success ? (
            <>
              <CheckCircle2 className="h-16 w-16 text-success mx-auto" />
              <h1 className="text-xl font-bold">پرداخت موفق</h1>
              <p className="text-muted">
                {isDomain
                  ? 'پرداخت دامنه ثبت شد. طی ۲۴ ساعت دامنه خریداری و به وبسایت شما متصل می‌شود.'
                  : 'اشتراک شما فعال شد.'}
                {' '}کد پیگیری: {params.get('ref') || '—'}
              </p>
            </>
          ) : (
            <>
              <XCircle className="h-16 w-16 text-danger mx-auto" />
              <h1 className="text-xl font-bold">پرداخت ناموفق</h1>
              <p className="text-muted">{params.get('message') || 'پرداخت انجام نشد یا لغو شد.'}</p>
            </>
          )}
          <Link to={success ? (isDomain ? '/office-website' : '/dashboard') : (isDomain ? '/office-website' : '/renew')}>
            <Button className="w-full">{success ? (isDomain ? 'بازگشت به وبسایت' : 'رفتن به داشبورد') : 'تلاش مجدد'}</Button>
          </Link>
        </CardContent>
      </Card>
    </div>
  )
}
