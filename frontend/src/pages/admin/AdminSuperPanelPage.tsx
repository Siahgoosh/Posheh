import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import {
  Users,
  Building2,
  Eye,
  Download,
  TrendingUp,
  BookOpen,
  ArrowRight,
  BarChart3,
  Wallet,
  LogIn,
  MessageSquare,
  Settings,
  Shield,
} from 'lucide-react'
import api from '@/lib/api'
import { formatNumber } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface DashboardData {
  users: { total: number; active: number; new_today: number; new_week: number; super_admins: number; managers: number; consultants: number }
  offices: { total: number; active: number; on_trial: number }
  blog: { total_posts: number; published_posts: number; total_views: number; top_posts: { title: string; views: number; slug: string }[] }
  traffic: { views_today: number; views_week: number; views_month: number; unique_today: number; unique_week: number }
  downloads: { clicks_today: number; clicks_week: number; by_platform_week: Record<string, number> }
  revenue: { total: number; monthly: number; paid_count: number }
  auth: { otp_sent_today: number; logins_today: number; logins_week: number }
  top_pages: { path: string; views: number }[]
  top_referrers: { referrer: string; views: number }[]
  visits_chart: { date: string; views: number; unique: number }[]
  registrations_chart: { date: string; count: number }[]
  system?: {
    sms: { sms_mode: string; is_live: boolean; is_ready: boolean; has_credentials: boolean }
    app_env: string
    app_debug: boolean
  }
}

