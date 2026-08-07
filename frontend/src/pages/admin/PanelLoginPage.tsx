import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useNavigate } from 'react-router-dom'
import { Shield, KeyRound } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import api from '@/lib/api'
import { getDeviceId, getDeviceName, getPlatform } from '@/lib/device'
import { useAuthStore } from '@/stores/auth'
import { normalizeMobile } from '@/lib/utils'
import { isPlatformStaffRole } from '@/lib/subdomain'

/** ورود فقط برای مدیران پلتفرم */
export function PanelLoginPage() {
  const [login, setLogin] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const { setAuth } = useAuthStore()
  const navigate = useNavigate()

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')

    if (/^09\d{9}$/.test(normalizeMobile(login))) {
      setError('ورود پنل با ایمیل یا نام کاربری و رمز عبور انجام می‌شود.')
      return
    }

    setLoading(true)
    try {
      const { data } = await api.post('/auth/login', {
        login: login.includes('@') ? login.trim() : login.trim().toLowerCase(),
        password,
        device_id: getDeviceId(),
        device_name: getDeviceName(),
        platform: getPlatform(),
      })
      if (!isPlatformStaffRole(data.user?.role)) {
        setError('این حساب دسترسی پنل مدیریت ندارد.')
        return
      }
      setAuth(data.user, data.token)
      navigate('/')
    } catch (err: unknown) {
      const axiosErr = err as {
        response?: { status?: number; data?: { errors?: Record<string, string[]>; message?: string } }
        message?: string
        code?: string
      }
      if (!axiosErr.response) {
        setError(
          axiosErr.code === 'ECONNABORTED'
            ? 'زمان پاسخ سرور تمام شد. دوباره تلاش کنید.'
            : 'ارتباط با سرور برقرار نشد (ممکن است خطای 502 باشد). چند دقیقه بعد دوباره امتحان کنید.',
        )
        return
      }
      const status = axiosErr.response.status
      if (status === 502 || status === 503 || status === 504) {
        setError('سرور موقتاً در دسترس نیست (خطای '+status+'). لطفاً چند دقیقه بعد دوباره تلاش کنید.')
        return
      }
      setError(
        axiosErr.response?.data?.errors?.login?.[0]
          || axiosErr.response?.data?.message
          || 'خطا در ورود',
      )
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center p-4 bg-background">
      <Card className="w-full max-w-md">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Shield className="h-5 w-5 text-primary" />
            ورود پنل مدیریت
          </CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleLogin} className="space-y-4">
            <Input
              placeholder="ایمیل یا نام کاربری"
              value={login}
              onChange={(e) => setLogin(e.target.value)}
              dir="ltr"
              autoComplete="username"
              required
            />
            <Input
              type="password"
              placeholder="رمز عبور"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              autoComplete="current-password"
              required
            />
            {error && <p className="text-sm text-danger">{error}</p>}
            <Button type="submit" className="w-full gap-2" disabled={loading}>
              <KeyRound className="h-4 w-4" />
              {loading ? 'ورود…' : 'ورود'}
            </Button>
            <p className="text-center text-sm">
              <Link to="/forgot-password" className="text-primary hover:underline">فراموشی رمز عبور</Link>
            </p>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
