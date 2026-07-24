import { AdminListPage } from '@/components/admin/AdminListPage'
import { formatPrice, formatJalaliDate } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'

interface Row {
  id: number
  amount: number
  type: string
  description?: string
  created_at?: string
  wallet?: { office?: { name: string } }
}

export function AdminWalletTransactionsPage() {
  return (
    <AdminListPage<Row>
      title="تراکنش‌های کیف پول"
      endpoint="/admin/wallet-transactions"
      queryKey="admin-wallet-tx"
      renderRow={(t) => (
        <div className="flex justify-between border-b border-card-border pb-2 text-sm">
          <div>
            <span>{t.description ?? t.type}</span>
            <Badge variant="outline" className="mr-2">{t.wallet?.office?.name}</Badge>
          </div>
          <span className={t.type === 'debit' ? 'text-danger' : ''}>
            {formatPrice(t.amount)} · {t.created_at ? formatJalaliDate(t.created_at) : ''}
          </span>
        </div>
      )}
    />
  )
}
