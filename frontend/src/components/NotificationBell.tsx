import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Bell } from 'lucide-react'
import { Link } from 'react-router-dom'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'

export function NotificationBell() {
  const queryClient = useQueryClient()
  const { data } = useQuery({
    queryKey: ['notifications'],
    queryFn: async () => (await api.get('/notifications', { params: { platform: 'web' } })).data.data as Array<{
      id: number; title: string; body: string; link_url?: string; is_read: boolean; priority?: string
    }>,
    refetchInterval: 60_000,
  })

  const markRead = useMutation({
    mutationFn: (id: number) => api.post(`/notifications/${id}/read`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  })

  const unread = data?.filter((n) => !n.is_read) ?? []

  if (!data?.length) return null

  return (
    <div className="relative group">
      <Button variant="ghost" size="icon" className="relative" aria-label="اعلان‌ها">
        <Bell className="h-5 w-5" />
        {unread.length > 0 && (
          <span className="absolute -top-0.5 -left-0.5 h-4 min-w-4 px-1 rounded-full bg-warning text-[10px] font-bold text-black flex items-center justify-center">
            {unread.length}
          </span>
        )}
      </Button>
      <div className="hidden group-hover:block absolute left-0 top-full z-50 mt-2 w-80 rounded-xl border border-card-border bg-background shadow-xl p-2">
        {data.slice(0, 5).map((n) => (
          <div key={n.id} className={`p-3 rounded-lg text-sm ${n.is_read ? 'opacity-70' : 'bg-primary/5'}`}>
            <p className="font-medium">{n.title}</p>
            <p className="text-muted text-xs mt-1 leading-relaxed">{n.body}</p>
            <div className="flex gap-2 mt-2">
              {n.link_url && (
                <a href={n.link_url} target="_blank" rel="noreferrer" className="text-xs text-primary hover:underline">باز کردن</a>
              )}
              {!n.is_read && (
                <button type="button" className="text-xs text-muted hover:text-primary" onClick={() => markRead.mutate(n.id)}>خواندم</button>
              )}
            </div>
          </div>
        ))}
        {unread.some((n) => n.link_url?.includes('/renew')) && (
          <Link to="/renew" className="block text-center text-xs text-primary py-2">تمدید اشتراک</Link>
        )}
      </div>
    </div>
  )
}
