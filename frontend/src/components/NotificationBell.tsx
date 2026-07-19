import { useEffect, useRef, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Bell } from 'lucide-react'
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
}

export function NotificationBell() {
  const [open, setOpen] = useState(false)
  const panelRef = useRef<HTMLDivElement>(null)
  const queryClient = useQueryClient()

  const { data, isLoading, isError } = useQuery({
    queryKey: ['notifications'],
    queryFn: async () =>
      (await api.get('/notifications', { params: { platform: 'web' } })).data.data as NotificationItem[],
    refetchInterval: 30_000,
    refetchOnWindowFocus: true,
  })

  const markRead = useMutation({
    mutationFn: (id: number) => api.post(`/notifications/${id}/read`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  })

  const unread = data?.filter((n) => !n.is_read) ?? []

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
        <div className="absolute left-0 top-full z-50 mt-2 w-80 max-h-[70vh] overflow-y-auto rounded-xl border border-card-border bg-background shadow-xl p-2">
          {isLoading && <p className="p-3 text-sm text-muted">در حال بارگذاری…</p>}
          {isError && <p className="p-3 text-sm text-danger">خطا در دریافت اعلان‌ها</p>}
          {!isLoading && !isError && !data?.length && (
            <p className="p-3 text-sm text-muted text-center">اعلانی ندارید</p>
          )}
          {data?.slice(0, 10).map((n) => (
            <div key={n.id} className={`p-3 rounded-lg text-sm ${n.is_read ? 'opacity-70' : 'bg-primary/5'}`}>
              <p className="font-medium">{n.title}</p>
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
                  <button
                    type="button"
                    className="text-xs text-muted hover:text-primary"
                    onClick={() => markRead.mutate(n.id)}
                  >
                    خواندم
                  </button>
                )}
              </div>
            </div>
          ))}
          {unread.some((n) => n.link_url?.includes('/renew')) && (
            <Link to="/renew" className="block text-center text-xs text-primary py-2" onClick={() => setOpen(false)}>
              تمدید اشتراک
            </Link>
          )}
        </div>
      )}
    </div>
  )
}
