import { useQuery } from '@tanstack/react-query'
import { History } from 'lucide-react'
import api from '@/lib/api'
import { usePlanFeature } from '@/components/SubscriptionGuard'
import { Card, CardContent } from '@/components/ui/card'

interface LogItem {
  id: number
  type: string
  description: string
  created_at_jalali?: string
  user?: { name: string }
}

export function ActivityLogsPage() {
  const hasFeature = usePlanFeature('activity_logs')

  const { data, isLoading } = useQuery({
    queryKey: ['activity-logs'],
    queryFn: async () => (await api.get('/activity-logs')).data.data as LogItem[],
    enabled: hasFeature,
  })

  if (!hasFeature) {
    return <p className="text-center text-muted py-20">گزارش فعالیت‌ها در پلن شما فعال نیست.</p>
  }

  if (isLoading) {
    return <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
  }

  return (
    <div className="space-y-6 animate-fade-in">
      <h1 className="text-2xl font-bold flex items-center gap-2"><History className="h-6 w-6 text-primary" />گزارش فعالیت‌ها</h1>
      <div className="space-y-2">
        {data?.map((item) => (
          <Card key={item.id}>
            <CardContent className="p-4 flex justify-between gap-4 text-sm">
              <div>
                <p className="font-medium">{item.description}</p>
                <p className="text-muted">{item.user?.name || 'سیستم'} — {item.type}</p>
              </div>
              <span className="text-muted shrink-0">{item.created_at_jalali}</span>
            </CardContent>
          </Card>
        )) ?? <p className="text-muted">فعالیتی ثبت نشده.</p>}
      </div>
    </div>
  )
}
