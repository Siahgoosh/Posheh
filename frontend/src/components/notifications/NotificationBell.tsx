import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Bell, Check, CheckCheck } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { formatJalaliDate } from '@/lib/utils'

interface NotificationItem {
  id: string
  title: string
  body: string
  link?: string
  read_at?: string
  created_at?: string
  source: string
}

export function NotificationBell() {
  const [open, setOpen] = useState(false)
  const queryClient = useQueryClient()

  const { data } = useQuery({
    queryKey: ['notifications'],
    queryFn: async () => (await api.get('/notifications')).data.data as {
      items: NotificationItem[]
      unread_count: number
    },
    refetchInterval: 60_000,
  })

  const markRead = useMutation({
    mutationFn: (id: string) => api.post(`/notifications/${id}/read`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  })

  const markAll = useMutation({
    mutationFn: () => api.post('/notifications/read-all'),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  })

  const items = data?.items ?? []
  const unread = data?.unread_count ?? items.filter((n) => n.source === 'system' && !n.read_at).length

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setOpen(!open)}
        className="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-card-border hover:bg-muted/10 transition-colors"
        aria-label="اعلان‌ها"
      >
        <Bell className="h-5 w-5" />
        {unread > 0 && (
          <span className="absolute -top-1 -left-1 h-5 min-w-5 px-1 rounded-full bg-danger text-white text-[10px] font-bold flex items-center justify-center">
            {unread > 9 ? '۹+' : unread}
          </span>
        )}
      </button>

      {open && (
        <>
          <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
          <div className="absolute left-0 top-12 z-50 w-[min(20rem,calc(100vw-1.5rem))] max-h-[70vh] overflow-hidden rounded-2xl border border-card-border bg-background shadow-xl">
            <div className="flex items-center justify-between p-3 border-b border-card-border">
              <p className="font-semibold text-sm">اعلان‌ها</p>
              {unread > 0 && (
                <Button variant="ghost" size="sm" onClick={() => markAll.mutate()} disabled={markAll.isPending}>
                  <CheckCheck className="h-4 w-4" />
                  همه خوانده
                </Button>
              )}
            </div>
            <div className="overflow-y-auto max-h-[60vh]">
              {items.length === 0 ? (
                <p className="p-6 text-center text-sm text-muted">اعلانی نیست</p>
              ) : (
                items.map((n) => {
                  const isUnread = n.source === 'system' && !n.read_at
                  const content = (
                    <div className="flex items-start justify-between gap-2">
                      <div className="min-w-0">
                        <p className="font-medium">{n.title}</p>
                        <p className="text-muted text-xs mt-1 line-clamp-3">{n.body}</p>
                        {n.created_at && (
                          <p className="text-[10px] text-muted mt-1">{formatJalaliDate(n.created_at)}</p>
                        )}
                        {n.source === 'announcement' && (
                          <p className="text-[10px] text-accent mt-1">اطلاعیه عمومی</p>
                        )}
                      </div>
                      {isUnread && (
                        <button
                          type="button"
                          onClick={(e) => {
                            e.preventDefault()
                            e.stopPropagation()
                            markRead.mutate(n.id)
                          }}
                          className="text-primary shrink-0"
                          aria-label="علامت خوانده"
                        >
                          <Check className="h-4 w-4" />
                        </button>
                      )}
                    </div>
                  )

                  return (
                    <div
                      key={n.id}
                      className={`p-3 border-b border-card-border/50 text-sm ${isUnread ? 'bg-primary/5' : ''}`}
                    >
                      {n.link ? (
                        <Link
                          to={n.link}
                          onClick={() => {
                            if (isUnread) markRead.mutate(n.id)
                            setOpen(false)
                          }}
                          className="block hover:text-primary"
                        >
                          {content}
                        </Link>
                      ) : (
                        content
                      )}
                    </div>
                  )
                })
              )}
            </div>
          </div>
        </>
      )}
    </div>
  )
}
