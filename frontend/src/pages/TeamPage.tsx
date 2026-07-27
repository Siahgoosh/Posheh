import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Users, UserPlus } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

export function TeamPage() {
  const [mobile, setMobile] = useState('')
  const { data: team, refetch } = useQuery({
    queryKey: ['team'],
    queryFn: async () => (await api.get('/office/team')).data.data,
  })

  const invite = async () => {
    if (!mobile) return
    await api.post('/office/invite', { mobile, role: 'consultant' })
    setMobile('')
    refetch()
    alert('دعوت ارسال شد')
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold flex items-center gap-2"><Users className="h-6 w-6 text-primary" />مدیریت تیم</h1>
      <Card className="p-4 flex gap-2">
        <Input placeholder="موبایل مشاور جدید" value={mobile} onChange={(e) => setMobile(e.target.value)} />
        <Button onClick={invite}><UserPlus className="h-4 w-4" />دعوت</Button>
      </Card>
      <div className="space-y-2">
        {team?.map((m: { id: number; name: string; mobile: string; role_label: string; is_active: boolean }) => (
          <Card key={m.id} className="p-4 flex justify-between items-center">
            <div>
              <p className="font-medium">{m.name}</p>
              <p className="text-xs text-muted">{m.mobile}</p>
            </div>
            <Badge>{m.role_label}</Badge>
          </Card>
        ))}
      </div>
    </div>
  )
}
