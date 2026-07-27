import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Plus, Eye, Users, ExternalLink, Box } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

export function VirtualToursPage() {
  const { data, isLoading, refetch } = useQuery({
    queryKey: ['virtual-tours'],
    queryFn: async () => (await api.get('/virtual-tours')).data.data,
  })

  const tours = data?.data || []

  const createTour = async () => {
    const title = prompt('عنوان تور مجازی:')
    if (!title) return
    await api.post('/virtual-tours', { title })
    refetch()
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <Box className="h-6 w-6 text-primary" />
            تور مجازی ۳۶۰ درجه
          </h1>
          <p className="text-muted text-sm mt-1">ساخت و مدیریت تور مجازی املاک — مشابه ۳۶۰نما</p>
        </div>
        <Button onClick={createTour}><Plus className="h-4 w-4" />تور جدید</Button>
      </div>

      {isLoading ? (
        <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {tours.map((t: { id: number; title: string; slug: string; status: string; view_count: number; leads_count?: number; scenes?: { name: string }[] }) => (
            <Card key={t.id} className="p-5 glass-hover">
              <div className="flex justify-between items-start mb-3">
                <h2 className="font-semibold">{t.title}</h2>
                <Badge variant={t.status === 'published' ? 'default' : 'outline'}>
                  {t.status === 'published' ? 'منتشر شده' : 'پیش‌نویس'}
                </Badge>
              </div>
              <div className="flex gap-4 text-xs text-muted mb-4">
                <span className="flex items-center gap-1"><Eye className="h-3 w-3" />{t.view_count || 0} بازدید</span>
                <span className="flex items-center gap-1"><Users className="h-3 w-3" />{t.leads_count || 0} سرنخ</span>
                <span>{t.scenes?.length || 0} صحنه</span>
              </div>
              <div className="flex gap-2">
                <Link to={`/virtual-tours/${t.id}/edit`} className="flex-1">
                  <Button variant="outline" size="sm" className="w-full">ویرایش</Button>
                </Link>
                {t.status === 'published' && (
                  <a href={`/tour/${t.slug}`} target="_blank" rel="noreferrer">
                    <Button size="sm" variant="ghost"><ExternalLink className="h-4 w-4" /></Button>
                  </a>
                )}
              </div>
            </Card>
          ))}
          {!tours.length && (
            <Card className="p-12 col-span-full text-center text-muted">
              <Box className="h-12 w-12 mx-auto mb-4 opacity-30" />
              <p>هنوز تور مجازی نساخته‌اید</p>
              <Button className="mt-4" onClick={createTour}>ساخت اولین تور</Button>
            </Card>
          )}
        </div>
      )}
    </div>
  )
}
