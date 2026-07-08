import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { UserPlus, Users } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

interface TeamMember {
  id: number
  name: string
  mobile: string
  role_label: string
}

export function TeamPage() {
  const [mobile, setMobile] = useState('')
  const [error, setError] = useState('')
  const queryClient = useQueryClient()

  const { data: members, isLoading } = useQuery({
    queryKey: ['team'],
    queryFn: async () => {
      const res = await api.get('/office/team')
      return res.data.data as TeamMember[]
    },
  })

  const inviteMutation = useMutation({
    mutationFn: (m: string) => api.post('/office/invite', { mobile: m }),
    onSuccess: () => {
      setMobile('')
      setError('')
      queryClient.invalidateQueries({ queryKey: ['team'] })
    },
    onError: (err: unknown) => {
      const axiosErr = err as { response?: { data?: { message?: string } } }
      setError(axiosErr.response?.data?.message || 'خطا در دعوت')
    },
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <Users className="h-6 w-6 text-primary" />
          مدیریت تیم
        </h1>
        <p className="text-muted mt-1">اعضای دفتر و دعوت مشاور جدید</p>
      </div>

      <Card>
        <CardContent className="p-4">
          <p className="text-sm font-medium mb-3 flex items-center gap-2">
            <UserPlus className="h-4 w-4" /> دعوت عضو جدید
          </p>
          <div className="flex gap-2">
            <Input
              placeholder="09121234567"
              value={mobile}
              onChange={(e) => setMobile(e.target.value)}
              dir="ltr"
              className="flex-1"
            />
            <Button
              disabled={mobile.length < 11 || inviteMutation.isPending}
              onClick={() => inviteMutation.mutate(mobile)}
            >
              دعوت
            </Button>
          </div>
          {error && <p className="text-sm text-danger mt-2">{error}</p>}
        </CardContent>
      </Card>

      {isLoading ? (
        <div className="flex justify-center py-12">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : (
        <div className="grid gap-3">
          {members?.map((m) => (
            <Card key={m.id} className="glass-hover">
              <CardContent className="flex items-center gap-4 p-4">
                <div className="flex h-11 w-11 items-center justify-center rounded-full bg-primary/20 text-primary font-bold">
                  {m.name?.charAt(0)}
                </div>
                <div className="flex-1">
                  <p className="font-medium">{m.name}</p>
                  <p className="text-sm text-muted" dir="ltr">{m.mobile}</p>
                </div>
                <Badge variant="outline">{m.role_label}</Badge>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
