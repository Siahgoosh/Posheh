import { useQuery } from '@tanstack/react-query'
import { Shield, Building2, Users, DollarSign, Settings, MessageSquare, CheckCircle2, AlertTriangle } from 'lucide-react'
import { Link } from 'react-router-dom'
import api from '@/lib/api'
import { formatPrice, formatNumber } from '@/lib/utils'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

interface SmsStatus {
  sms_mode: string
  is_live: boolean
  has_api_key: boolean
  has_credentials: boolean
  has_from_number: boolean
  is_ready: boolean
}

export function AdminDashboardPage() {
  const { data: analytics } = useQuery({
    queryKey: ['admin-analytics'],
    queryFn: async () => (await api.get('/admin/analytics')).data,
  })

  const { data: offices } = useQuery({
    queryKey: ['admin-offices'],
    queryFn: async () => (await api.get('/admin/offices')).data,
  })

  const { data: smsStatus } = useQuery({
    queryKey: ['admin-sms-status'],
    queryFn: async () => (await api.get<{ data: SmsStatus }>('/admin/sms-status')).data.data,
  })

  const stats = [
    { label: 'کل دفاتر', value: analytics?.total_offices, icon: Building2 },
    { label: 'دفاتر فعال', value: analytics?.active_offices, icon: Shield },
    { label: 'کل کاربران', value: analytics?.total_users, icon: Users },
    { label: 'درآمد کل', value: analytics?.total_revenue ? formatPrice(analytics.total_revenue) : '۰', icon: DollarSign },
  ]

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <Shield className="h-6 w-6 text-primary" />
            پنل مدیر کل
          </h1>
          <p className="text-muted mt-1">مدیریت کل پلتفرم پوشه</p>
        </div>
        <Link to="/admin/settings">
          <Button variant="secondary">
            <Settings className="h-4 w-4" />
            تنظیمات سیستم
          </Button>
        </Link>
      </div>

      {smsStatus && (
        <Card className={`glass ${smsStatus.is_ready && smsStatus.is_live ? 'border-success/30' : 'border-warning/30'}`}>
          <CardContent className="p-4 flex flex-wrap items-center justify-between gap-3">
            <div className="flex items-center gap-3">
              <MessageSquare className="h-6 w-6 text-primary" />
              <div>
                <p className="font-medium">وضعیت پیامک IPPanel</p>
                <p className="text-sm text-muted">
                  {smsStatus.is_live
                    ? smsStatus.is_ready
                      ? 'آماده ارسال OTP و دعوت‌نامه'
                      : 'تنظیمات ناقص — کلید API یا شماره ارسال‌کننده را تکمیل کنید'
                    : 'حالت لاگ فعال — OTP واقعی ارسال نمی‌شود'}
                </p>
              </div>
            </div>
            <div className="flex items-center gap-2">
              {smsStatus.is_ready && smsStatus.is_live ? (
                <Badge variant="success"><CheckCircle2 className="h-3 w-3 ml-1" />فعال</Badge>
              ) : (
                <Badge variant="warning"><AlertTriangle className="h-3 w-3 ml-1" />نیاز به تنظیم</Badge>
              )}
              <Link to="/admin/settings">
                <Button size="sm" variant="outline">تنظیم پیامک</Button>
              </Link>
            </div>
          </CardContent>
        </Card>
      )}

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {stats.map((s) => (
          <Card key={s.label} className="glass">
            <CardContent className="p-4 flex items-center gap-3">
              <s.icon className="h-8 w-8 text-primary" />
              <div>
                <p className="text-xs text-muted">{s.label}</p>
                <p className="text-xl font-bold">{typeof s.value === 'number' ? formatNumber(s.value) : s.value}</p>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <div>
        <h2 className="text-lg font-semibold mb-3">دفاتر اخیر</h2>
        <div className="space-y-2">
          {offices?.data?.slice(0, 10).map((office: { id: number; name: string; is_active: boolean; properties_count: number; users?: unknown[] }) => (
            <Card key={office.id} className="glass-hover">
              <CardContent className="p-4 flex items-center justify-between">
                <div>
                  <p className="font-medium">{office.name}</p>
                  <p className="text-sm text-muted">{office.properties_count} ملک · {office.users?.length ?? 0} کاربر</p>
                </div>
                <span className={`text-xs px-2 py-1 rounded-full ${office.is_active ? 'bg-success/20 text-success' : 'bg-danger/20 text-danger'}`}>
                  {office.is_active ? 'فعال' : 'غیرفعال'}
                </span>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </div>
  )
}
