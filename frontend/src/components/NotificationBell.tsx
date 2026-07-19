import { useEffect, useRef, useState, useCallback } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Bell, CheckCheck, Trash2 } from 'lucide-react'
import { Link } from 'react-router-dom'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'

type NotificationItem = {
  id: number
  title: string
  body: string
  link_url?: string
  is_read: boolean
  priority?: string
  sent_at_human?: string
}

function requestBrowserPermission() {
  if (typeof Notification === 'undefined' || Notification.permission === 'granted') return
  if (Notification.permission !== 'denied') {
    Notification.requestPermission().catch(() => {})
  }
}

export function NotificationBell() {
  const [open, setOpen] = useState(false)
  const panelRef = useRef<HTMLDivElement>(null)
  const queryClient = useQueryClient()
  const lastUnreadRef = useRef(0)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['notifications'],
    queryFn: async () => {
      const res = await api.get('/notifications', { params: { platform: 'web' } })
      return {
        items: res.data.data as NotificationItem[],
        pollInterval: (res.data.poll_interval as number) || 30,
        unreadCount: (res.data.unread_count as number) || 0,
      }
    },
    refetchInterval: (query) => (query.state.data?.pollInterval ?? 30) * 1000,
    refetchOnWindowFocus: true,
  })

  const items = data?.items ?? []
  const unread = items.filter((n) => !n.is_read)

  const showBrowserAlert = useCallback((notification: NotificationItem) => {
    if (typeof Notification === 'undefined' || Notification.permission !== 'granted') return
    try {
      const n = new Notification(notification.title, {
        body: notification.body,
        tag: `posheh-${notification.id}`,
        icon: '/favicon.ico',
      })
      n.onclick = () => {
        window.focus()
        if (notification.link_url) window.open(notification.link_url, '_blank')
        n.close()
      }
    } catch {
      // ignore
    }
  }, [])

  useEffect(() => {
    requestBrowserPermission()
  }, [])

  useEffect(() => {
    if (!data) return
    const newUnread = data.unreadCount
    if (newUnread > lastUnreadRef.current) {
      const latest = items.find((n) => !n.is_read)
      if (latest) showBrowserAlert(latest)
    }
    lastUnreadRef.current = newUnread
  }, [data, items, showBrowserAlert])

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['notifications'] })

  const markRead = useMutation({
    mutationFn: (id: number) => api.post(`/notifications/${id}/read`),
    onSuccess: invalidate,
  })

  const markAllRead = useMutation({
    mutationFn: () => api.post('/notifications/read-all'),
    onSuccess: invalidate,
  })

  const dismiss = useMutation({
    mutationFn: (id: number) => api.delete(`/notifications/${id}`),
    onSuccess: invalidate,
  })

  useEffect(() => {
    if (!open) return
    const onPointerDown = (event: MouseEvent) => {
      if (panelRef.current && !panelRef.current.contains(event.target as Node)) {
        setOpen(false)
      }
    }
    document.addEventListener('mousedown', onPointerDown)
    return () => document.removeEventListener('mousedown', onPointerDown)
  }, [open])

  return (
    <div className="relative" ref={panelRef}>
      <Button
        variant="ghost"
        size="icon"
        className="relative"
        aria-label="اعلان‌ها"
        aria-expanded={open}
        onClick={() => setOpen((v) => !v)}
      >
        <Bell className="h-5 w-5" />
        {unread.length > 0 && (
          <span className="absolute -top-0.5 -left-0.5 h-4 min-w-4 px-1 rounded-full bg-warning text-[10px] font-bold text-black flex items-center justify-center">
            {unread.length}
          </span>
        )}
      </Button>

      {open && (
        <div className="absolute left-0 top-full z-50 mt-2 w-80 max-h-[70vh] overflow-y-auto rounded-xl border border-card-border bg-background shadow-xl">
          <div className="flex items-center justify-between p-2 border-b border-card-border sticky top-0 bg-background">
            <span className="text-sm font-semibold px-1">اعلان‌ها</span>
            {unread.length > 0 && (
              <Button variant="ghost" size="sm" className="text-xs h-7" onClick={() => markAllRead.mutate()} disabled={markAllRead.isPending}>
                <CheckCheck className="h-3 w-3 ml-1" /> همه خوانده
              </Button>
            )}
          </div>

          <div className="p-2">
            {isLoading && <p className="p-3 text-sm text-muted">در حال بارگذاری…</p>}
            {isError && <p className="p-3 text-sm text-danger">خطا در دریافت اعلان‌ها</p>}
            {!isLoading && !isError && !items.length && (
              <p className="p-3 text-sm text-muted text-center">اعلانی ندارید</p>
            )}
            {items.slice(0, 15).map((n) => (
              <div key={n.id} className={`p-3 rounded-lg text-sm mb-1 ${n.is_read ? 'opacity-70' : 'bg-primary/5'}`}>
                <div className="flex items-start justify-between gap-2">
                  <p className="font-medium">{n.title}</p>
                  {n.sent_at_human && <span className="text-[10px] text-muted shrink-0">{n.sent_at_human}</span>}
                </div>
                <p className="text-muted text-xs mt-1 leading-relaxed">{n.body}</p>
                <div className="flex gap-2 mt-2">
                  {n.link_url && (
                    <a
                      href={n.link_url}
                      target="_blank"
                      rel="noreferrer"
                      className="text-xs text-primary hover:underline"
                      onClick={() => {
                        if (!n.is_read) markRead.mutate(n.id)
                        setOpen(false)
                      }}
                    >
                      باز کردن
                    </a>
                  )}
                  {!n.is_read && (
                    <button type="button" className="text-xs text-muted hover:text-primary" onClick={() => markRead.mutate(n.id)}>
                      خواندم
                    </button>
                  )}
                  <button type="button" className="text-xs text-muted hover:text-danger flex items-center gap-0.5" onClick={() => dismiss.mutate(n.id)}>
                    <Trash2 className="h-3 w-3" /> حذف
                  </button>
                </div>
              </div>
            ))}
            {unread.some((n) => n.link_url?.includes('/renew')) && (
              <Link to="/renew" className="block text-center text-xs text-primary py-2" onClick={() => setOpen(false)}>
                تمدید اشتراک
              </Link>
            )}
          </div>
        </div>
      )}
    </div>
  )
}
