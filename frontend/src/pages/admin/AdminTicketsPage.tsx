import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowRight } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

export function AdminTicketsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-tickets'],
    queryFn: async () => {
      const res = await api.get('/admin/tickets')
      return (res.data.data ?? []) as { id: number; subject: string; status: string; office?: { name: string }; user?: { name: string } }[]
    },
  })

  return (
    <div className="space-y-6 max-w-4xl mx-auto animate-fade-in">
      <div className="flex items-center gap-3">
        <Link to="/admin"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
        <h1 className="text-2xl font-bold">تیکت‌های پشتیبانی</h1>
      </div>
      <Card>
        <CardHeader><CardTitle>همه تیکت‌ها</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {isLoading ? <p className="text-sm text-muted">بارگذاری…</p> : data?.map((t) => (
            <div key={t.id} className="flex justify-between gap-2 text-sm border-b border-card-border pb-2">
              <span>{t.subject}</span>
              <span className="text-muted">{t.office?.name} · {t.user?.name} · {t.status}</span>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
