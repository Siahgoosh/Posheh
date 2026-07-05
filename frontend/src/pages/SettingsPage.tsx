import { useAuthStore, useThemeStore } from '@/stores/auth'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Moon, Sun, Smartphone, Building2 } from 'lucide-react'

export function SettingsPage() {
  const { user } = useAuthStore()
  const { theme, toggleTheme } = useThemeStore()

  return (
    <div className="space-y-6 animate-fade-in max-w-2xl">
      <div>
        <h1 className="text-2xl font-bold">تنظیمات</h1>
        <p className="text-muted mt-1">پیکربندی حساب و نمایش</p>
      </div>

      <Card className="glass">
        <CardHeader><CardTitle>پروفایل</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <div className="flex items-center gap-4">
            <div className="h-16 w-16 rounded-2xl bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white text-2xl font-bold">
              {user?.name?.charAt(0)}
            </div>
            <div>
              <p className="font-semibold text-lg">{user?.name}</p>
              <p className="text-muted" dir="ltr">{user?.mobile}</p>
              <p className="text-sm text-primary">{user?.role_label}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card className="glass">
        <CardHeader><CardTitle>دفتر</CardTitle></CardHeader>
        <CardContent>
          <div className="flex items-center gap-3">
            <Building2 className="h-5 w-5 text-primary" />
            <span>{user?.office?.name || '—'}</span>
          </div>
        </CardContent>
      </Card>

      <Card className="glass">
        <CardHeader><CardTitle>ظاهر</CardTitle></CardHeader>
        <CardContent>
          <Button variant="secondary" onClick={toggleTheme}>
            {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
            {theme === 'dark' ? 'حالت روشن' : 'حالت تاریک'}
          </Button>
        </CardContent>
      </Card>

      <Card className="glass">
        <CardHeader><CardTitle>اپلیکیشن موبایل و ویندوز</CardTitle></CardHeader>
        <CardContent className="space-y-2 text-sm text-muted">
          <p className="flex items-center gap-2"><Smartphone className="h-4 w-4" /> اندروید و iOS: پوشه Flutter</p>
          <p>ویندوز: <code className="text-primary">flutter build windows</code> در پوشه mobile</p>
          <p>API: <code className="text-primary" dir="ltr">/api/v1</code></p>
        </CardContent>
      </Card>
    </div>
  )
}
