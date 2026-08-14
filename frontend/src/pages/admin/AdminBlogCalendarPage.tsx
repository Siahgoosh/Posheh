import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowRight, CalendarDays } from 'lucide-react'
import api from '@/lib/api'
import { adminPath } from '@/lib/adminPaths'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

interface CalItem {
  id: number
  title: string
  slug: string
  kind: string
  review_status?: string
  scheduled_at?: string
  published_at?: string
  category_slug?: string
}

export function AdminBlogCalendarPage() {
  const [from, setFrom] = useState(() => {
    const d = new Date(); d.setMonth(d.getMonth() - 1); return d.toISOString().slice(0, 10)
  })
  const [to, setTo] = useState(() => {
    const d = new Date(); d.setMonth(d.getMonth() + 1); return d.toISOString().slice(0, 10)
  })

  const { data, isLoading } = useQuery({
    queryKey: ['admin-blog-calendar', from, to],
    queryFn: async () => (await api.get('/admin/blog/calendar', { params: { from, to } })).data.data as CalItem[],
  })

  const groups = {
    scheduled: data?.filter((i) => i.kind === 'scheduled') ?? [],
    published: data?.filter((i) => i.kind === 'published') ?? [],
    draft: data?.filter((i) => i.kind === 'draft') ?? [],
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-6xl mx-auto">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Link to={adminPath('blog')}><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
          <div>
            <h1 className="text-2xl font-bold flex items-center gap-2"><CalendarDays className="h-6 w-6" /> تقویم محتوا</h1>
            <p className="text-sm text-muted">زمان‌بندی‌شده / منتشرشده / پیش‌نویس</p>
          </div>
        </div>
        <div className="flex gap-2 items-end">
          <div>
            <label className="text-xs text-muted block mb-1">از</label>
            <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} dir="ltr" />
          </div>
          <div>
            <label className="text-xs text-muted block mb-1">تا</label>
            <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} dir="ltr" />
          </div>
        </div>
      </div>

      {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : (
        <div className="grid lg:grid-cols-3 gap-4">
          {([
            ['زمان‌بندی‌شده', groups.scheduled],
            ['منتشرشده', groups.published],
            ['پیش‌نویس‌ها', groups.draft],
          ] as const).map(([label, items]) => (
            <Card key={label}>
              <CardHeader><CardTitle className="text-base">{label} ({items.length})</CardTitle></CardHeader>
              <CardContent className="space-y-2 max-h-[60vh] overflow-y-auto">
                {items.length === 0 && <p className="text-xs text-muted">موردی نیست.</p>}
                {items.map((item) => (
                  <Link key={item.id} to={adminPath(`blog/${item.id}/edit`)} className="block rounded-lg border border-card-border px-3 py-2 hover:bg-card/60">
                    <p className="text-sm font-medium">{item.title}</p>
                    <p className="text-[10px] text-muted">
                      {item.scheduled_at || item.published_at || '—'} · {item.category_slug || '—'}
                    </p>
                  </Link>
                ))}
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
