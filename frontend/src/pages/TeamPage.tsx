import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Users, UserPlus, Phone } from 'lucide-react'
import api from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

interface TeamMember {
  id: number
  name: string
  mobile: string
  role: string
  role_label: string
  is_active: boolean
}

export function TeamPage() {
  const { user } = useAuthStore()
  const queryClient = useQueryClient()
  const [mobile, setMobile] = useState('')
  const [message, setMessage] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['team'],
    queryFn: async () => (await api.get('/office/team')).data.data as TeamMember[],
  })

  const inviteMutation = useMutation({
    mutationFn: async () => api.post('/office/invite', { mobile }),
    onSuccess: () => {
      setMessage('دعوتنامه ارسال شد و پیامک برای مشاور فرستاده شد')
      setMobile('')
      queryClient.invalidateQueries({ queryKey: ['team'] })
    },
    onError: () => setMessage('خطا در ارسال دعوتنامه'),
  })

  const canInvite = user?.role === 'office_manager' || user?.role === 'super_admin'

  return (
    <div className="space-y-6 animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <Users className="h-6 w-6 text-primary" />
          مدیریت تیم
        </h1>
        <p className="text-muted mt-1">اعضای دفتر املاک</p>
      </div>

      {canInvite && (
        <Card className="glass">
          <CardContent className="p-4">
            <h3 className="font-semibold mb-3 flex items-center gap-2">
              <UserPlus className="h-4 w-4" />
              دعوت مشاور جدید
            </h3>
            <div className="flex gap-3">
              <Input
                placeholder="09121234567"
                value={mobile}
                onChange={(e) => setMobile(e.target.value)}
                dir="ltr"
                className="max-w-xs"
              />
              <Button onClick={() => inviteMutation.mutate()} disabled={inviteMutation.isPending || mobile.length < 11}>
                ارسال دعوت
              </Button>
            </div>
            {message && <p className="text-sm text-primary mt-2">{message}</p>}
          </CardContent>
        </Card>
      )}

      {isLoading ? (
        <div className="flex justify-center py-20">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {data?.map((member) => (
            <Card key={member.id} className="glass-hover">
              <CardContent className="p-5">
                <div className="flex items-center gap-4">
                  <div className="h-12 w-12 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-lg">
                    {member.name.charAt(0)}
                  </div>
                  <div className="flex-1">
                    <p className="font-semibold">{member.name}</p>
                    <p className="text-sm text-muted flex items-center gap-1" dir="ltr">
                      <Phone className="h-3 w-3" />{member.mobile}
                    </p>
                  </div>
                </div>
                <div className="mt-3 flex gap-2">
                  <Badge>{member.role_label}</Badge>
                  <Badge variant={member.is_active ? 'default' : 'outline'}>
                    {member.is_active ? 'فعال' : 'غیرفعال'}
                  </Badge>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
