import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Bookmark, Play, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import api from '@/lib/api'
import { usePlanFeature } from '@/components/SubscriptionGuard'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

export function SavedSearchesPage() {
  const hasFeature = usePlanFeature('saved_searches')
  const queryClient = useQueryClient()
  const [name, setName] = useState('')
  const [q, setQ] = useState('')

  const { data: searches } = useQuery({
    queryKey: ['saved-searches'],
    queryFn: async () => (await api.get('/saved-searches')).data.data as Array<{ id: number; name: string; filters: Record<string, string> }>,
    enabled: hasFeature,
  })

  const create = useMutation({
    mutationFn: () => api.post('/saved-searches', { name, filters: { q } }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['saved-searches'] })
      setName('')
      setQ('')
    },
  })

  const remove = useMutation({
    mutationFn: (id: number) => api.delete(`/saved-searches/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['saved-searches'] }),
  })

  if (!hasFeature) {
    return <p className="text-center text-muted py-20">جستجوهای ذخیره‌شده در پلن شما فعال نیست.</p>
  }

  return (
    <div className="space-y-6 animate-fade-in">
      <h1 className="text-2xl font-bold flex items-center gap-2"><Bookmark className="h-6 w-6 text-primary" />جستجوهای ذخیره‌شده</h1>
      <Card>
        <CardHeader><CardTitle>ذخیره جستجوی جدید</CardTitle></CardHeader>
        <CardContent className="grid sm:grid-cols-3 gap-3">
          <Input placeholder="نام جستجو" value={name} onChange={(e) => setName(e.target.value)} />
          <Input placeholder="کلیدواژه / محله" value={q} onChange={(e) => setQ(e.target.value)} />
          <Button onClick={() => create.mutate()} disabled={!name || create.isPending}>ذخیره</Button>
        </CardContent>
      </Card>
      <div className="space-y-3">
        {searches?.map((s) => (
          <Card key={s.id}>
            <CardContent className="p-4 flex items-center justify-between gap-3">
              <div>
                <p className="font-medium">{s.name}</p>
                <p className="text-sm text-muted">{s.filters?.q || 'بدون فیلتر'}</p>
              </div>
              <div className="flex gap-2">
                <Link to={`/search?${new URLSearchParams(s.filters as Record<string, string>).toString()}`}>
                  <Button size="sm" variant="outline"><Play className="h-4 w-4" /></Button>
                </Link>
                <Button size="sm" variant="ghost" onClick={() => remove.mutate(s.id)}><Trash2 className="h-4 w-4 text-danger" /></Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  )
}
