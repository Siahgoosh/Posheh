import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Phone, Download, ArrowRight, Search } from 'lucide-react'
import api from '@/lib/api'
import { formatNumber, formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

interface PaymentLead {
  id: number
  status: string
  amount: number
  user_phone?: string
  gateway: string
  created_at: string
  metadata?: { plan_name?: string; user_name?: string; discount_code?: string }
  user?: { name?: string; mobile?: string }
  office?: { name?: string }
}

interface LeadsResponse {
  stats: { pending: number; failed: number; paid: number }
  data: { data: PaymentLead[]; current_page: number; last_page: number }
}

const statusLabel: Record<string, string> = {
  pending: 'شروع پرداخت — رها شده',
  failed: 'لغو / ناموفق',
  paid: 'موفق',
}

export function AdminPaymentLeadsPage() {
  const [status, setStatus] = useState('incomplete')
  const [search, setSearch] = useState('')

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['admin-payment-leads', status, search],
    queryFn: async () => {
      const res = await api.get('/admin/payment-leads', { params: { status, search: search || undefined } })
      return res.data as LeadsResponse
    },
  })

  const download = async () => {
    const res = await api.get('/admin/payment-leads/export', {
      params: { status },
      responseType: 'blob',
    })
    const url = URL.createObjectURL(res.data)
    const a = document.createElement('a')
    a.href = url
    a.download = `posheh-payment-leads-${new Date().toISOString().slice(0, 10)}.xlsx`
    a.click()
    URL.revokeObjectURL(url)
  }

  const leads = data?.data?.data ?? []

  return (
    <div className="space-y-6 max-w-6xl mx-auto animate-fade-in">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <Phone className="h-7 w-7 text-primary" />
            سرنخ‌های پرداخت ناتمام
          </h1>
          <p className="text-sm text-muted mt-1">کاربرانی که پرداخت را شروع کردند ولی تکمیل نکردند — برای تماس فروش</p>
        </div>
        <div className="flex gap-2 flex-wrap">
          <Button variant="outline" size="sm" onClick={download}>
            <Download className="h-4 w-4 ml-1" />
            دانلود اکسل
          </Button>
          <Link to="/admin"><Button variant="ghost" size="sm"><ArrowRight className="h-4 w-4" /> پنل مدیر</Button></Link>
        </div>
      </div>

      {data?.stats && (
        <div className="grid sm:grid-cols-3 gap-4">
          <Card><CardContent className="pt-6"><p className="text-sm text-muted">رها شده (pending)</p><p className="text-2xl font-bold">{formatNumber(data.stats.pending)}</p></CardContent></Card>
          <Card><CardContent className="pt-6"><p className="text-sm text-muted">لغو / ناموفق</p><p className="text-2xl font-bold">{formatNumber(data.stats.failed)}</p></CardContent></Card>
          <Card><CardContent className="pt-6"><p className="text-sm text-muted">پرداخت موفق</p><p className="text-2xl font-bold text-success">{formatNumber(data.stats.paid)}</p></CardContent></Card>
        </div>
      )}

      <Card>
        <CardHeader className="flex flex-row flex-wrap gap-3 items-center justify-between">
          <CardTitle className="text-base">لیست تماس</CardTitle>
          <div className="flex gap-2 flex-wrap">
            {(['incomplete', 'pending', 'failed', 'paid', 'all'] as const).map((s) => (
              <Button key={s} size="sm" variant={status === s ? 'default' : 'outline'} onClick={() => setStatus(s)}>
                {s === 'incomplete' ? 'همه ناتمام' : statusLabel[s] || s}
              </Button>
            ))}
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="relative max-w-sm">
            <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted" />
            <Input
              placeholder="جستجو نام یا موبایل…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && refetch()}
              className="pr-9"
            />
          </div>

          {isLoading ? (
            <p className="text-muted text-center py-8">بارگذاری…</p>
          ) : leads.length === 0 ? (
            <p className="text-muted text-center py-8">موردی یافت نشد.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-card-border text-muted text-right">
                    <th className="py-2 px-2">نام</th>
                    <th className="py-2 px-2">موبایل</th>
                    <th className="py-2 px-2">دفتر</th>
                    <th className="py-2 px-2">پلن</th>
                    <th className="py-2 px-2">مبلغ</th>
                    <th className="py-2 px-2">وضعیت</th>
                    <th className="py-2 px-2">تاریخ</th>
                    <th className="py-2 px-2"></th>
                  </tr>
                </thead>
                <tbody>
                  {leads.map((lead) => {
                    const phone = lead.user_phone || lead.user?.mobile || ''
                    const name = lead.user?.name || lead.metadata?.user_name || '—'
                    return (
                      <tr key={lead.id} className="border-b border-card-border/50">
                        <td className="py-3 px-2">{name}</td>
                        <td className="py-3 px-2 font-mono" dir="ltr">{phone || '—'}</td>
                        <td className="py-3 px-2">{lead.office?.name || '—'}</td>
                        <td className="py-3 px-2">{lead.metadata?.plan_name || '—'}</td>
                        <td className="py-3 px-2">{formatPrice(lead.amount)}</td>
                        <td className="py-3 px-2">{statusLabel[lead.status] || lead.status}</td>
                        <td className="py-3 px-2 text-muted">{new Date(lead.created_at).toLocaleString('fa-IR')}</td>
                        <td className="py-3 px-2">
                          {phone && (
                            <a href={`tel:${phone}`}>
                              <Button size="sm" variant="outline">تماس</Button>
                            </a>
                          )}
                        </td>
                      </tr>
                    )
                  })}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
