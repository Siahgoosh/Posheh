import { useQuery } from '@tanstack/react-query'
import { Shield, Building2, Activity, Flag, Box } from 'lucide-react'
import api from '@/lib/api'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

export function AdminPanelPage() {
  const { data: analytics } = useQuery({ queryKey: ['admin-analytics'], queryFn: async () => (await api.get('/admin/analytics')).data })
  const { data: health } = useQuery({ queryKey: ['admin-health'], queryFn: async () => (await api.get('/admin/health-scores')).data.data })
  const { data: mrr } = useQuery({ queryKey: ['admin-mrr'], queryFn: async () => (await api.get('/admin/mrr')).data.data })
  const { data: tourStats } = useQuery({ queryKey: ['admin-tour-stats'], queryFn: async () => (await api.get('/admin/virtual-tour-stats')).data.data })

  const toggleOffice = async (id: number) => {
    await api.post(`/admin/offices/${id}/toggle`)
    window.location.reload()
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold flex items-center gap-2"><Shield className="h-6 w-6 text-primary" />پنل مدیریت پلتفرم</h1>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {[
          { label: 'دفاتر', value: analytics?.total_offices, icon: Building2 },
          { label: 'کاربران', value: analytics?.total_users, icon: Activity },
          { label: 'MRR', value: mrr?.mrr ? `${(mrr.mrr / 1e6).toFixed(0)}M` : '—', icon: Flag },
          { label: 'تور مجازی', value: tourStats?.published_tours, icon: Box },
        ].map((s) => (
          <Card key={s.label} className="p-4"><s.icon className="h-5 w-5 text-primary mb-2" /><p className="text-xs text-muted">{s.label}</p><p className="text-xl font-bold">{s.value ?? '—'}</p></Card>
        ))}
      </div>

      <Card className="p-4">
        <h2 className="font-semibold mb-4">سلامت دفاتر (Health Score)</h2>
        <div className="space-y-2">
          {health?.map((o: { id: number; name: string; health_score: number; is_active: boolean }) => (
            <div key={o.id} className="flex items-center justify-between p-3 rounded-lg bg-muted/10">
              <div className="flex items-center gap-3">
                <span className="font-medium">{o.name}</span>
                <Badge variant={o.health_score >= 70 ? 'default' : 'outline'}>{o.health_score}/100</Badge>
                {!o.is_active && <Badge variant="outline" className="text-danger">تعلیق</Badge>}
              </div>
              <Button size="sm" variant="ghost" onClick={() => toggleOffice(o.id)}>
                {o.is_active ? 'تعلیق' : 'فعال‌سازی'}
              </Button>
            </div>
          ))}
        </div>
      </Card>
    </div>
  )
}
