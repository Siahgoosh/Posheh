import { formatPrice } from '@/lib/utils'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

export interface InvoiceData {
  invoice_number: string
  invoice_type: string
  invoice_type_label: string
  status: string
  status_label: string
  issued_at?: string
  seller: { name: string; support_phone?: string; support_email?: string }
  buyer: { office_name?: string; user_name?: string; user_phone?: string }
  items: { title: string; quantity: number; unit_price: number; discount: number; total: number }[]
  subtotal: number
  discount: number
  vat_percent: number
  vat_amount: number
  total: number
  gateway_label?: string
  ref_id?: string
  currency: string
}

interface Props {
  invoice: InvoiceData
  paymentId?: number
  onConfirm?: () => void
  onClose?: () => void
  confirmLabel?: string
  showActions?: boolean
}

export function PaymentInvoiceCard({ invoice, paymentId, onConfirm, onClose, confirmLabel = 'رفتن به درگاه پرداخت', showActions = true }: Props) {
  const downloadPdf = async () => {
    if (!paymentId) return
    try {
      const res = await api.get(`/payments/${paymentId}/invoice/pdf`, { responseType: 'blob' })
      const blob = new Blob([res.data], { type: 'application/pdf' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `${invoice.invoice_number ?? `invoice-${paymentId}`}.pdf`
      a.click()
      URL.revokeObjectURL(url)
    } catch {
      alert('خطا در دانلود فاکتور — دوباره تلاش کنید')
    }
  }

  return (
    <Card className="border-primary/20 shadow-lg max-w-lg w-full">
      <CardHeader className="pb-3 border-b border-card-border">
        <div className="flex items-start justify-between gap-2">
          <div>
            <CardTitle className="text-lg">{invoice.invoice_type_label}</CardTitle>
            <p className="text-xs text-muted mt-1">شماره: {invoice.invoice_number}</p>
          </div>
          <Badge variant={invoice.status === 'paid' ? 'default' : 'outline'} className={invoice.status === 'paid' ? 'bg-success/20 text-success' : ''}>
            {invoice.status_label}
          </Badge>
        </div>
      </CardHeader>
      <CardContent className="space-y-4 pt-4 text-sm">
        <div className="grid grid-cols-2 gap-3 text-xs">
          <div>
            <p className="text-muted mb-1">فروشنده</p>
            <p className="font-medium">{invoice.seller.name}</p>
          </div>
          <div>
            <p className="text-muted mb-1">خریدار</p>
            <p className="font-medium">{invoice.buyer.office_name ?? '—'}</p>
            {invoice.buyer.user_phone && <p dir="ltr" className="text-muted">{invoice.buyer.user_phone}</p>}
          </div>
        </div>

        <div className="rounded-lg border border-card-border overflow-hidden">
          {invoice.items.map((item, i) => (
            <div key={i} className="flex justify-between gap-2 p-3 border-b border-card-border last:border-0">
              <span>{item.title}</span>
              <span className="font-semibold text-primary shrink-0">{formatPrice(item.total)}</span>
            </div>
          ))}
        </div>

        <div className="space-y-1 text-xs">
          <div className="flex justify-between"><span className="text-muted">جمع</span><span>{formatPrice(invoice.subtotal)}</span></div>
          {invoice.discount > 0 && (
            <div className="flex justify-between text-success"><span>تخفیف</span><span>−{formatPrice(invoice.discount)}</span></div>
          )}
          {invoice.vat_amount > 0 && (
            <div className="flex justify-between"><span>مالیات ({invoice.vat_percent}%)</span><span>{formatPrice(invoice.vat_amount)}</span></div>
          )}
          <div className="flex justify-between font-bold text-base pt-2 border-t border-card-border">
            <span>مبلغ قابل پرداخت</span>
            <span className="text-primary">{formatPrice(invoice.total)}</span>
          </div>
        </div>

        {invoice.ref_id && (
          <p className="text-xs text-muted">کد پیگیری: <span dir="ltr" className="font-mono">{invoice.ref_id}</span> — {invoice.gateway_label}</p>
        )}

        {showActions && (
          <div className="flex flex-wrap gap-2 pt-2">
            {onConfirm && invoice.status !== 'paid' && (
              <Button className="flex-1" onClick={onConfirm}>{confirmLabel}</Button>
            )}
            {onClose && (
              <Button variant="outline" onClick={onClose}>بستن</Button>
            )}
            {paymentId && invoice.status === 'paid' && (
              <Button variant="outline" type="button" onClick={downloadPdf}>دانلود PDF</Button>
            )}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
