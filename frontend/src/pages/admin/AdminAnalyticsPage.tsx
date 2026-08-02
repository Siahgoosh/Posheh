import { useQuery } from '@tanstack/react-query'
import { Download, Users, Activity, Globe, Smartphone, Monitor } from 'lucide-react'
import api from '@/lib/api'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { formatNumber } from '@/lib/utils'

interface PlatformRow {
  id: number
  name: string
  mobile: string
  email?: string
  platform: string
  platform_label: string
  app_version?: string
  is_active: boolean
  account_active: boolean
  office_name?: string
  last_active_at?: string
}

export function AdminAnalyticsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-platform-analytics'],
    queryFn: async () => (await api.get('/admin/platform-analytics')).data.data as {
      marketing: { traffic: { views_today: number; views_month: number; unique_month: number }; downloads: { by_platform_week: Record<string, number> } }
      platform_users: {
        traffic: { views_today: number; views_month: number; unique_month: number }
        users: {
          total: number
          active: number
          inactive: number
          by_platform: Record<string, number>
          active_by_platform: Record<string, number>
          inactive_by_platform: Record<string, number>
        }
        rows: PlatformRow[]
      }
    },
  })

  const traffic = data?.platform_users?.traffic
  const users = data?.platform_users?.users
  const rows = data?.platform_users?.rows ?? []

  const exportExcel = async () => {
    const res = await api.get('/admin/platform-analytics/export', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const a = document.createElement('a')
    a.href = url
    a.download = `posheh-users-${new Date().toISOString().slice(0, 10)}.xlsx`
    a.click()
    window.URL.revokeObjectURL(url)
  }

  const platformIcon = (p: string) => {
    if (p === 'android') return <Smartphone className="h-4 w-4" />
    if (p === 'windows') return <Monitor className="h-4 w-4" />
    return <Globe className="h-4 w-4" />
  }

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <AdminPageHeader title="تحلیل بازدید و کاربران" description="بازدید وبسایت، کاربران فعال/غیرفعال به تفکیک پلتفرم" />
        <Button onClick={exportExcel}>
          <Download className="h-4 w-4" />
          دانلود اکسل
        </Button>
      </div>

      {isLoading ? (
        <p className="text-muted text-center py-12">در حال بارگذاری…</p>
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card className="glass"><CardContent className="pt-6"><p className="text-sm text-muted">بازدید امروز</p><p className="text-2xl font-bold">{formatNumber(traffic?.views_today ?? 0)}</p></CardContent></Card>
            <Card className="glass"><CardContent className="pt-6"><p className="text-sm text-muted">بازدید ۳۰ روز</p><p className="text-2xl font-bold">{formatNumber(traffic?.views_month ?? 0)}</p></CardContent></Card>
            <Card className="glass"><CardContent className="pt-6"><p className="text-sm text-muted">بازدیدکننده یکتا</p><p className="text-2xl font-bold">{formatNumber(traffic?.unique_month ?? 0)}</p></CardContent></Card>
            <Card className="glass"><CardContent className="pt-6"><p className="text-sm text-muted flex items-center gap-1"><Users className="h-4 w-4" />کاربران فعال</p><p className="text-2xl font-bold">{formatNumber(users?.active ?? 0)} <span className="text-sm text-muted font-normal">/ {formatNumber(users?.total ?? 0)}</span></p></CardContent></Card>
          </div>

          <Card>
            <CardHeader><CardTitle className="flex items-center gap-2"><Activity className="h-5 w-5" />کاربران به تفکیک پلتفرم (۳۰ روز اخیر)</CardTitle></CardHeader>
            <CardContent className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
              {(['android', 'windows', 'pwa', 'web'] as const).map((p) => (
                <div key={p} className="rounded-xl border border-card-border p-4">
                  <div className="flex items-center gap-2 text-sm text-muted mb-2">{platformIcon(p)} {p === 'pwa' ? 'PWA / iPhone' : p}</div>
                  <p className="text-lg font-bold">{formatNumber(users?.by_platform?.[p] ?? 0)} کل</p>
                  <p className="text-xs text-success mt-1">فعال: {formatNumber(users?.active_by_platform?.[p] ?? 0)}</p>
                  <p className="text-xs text-muted">غیرفعال: {formatNumber(users?.inactive_by_platform?.[p] ?? 0)}</p>
                </div>
              ))}
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle>لیست کاربران با جزئیات</CardTitle></CardHeader>
            <CardContent className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-muted border-b border-card-border">
                    <th className="text-right py-2 px-2">نام</th>
                    <th className="text-right py-2 px-2">موبایل</th>
                    <th className="text-right py-2 px-2">ایمیل</th>
                    <th className="text-right py-2 px-2">دفتر</th>
                    <th className="text-right py-2 px-2">پلتفرم</th>
                    <th className="text-right py-2 px-2">نسخه</th>
                    <th className="text-right py-2 px-2">وضعیت</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row) => (
                    <tr key={row.id} className="border-b border-card-border/50">
                      <td className="py-2 px-2 font-medium">{row.name}</td>
                      <td className="py-2 px-2" dir="ltr">{row.mobile}</td>
                      <td className="py-2 px-2" dir="ltr">{row.email || '—'}</td>
                      <td className="py-2 px-2">{row.office_name || '—'}</td>
                      <td className="py-2 px-2">{row.platform_label}</td>
                      <td className="py-2 px-2" dir="ltr">{row.app_version || '—'}</td>
                      <td className="py-2 px-2">
                        <Badge variant={row.is_active ? 'default' : 'outline'}>{row.is_active ? 'فعال' : 'غیرفعال'}</Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </CardContent>
          </Card>
        </>
      )}
    </div>
  )
}
