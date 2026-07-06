import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Bell, Check } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'

interface Notification {
  id: string
  title: string
  body: string
  type: string
  read_at: string | null
  created_at: string
}

export function NotificationBell() {
  const [open, setOpen] = useState(false)
  const queryClient = useQueryClient()

  const { data } = useQuery({
    queryKey: ['notifications'],
    queryFn: async () => (await api.get('/notifications')).data,
    refetchInterval: 60000,
  })

  const markReadMutation = useMutation({
    mutationFn: async (id: string) => api.post(`/notifications/${id}/read`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  })

  const markAllMutation = useMutation({
    mutationFn: async () => api.post('/notifications/read-all'),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  })

  const unread = data?.unread_count ?? 0

  return (
    <div className="relative">
      <Button variant="ghost" size="icon" onClick={() => setOpen(!open)} className="relative">
        <Bell className="h-5 w-5" />
        {unread > 0 && (
          <span className="absolute -top-1 -left-1 h-5 w-5 rounded-full bg-danger text-white text-xs flex items-center justify-center">
            {unread > 9 ? '9+' : unread}
          </span>
        )}
      </Button>

      {open && (
        <>
          <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
          <div className="absolute left-0 top-full mt-2 w-80 z-50 glass rounded-xl border border-card-border shadow-xl overflow-hidden">
            <div className="flex items-center justify-between p-3 border-b border-card-border">
              <span className="font-semibold text-sm">اعلان‌ها</span>
              {unread > 0 && (
                <button onClick={() => markAllMutation.mutate()} className="text-xs text-primary hover:underline">
                  خواندن همه
                </button>
              )}
            </div>
            <div className="max-h-80 overflow-y-auto">
              {data?.data?.length ? data.data.map((n: Notification) => (
                <div
                  key={n.id}
                  className={`p-3 border-b border-card-border last:border-0 hover:bg-white/5 cursor-pointer ${!n.read_at ? 'bg-primary/5' : ''}`}
                  onClick={() => !n.read_at && markReadMutation.mutate(n.id)}
                >
                  <div className="flex items-start gap-2">
                    <div className="flex-1">
                      <p className="text-sm font-medium">{n.title}</p>
                      <p className="text-xs text-muted mt-1">{n.body}</p>
                    </div>
                    {n.read_at && <Check className="h-3 w-3 text-success shrink-0" />}
                  </div>
                </div>
              )) : (
                <p className="text-center text-muted text-sm py-8">اعلانی نیست</p>
              )}
            </div>
          </div>
        </>
      )}
    </div>
  )
}
