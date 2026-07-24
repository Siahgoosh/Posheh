import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { formatNumber } from '@/lib/utils'
import { useState } from 'react'

interface Paginated<T> {
  data: T[]
  total: number
  current_page: number
  last_page: number
}

export function useAdminList<T>(key: string, endpoint: string, params: Record<string, string | number | undefined> = {}) {
  return useQuery({
    queryKey: [key, params],
    queryFn: async () => {
      const res = await api.get(endpoint, { params })
      return res.data as Paginated<T>
    },
  })
}

export function AdminListPage<T extends { id: number }>({
  title,
  description,
  endpoint,
  queryKey,
  searchPlaceholder = 'جستجو…',
  filters,
  renderRow,
  extraParams = {},
}: {
  title: string
  description?: string
  endpoint: string
  queryKey: string
  searchPlaceholder?: string
  filters?: React.ReactNode
  renderRow: (item: T) => React.ReactNode
  extraParams?: Record<string, string | number | undefined>
}) {
  const [q, setQ] = useState('')
  const [page, setPage] = useState(1)
  const { data, isLoading } = useAdminList<T>(queryKey, endpoint, { q: q || undefined, page, ...extraParams })
  const rows = data?.data ?? []

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title={title} description={description} />
      <Card>
        <CardHeader className="flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
          <CardTitle className="text-base">{formatNumber(data?.total ?? 0)} مورد</CardTitle>
          <div className="flex flex-wrap gap-2">
            <Input
              placeholder={searchPlaceholder}
              value={q}
              onChange={(e) => { setQ(e.target.value); setPage(1) }}
              className="w-full sm:w-56"
            />
            {filters}
          </div>
        </CardHeader>
        <CardContent className="space-y-3">
          {isLoading ? (
            <p className="text-sm text-muted">بارگذاری…</p>
          ) : rows.length === 0 ? (
            <p className="text-sm text-muted">موردی یافت نشد.</p>
          ) : (
            rows.map((item) => <div key={item.id}>{renderRow(item)}</div>)
          )}
          {(data?.last_page ?? 1) > 1 && (
            <div className="flex justify-center gap-2 pt-4">
              <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>قبلی</Button>
              <span className="text-sm text-muted self-center">{page} / {data?.last_page}</span>
              <Button variant="outline" size="sm" disabled={page >= (data?.last_page ?? 1)} onClick={() => setPage((p) => p + 1)}>بعدی</Button>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
