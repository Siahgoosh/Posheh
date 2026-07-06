import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { CheckSquare, Plus, Trash2 } from 'lucide-react'
import api from '@/lib/api'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

interface Task {
  id: number
  title: string
  description?: string
  priority: string
  status: string
  due_at_jalali?: string
}

const priorityColors: Record<string, string> = {
  high: 'text-danger',
  medium: 'text-warning',
  low: 'text-muted',
}

const statusLabels: Record<string, string> = {
  pending: 'در انتظار',
  in_progress: 'در حال انجام',
  completed: 'انجام شده',
  cancelled: 'لغو شده',
}

export function TasksPage() {
  const queryClient = useQueryClient()
  const [title, setTitle] = useState('')
  const [showForm, setShowForm] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['tasks'],
    queryFn: async () => (await api.get('/tasks')).data,
  })

  const createMutation = useMutation({
    mutationFn: async () => api.post('/tasks', { title }),
    onSuccess: () => {
      setTitle('')
      setShowForm(false)
      queryClient.invalidateQueries({ queryKey: ['tasks'] })
    },
  })

  const updateMutation = useMutation({
    mutationFn: async ({ id, status }: { id: number; status: string }) =>
      api.put(`/tasks/${id}`, { status }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['tasks'] }),
  })

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => api.delete(`/tasks/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['tasks'] }),
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <CheckSquare className="h-6 w-6 text-primary" />
            وظایف
          </h1>
          <p className="text-muted mt-1">مدیریت کارهای روزانه تیم</p>
        </div>
        <Button onClick={() => setShowForm(!showForm)}>
          <Plus className="h-4 w-4" />
          وظیفه جدید
        </Button>
      </div>

      {showForm && (
        <Card className="glass">
          <CardContent className="p-4 flex gap-3">
            <Input
              placeholder="عنوان وظیفه..."
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              className="flex-1"
            />
            <Button onClick={() => createMutation.mutate()} disabled={!title || createMutation.isPending}>
              ذخیره
            </Button>
          </CardContent>
        </Card>
      )}

      {isLoading ? (
        <div className="flex justify-center py-20">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : (
        <div className="space-y-3">
          {data?.data?.map((task: Task) => (
            <Card key={task.id} className="glass-hover">
              <CardContent className="p-4 flex items-center gap-4">
                <button
                  onClick={() => updateMutation.mutate({
                    id: task.id,
                    status: task.status === 'completed' ? 'pending' : 'completed',
                  })}
                  className={`h-6 w-6 rounded-md border-2 flex items-center justify-center transition-colors ${
                    task.status === 'completed' ? 'bg-success border-success text-white' : 'border-muted'
                  }`}
                >
                  {task.status === 'completed' && '✓'}
                </button>
                <div className="flex-1">
                  <p className={`font-medium ${task.status === 'completed' ? 'line-through text-muted' : ''}`}>
                    {task.title}
                  </p>
                  {task.due_at_jalali && (
                    <p className="text-xs text-muted mt-1">سررسید: {task.due_at_jalali}</p>
                  )}
                </div>
                <Badge variant="outline" className={priorityColors[task.priority]}>
                  {task.priority}
                </Badge>
                <Badge>{statusLabels[task.status] || task.status}</Badge>
                <Button variant="ghost" size="icon" onClick={() => deleteMutation.mutate(task.id)}>
                  <Trash2 className="h-4 w-4 text-danger" />
                </Button>
              </CardContent>
            </Card>
          ))}
          {!data?.data?.length && (
            <p className="text-center text-muted py-12">وظیفه‌ای ثبت نشده</p>
          )}
        </div>
      )}
    </div>
  )
}
