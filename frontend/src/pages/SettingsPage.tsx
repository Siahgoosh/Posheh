import { useQuery } from '@tanstack/react-query'
import { LogOut, Moon, Sun, Smartphone, User } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import api from '@/lib/api'
import { useAuthStore, useThemeStore } from '@/stores/auth'
import { toPersianDigits } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface Device {
  id: number
  device_name: string
  platform: string
  last_active_at: string
}

export function SettingsPage() {
  const { user, logout, logoutAll } = useAuthStore()
  const { theme, toggleTheme } = useThemeStore()
  const navigate = useNavigate()

  const { data: devices } = useQuery({
    queryKey: ['devices'],
    queryFn: async () => {
      const res = await api.get('/auth/devices')
      return res.data.data as Device[]
    },
  })

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const handleLogoutAll = async () => {
    await logoutAll()
    navigate('/login')
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-2xl">
      <div>
        <h1 className="text-2xl font-bold">تنظیمات</h1>
        <p className="text-muted mt-1">حساب کاربری و ترجیحات</p>
      </div>

      <Card className="overflow-hidden">
        <div className="bg-gradient-to-br from-primary to-accent p-6">
          <div className="flex items-center gap-4">
            <div className="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 text-2xl font-bold text-white">
              {user?.name?.charAt(0)}
            </div>
            <div className="text-white">
              <p className="text-lg font-bold">{user?.name}</p>
              <p className="text-white/80 text-sm">{user?.office?.name}</p>
              <p className="text-white/60 text-xs mt-1" dir="ltr">{user?.mobile && toPersianDigits(user.mobile)}</p>
            </div>
          </div>
        </div>
        <CardContent className="p-4">
          <p className="text-sm text-muted">نقش: {user?.role_label}</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle className="text-base flex items-center gap-2"><User className="h-4 w-4" /> ظاهر</CardTitle></CardHeader>
        <CardContent>
          <Button variant="outline" onClick={toggleTheme} className="w-full justify-start gap-2">
            {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
            {theme === 'dark' ? 'حالت روشن' : 'حالت تاریک'}
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle className="text-base flex items-center gap-2"><Smartphone className="h-4 w-4" /> دستگاه‌های فعال</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {devices?.length ? devices.map((d) => (
            <div key={d.id} className="flex justify-between text-sm p-3 rounded-lg bg-white/5">
              <span>{d.device_name || d.platform}</span>
              <span className="text-muted text-xs">{d.platform}</span>
            </div>
          )) : (
            <p className="text-sm text-muted">دستگاهی ثبت نشده</p>
          )}
        </CardContent>
      </Card>

      <div className="space-y-2">
        <Button variant="outline" className="w-full text-danger border-danger/30" onClick={handleLogout}>
          <LogOut className="h-4 w-4" />
          خروج از این دستگاه
        </Button>
        <Button variant="ghost" className="w-full text-danger" onClick={handleLogoutAll}>
          خروج از همه دستگاه‌ها
        </Button>
      </div>
    </div>
  )
}