function StatCard({ icon: Icon, label, value, sub }: { icon: typeof Users; label: string; value: string | number; sub?: string }) {
  return (
    <Card className="glass">
      <CardContent className="pt-6">
        <div className="flex items-start justify-between">
          <div>
            <p className="text-sm text-muted">{label}</p>
            <p className="text-2xl font-bold mt-1">{typeof value === 'number' ? formatNumber(value) : value}</p>
            {sub && <p className="text-xs text-muted mt-1">{sub}</p>}
          </div>
          <div className="rounded-xl bg-primary/15 p-2 text-primary">
            <Icon className="h-5 w-5" />
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

function MiniBarChart({ data, valueKey, label }: { data: { date: string; [k: string]: string | number }[]; valueKey: string; label: string }) {
  const max = Math.max(...data.map((d) => Number(d[valueKey]) || 0), 1)

  return (
    <div className="space-y-2">
      <p className="text-sm font-medium">{label}</p>
      <div className="flex items-end gap-1 h-24">
        {data.slice(-14).map((row) => (
          <div key={row.date} className="flex-1 flex flex-col items-center gap-1">
            <div
              className="w-full rounded-t bg-primary/70 min-h-[4px]"
              style={{ height: `${(Number(row[valueKey]) / max) * 100}%` }}
              title={`${row.date}: ${row[valueKey]}`}
            />
          </div>
        ))}
      </div>
    </div>
  )
}

export function AdminSuperPanelPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-marketing'],
    queryFn: async () => {
      const res = await api.get('/admin/marketing')
      return res.data.data as DashboardData
    },
    refetchInterval: 60000,
  })

  if (isLoading || !data) {
    return <div className="p-8 text-center text-muted">بارگذاری پنل مارکتینگ…</div>
  }

  return (
    <div className="space-y-8 animate-fade-in max-w-6xl mx-auto">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <BarChart3 className="h-7 w-7 text-primary" />
            پنل مدیر کل — مارکتینگ
          </h1>
          <p className="text-sm text-muted mt-1">آمار کاربران، بازدید، وبلاگ، دانلود و درآمد</p>
        </div>
        <div className="flex gap-2 flex-wrap">
          <Link to="/admin/plans"><Button variant="outline" size="sm">پلن‌ها</Button></Link>
          <Link to="/admin/offices"><Button variant="outline" size="sm">دفاتر</Button></Link>
          <Link to="/admin/tickets"><Button variant="outline" size="sm">تیکت‌ها</Button></Link>
          <Link to="/admin/blog"><Button variant="outline" size="sm">وبلاگ</Button></Link>
          <Link to="/admin/downloads"><Button variant="outline" size="sm">دانلودها</Button></Link>
          <Link to="/settings"><Button variant="outline" size="sm"><Settings className="h-3 w-3 ml-1" /> تنظیمات</Button></Link>
        </div>
      </div>

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard icon={Users} label="کل کاربران" value={data.users.total} sub={`${formatNumber(data.users.new_week)} جدید این هفته`} />
        <StatCard icon={Eye} label="بازدید امروز" value={data.traffic.views_today} sub={`${formatNumber(data.traffic.unique_today)} یکتا`} />
        <StatCard icon={Eye} label="بازدید ماه" value={data.traffic.views_month} sub={`${formatNumber(data.traffic.unique_week)} یکتا هفته`} />
        <StatCard icon={LogIn} label="ورود امروز" value={data.auth.logins_today} sub={`${formatNumber(data.auth.otp_sent_today)} OTP ارسالی`} />
      </div>

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard icon={Building2} label="دفاتر فعال" value={data.offices.active} sub={`${formatNumber(data.offices.on_trial)} در دوره آزمایشی`} />
        <StatCard icon={Shield} label="مدیران / مشاوران" value={data.users.managers + data.users.consultants} sub={`${formatNumber(data.users.super_admins)} مدیر کل`} />
        <StatCard icon={BookOpen} label="بازدید وبلاگ" value={data.blog.total_views} sub={`${formatNumber(data.blog.published_posts)} مقاله منتشر`} />
        <StatCard icon={Download} label="کلیک دانلود هفته" value={data.downloads.clicks_week} sub={`${formatNumber(data.downloads.clicks_today)} امروز`} />
      </div>

      {data.system?.sms && (
        <Card className={data.system.sms.is_live && data.system.sms.is_ready ? 'border-success/30' : 'border-warning/30'}>
          <CardHeader>
            <CardTitle className="text-sm flex items-center gap-2">
              <MessageSquare className="h-4 w-4" />
              وضعیت پیامک OTP
            </CardTitle>
          </CardHeader>
          <CardContent className="flex flex-wrap gap-4 text-sm">
            <span>حالت: <strong>{data.system.sms.sms_mode}</strong></span>
            <span>ارسال زنده: <strong className={data.system.sms.is_live ? 'text-success' : 'text-warning'}>{data.system.sms.is_live ? 'فعال' : 'غیرفعال (تست ۱۲۳۴۵۶)'}</strong></span>
            <span>آماده ارسال: <strong>{data.system.sms.is_ready ? 'بله' : 'خیر — IPPanel را تنظیم کنید'}</strong></span>
            <span className="text-muted">محیط: {data.system.app_env}</span>
          </CardContent>
        </Card>
      )}

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard icon={Wallet} label="درآمد ماه" value={formatNumber(data.revenue.monthly)} sub={`${formatNumber(data.revenue.paid_count)} پرداخت موفق`} />
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader><CardTitle className="flex items-center gap-2"><TrendingUp className="h-5 w-5" /> نمودار بازدید (۱۴ روز)</CardTitle></CardHeader>
          <CardContent>
            <MiniBarChart data={data.visits_chart} valueKey="views" label="بازدید صفحات" />
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>ثبت‌نام کاربران</CardTitle></CardHeader>
          <CardContent>
            <MiniBarChart data={data.registrations_chart} valueKey="count" label="کاربر جدید" />
          </CardContent>
        </Card>
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader><CardTitle>پربازدیدترین صفحات (۳۰ روز)</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {data.top_pages.length === 0 ? (
              <p className="text-sm text-muted">هنوز داده‌ای ثبت نشده — بازدید از صفحات عمومی ردیابی می‌شود.</p>
            ) : (
              data.top_pages.map((p) => (
                <div key={p.path} className="flex justify-between text-sm border-b border-card-border pb-2">
                  <span dir="ltr">{p.path}</span>
                  <span className="text-muted">{formatNumber(p.views)}</span>
                </div>
              ))
            )}
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>منابع ورودی (Referrer)</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {data.top_referrers.length === 0 ? (
              <p className="text-sm text-muted">ترافیک مستقیم یا بدون referrer</p>
            ) : (
              data.top_referrers.map((r) => (
                <div key={r.referrer} className="flex justify-between gap-2 text-sm border-b border-card-border pb-2">
                  <span className="truncate" dir="ltr">{r.referrer}</span>
                  <span className="text-muted shrink-0">{formatNumber(r.views)}</span>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader><CardTitle>پربازدیدترین مقالات</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {data.blog.top_posts.map((post) => (
            <div key={post.slug} className="flex justify-between items-center gap-2 text-sm">
              <a href={`/blog/${post.slug}`} target="_blank" rel="noreferrer" className="hover:text-primary">{post.title}</a>
              <span className="text-muted">{formatNumber(post.views)} بازدید</span>
            </div>
          ))}
        </CardContent>
      </Card>

      <div className="flex gap-2">
        <Link to="/dashboard"><Button variant="ghost"><ArrowRight className="h-4 w-4" /> داشبورد املاک</Button></Link>
      </div>
    </div>
  )
}
