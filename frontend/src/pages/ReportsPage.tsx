import { useQuery } from '@tanstack/react-query'
import { BarChart3, Building2, Kanban, Wallet } from 'lucide-react'
import api from '@/lib/api'
import { formatNumber, formatPrice } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

export function ReportsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['reports'],
    queryFn: async () => (await api.get('/reports/dashboard')).data.data,
  })

  if (isLoading || !data) {
    return <div className="p-8 text-center text-muted">بارگذاری گزارش‌ها…</div>
  }

  return (
    <div className="space-y-6 animate-fade-in">
      <h1 className="text-2xl font-bold flex items-center gap-2"><BarChart3 className="h-6 w-6 text-primary" /> گزارش‌های دفتر</h1>

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card><CardContent className="pt-6"><Building2 className="h-5 w-5 text-primary mb-2" /><p className="text-2xl font-bold">{formatNumber(data.properties.total)}</p><p className="text-sm text-muted">کل املاک</p></CardContent></Card>
        <Card><CardContent className="pt-6"><p className="text-2xl font-bold text-success">{formatNumber(data.properties.active)}</p><p className="text-sm text-muted">فعال</p></CardContent></Card>
        <Card><CardContent className="pt-6"><Kanban className="h-5 w-5 text-primary mb-2" /><p className="text-2xl font-bold">{formatNumber(data.crm.open_deals)}</p><p className="text-sm text-muted">معامله باز</p></CardContent></Card>
        <Card><CardContent className="pt-6"><Wallet className="h-5 w-5 text-primary mb-2" /><p className="text-2xl font-bold">{formatPrice(data.accounting.month_income)}</p><p className="text-sm text-muted">درآمد ماه</p></CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>توزیع نوع ملک</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {Object.entries(data.by_type || {}).map(([type, count]) => (
            <div key={type} className="flex justify-between text-sm">
              <span>{type}</span>
              <span className="text-muted">{formatNumber(count as number)}</span>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
