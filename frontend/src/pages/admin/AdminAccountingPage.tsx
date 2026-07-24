import { AdminListPage } from '@/components/admin/AdminListPage'
import { formatPrice } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'

interface Row {
  id: number
  title?: string
  amount?: number
  type?: string
  office?: { name: string }
}

export function AdminAccountingPage() {
  return (
    <AdminListPage<Row>
      title="حسابداری"
      endpoint="/admin/accounting"
      queryKey="admin-accounting"
      renderRow={(t) => (
        <div className="flex justify-between border-b border-card-border pb-2 text-sm">
          <div>
            <span>{t.title ?? `تراکنش #${t.id}`}</span>
            <Badge variant="outline" className="mr-2">{t.office?.name}</Badge>
          </div>
          <span className={t.type === 'expense' ? 'text-danger' : 'text-primary'}>{formatPrice(t.amount ?? 0)}</span>
        </div>
      )}
    />
  )
}
