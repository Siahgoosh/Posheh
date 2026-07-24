import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Search } from 'lucide-react'
import api from '@/lib/api'
import { Input } from '@/components/ui/input'
import { Card, CardContent } from '@/components/ui/card'
import { Link } from 'react-router-dom'

export function AdminTopbar() {
  const [q, setQ] = useState('')
  const { data } = useQuery({
    queryKey: ['admin-search', q],
    queryFn: async () => {
      const res = await api.get('/admin/search', { params: { q } })
      return res.data.data as Record<string, { id: number; name?: string; title?: string; mobile?: string; subject?: string }[]>
    },
    enabled: q.length >= 2,
  })

  const hasResults = data && Object.values(data).some((arr) => arr?.length)

  return (
    <header className="sticky top-0 z-30 border-b border-card-border/50 bg-background/80 backdrop-blur-xl px-4 py-3 lg:px-8">
      <div className="relative max-w-xl">
        <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted" />
        <Input
          value={q}
          onChange={(e) => setQ(e.target.value)}
          placeholder="جستجو در کاربران، دفاتر، پرداخت‌ها…"
          className="pr-10"
        />
        {hasResults && (
          <Card className="absolute top-full mt-2 w-full z-50 shadow-xl">
            <CardContent className="p-3 space-y-3 text-sm max-h-80 overflow-y-auto">
              {data.users?.length ? (
                <div>
                  <p className="text-xs text-muted mb-1">کاربران</p>
                  {data.users.map((u) => (
                    <Link key={u.id} to={`/users`} className="block py-1 hover:text-primary">
                      {u.name} — {u.mobile}
                    </Link>
                  ))}
                </div>
              ) : null}
              {data.tenants?.length ? (
                <div>
                  <p className="text-xs text-muted mb-1">دفاتر</p>
                  {data.tenants.map((t) => (
                    <Link key={t.id} to={`/tenants`} className="block py-1 hover:text-primary">{t.name}</Link>
                  ))}
                </div>
              ) : null}
              {data.tickets?.length ? (
                <div>
                  <p className="text-xs text-muted mb-1">تیکت‌ها</p>
                  {data.tickets.map((t) => (
                    <Link key={t.id} to={`/tickets`} className="block py-1 hover:text-primary">{t.subject}</Link>
                  ))}
                </div>
              ) : null}
            </CardContent>
          </Card>
        )}
      </div>
    </header>
  )
}
