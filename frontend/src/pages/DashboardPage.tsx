import { useQuery } from '@tanstack/react-query'
import { motion } from 'framer-motion'
import { CheckCircle2, Building2, Clock, Users, AlertTriangle, Plus, TrendingUp } from 'lucide-react'
import { Link } from 'react-router-dom'
import api from '@/lib/api'
import { formatNumber } from '@/lib/utils'
import { taskStatusLabel } from '@/constants/plans'
import { WalletCard } from '@/components/wallet/WalletCard'
import { useAuthStore } from '@/stores/auth'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

interface DashboardData {
  stats: {
    total_properties: number
    active_properties: number
    expired_properties: number
    today_properties: number
    expiring_soon: number
    team_members: number
    pending_tasks: number
  }
  recent_properties: PropertyItem[]
  expiring_properties: PropertyItem[]
  activities: ActivityItem[]
  tasks: TaskItem[]
}

interface PropertyItem {
  id: number
  code: string
  type_label: string
  price?: number
  city?: string
  status_label: string
  created_at_jalali: string
}

interface ActivityItem {
  id: number
  description: string
  created_at_jalali: string
  user?: { name: string }
}

interface TaskItem {
  id: number
  title: string
  status: string
  due_at_jalali?: string
}

const statCards = [
  { key: 'total_properties', label: 'کل املاک', icon: Building2, color: 'text-primary' },
  { key: 'active_properties', label: 'املاک فعال', icon: TrendingUp, color: 'text-success' },
  { key: 'today_properties', label: 'امروز', icon: Clock, color: 'text-accent' },
  { key: 'expiring_soon', label: 'در حال انقضا', icon: AlertTriangle, color: 'text-warning' },
  { key: 'team_members', label: 'اعضای تیم', icon: Users, color: 'text-primary' },
] as const

export function DashboardPage() {
  const { user } = useAuthStore()
  const { data, isLoading } = useQuery<DashboardData>({
    queryKey: ['dashboard'],
    queryFn: async () => {
      const res = await api.get('/dashboard')
      return res.data
    },
  })

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  return (
    <div className="space-y-8 animate-fade-in">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">داشبورد</h1>
          <p className="text-muted mt-1">خلاصه وضعیت دفتر املاک</p>
        </div>
        <Link to="/properties/new">
          <Button>
            <Plus className="h-4 w-4" />
            ثبت ملک جدید
          </Button>
        </Link>
      </div>

      <div className="grid lg:grid-cols-3 gap-4">
        <div className="lg:col-span-2">
          <Card className="border-success/20 bg-success/5">
            <CardContent className="p-4 flex flex-wrap items-center justify-between gap-3">
              <div>
                <p className="text-sm text-muted">وضعیت اشتراک</p>
                <p className="font-semibold">
                  {user?.office?.plan?.name || 'بدون پلن'}
                  {user?.office?.on_trial && user?.office?.trial_label && (
                    <span className="text-warning text-sm mr-2">({user.office.trial_label})</span>
                  )}
                </p>
              </div>
              <Link to="/subscription">
                <Button variant="outline" size="sm">مدیریت اشتراک</Button>
              </Link>
            </CardContent>
          </Card>
        </div>
        <WalletCard compact />
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
        {statCards.map((stat, i) => (
          <motion.div
            key={stat.key}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: i * 0.05 }}
          >
            <Card className="!p-4">
              <div className="flex items-center gap-3">
                <div className={`p-2 rounded-xl bg-white/5 ${stat.color}`}>
                  <stat.icon className="h-5 w-5" />
                </div>
                <div>
                  <p className="text-2xl font-bold">
                    {formatNumber(data?.stats[stat.key] ?? 0)}
                  </p>
                  <p className="text-xs text-muted">{stat.label}</p>
                </div>
              </div>
            </Card>
          </motion.div>
        ))}
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>املاک اخیر</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {data?.recent_properties?.map((property) => (
              <Link
                key={property.id}
                to={`/properties/${property.id}`}
                className="flex items-center justify-between p-3 rounded-xl glass-hover"
              >
                <div>
                  <p className="font-medium">{property.code}</p>
                  <p className="text-xs text-muted">{property.type_label} · {property.city}</p>
                </div>
                <Badge variant="outline">{property.status_label}</Badge>
              </Link>
            ))}
            {!data?.recent_properties?.length && (
              <p className="text-center text-muted py-8">هنوز ملکی ثبت نشده</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <AlertTriangle className="h-4 w-4 text-warning" />
              در حال انقضا
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {data?.expiring_properties?.map((property) => (
              <Link
                key={property.id}
                to={`/properties/${property.id}`}
                className="flex items-center justify-between p-3 rounded-xl glass-hover"
              >
                <div>
                  <p className="font-medium">{property.code}</p>
                  <p className="text-xs text-muted">{property.type_label}</p>
                </div>
                <Badge variant="warning">انقضا نزدیک</Badge>
              </Link>
            ))}
            {!data?.expiring_properties?.length && (
              <p className="text-center text-muted py-8">ملکی در حال انقضا نیست</p>
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <CheckCircle2 className="h-4 w-4 text-primary" />
            وظایف
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-2">
          {data?.tasks?.map((task) => (
            <div key={task.id} className="flex items-center gap-3 p-3 rounded-xl glass-hover">
              <div className={`h-2 w-2 rounded-full ${task.status === 'completed' ? 'bg-success' : task.status === 'in_progress' ? 'bg-accent' : 'bg-muted'}`} />
              <div className="flex-1 min-w-0">
                <span className={`text-sm ${task.status === 'completed' ? 'line-through text-muted' : ''}`}>
                  {task.title}
                </span>
                <div className="flex items-center gap-2 text-xs text-muted mt-0.5">
                  <span>{taskStatusLabel(task.status)}</span>
                  {task.due_at_jalali && <span>· موعد: {task.due_at_jalali}</span>}
                </div>
              </div>
            </div>
          ))}
          {!data?.tasks?.length && (
            <p className="text-center text-muted py-4 text-sm">وظیفه‌ای ندارید</p>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>فعالیت‌های اخیر</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {data?.activities?.map((activity) => (
            <div key={activity.id} className="flex items-center gap-3 p-3 rounded-xl glass-hover">
              <div className="h-2 w-2 rounded-full bg-primary" />
              <div className="flex-1">
                <p className="text-sm">{activity.description}</p>
                <p className="text-xs text-muted">
                  {activity.user?.name} · {activity.created_at_jalali}
                </p>
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
