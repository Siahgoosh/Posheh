import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { MessageSquare } from 'lucide-react'
import api from '@/lib/api'
import { AdminNav } from '@/components/admin/AdminNav'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

interface Ticket {
  id: number
  subject: string
  message: string
  status: string
  priority: string
  created_at: string
  user?: { name: string; mobile?: string }
  office?: { name: string }
}

const statusLabels: Record<string, string> = {
  open: 'باز',
  in_progress: 'در حال بررسی',
  resolved: 'حل شده',
  closed: 'بسته',
}

export function AdminTicketsPage() {
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-tickets'],
    queryFn: async () => (await api.get('/admin/tickets')).data,
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      api.put(`/admin/tickets/${id}`, { status }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-tickets'] }),
  })

  return (
    <div className="space-y-4 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <MessageSquare className="h-6 w-6 text-primary" />
          تیکت‌های پشتیبانی
        </h1>
        <p className="text-muted mt-1">پاسخ و مدیریت درخواست‌های دفاتر</p>
      </div>

      <AdminNav />

      {isLoading ? (
        <div className="flex justify-center py-16"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
      ) : (
        <div className="space-y-3">
          {data?.data?.map((ticket: Ticket) => (
            <Card key={ticket.id} className="glass">
              <CardContent className="p-4 space-y-3">
                <div className="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <p className="font-semibold">{ticket.subject}</p>
                    <p className="text-sm text-muted">
                      {ticket.office?.name} · {ticket.user?.name} · {new Date(ticket.created_at).toLocaleDateString('fa-IR')}
                    </p>
                  </div>
                  <div className="flex gap-2">
                    <Badge variant={ticket.priority === 'high' ? 'danger' : 'outline'}>{ticket.priority}</Badge>
                    <Badge variant={ticket.status === 'open' ? 'warning' : 'success'}>
                      {statusLabels[ticket.status] || ticket.status}
                    </Badge>
                  </div>
                </div>
                <p className="text-sm">{ticket.message}</p>
                {ticket.status === 'open' && (
                  <div className="flex gap-2">
                    <Button size="sm" onClick={() => updateMutation.mutate({ id: ticket.id, status: 'in_progress' })}>
                      در حال بررسی
                    </Button>
                    <Button size="sm" variant="outline" onClick={() => updateMutation.mutate({ id: ticket.id, status: 'resolved' })}>
                      حل شد
                    </Button>
                  </div>
                )}
              </CardContent>
            </Card>
          ))}
          {!data?.data?.length && <p className="text-muted text-center py-8">تیکتی وجود ندارد</p>}
        </div>
      )}
    </div>
  )
}
