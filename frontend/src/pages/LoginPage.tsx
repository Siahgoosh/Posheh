import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import { Building2, KeyRound, Mail } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import api from '@/lib/api'
import { getDeviceId, getDeviceName, getPlatform } from '@/lib/device'
import { useAuthStore } from '@/stores/auth'
import { toEnglishDigits, normalizeMobile } from '@/lib/utils'
import { isPanelSubdomain, isPlatformStaffRole } from '@/lib/subdomain'

const LEGACY_MOBILE_HINT =
  'حساب شما قبلاً با شماره موبایل ثبت شده است. لطفاً ایمیل یا نام کاربری و رمز عبور را وارد کنید. اگر رمز ندارید با پشتیبانی تماس بگیرید.'

export function LoginPage({ panelMode = false }: { panelMode?: boolean }) {
  const [login, setLogin] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const { setAuth } = useAuthStore()
  const navigate = useNavigate()

  const looksLikeMobile = (value: string) => /^09\d{9}$/.test(normalizeMobile(value))

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')

    const loginValue = login.includes('@') ? login.trim() : login.trim().toLowerCase()

    if (looksLikeMobile(loginValue)) {
      setError(LEGACY_MOBILE_HINT)
      return
    }

    setLoading(true)
    try {
      const { data } = await api.post('/auth/login', {
        login: loginValue,
        password,
        device_id: getDeviceId(),
        device_name: getDeviceName(),
        platform: getPlatform(),
      })
      setAuth(data.user, data.token)
      const isPanel = panelMode || isPanelSubdomain()
      if (isPanel && isPlatformStaffRole(data.user?.role)) {
        navigate('/')
      } else if (isPanel && !isPlatformStaffRole(data.user?.role)) {
        setError('این حساب دسترسی پنل مدیریت ندارد.')
        return
      } else {
        navigate(data.subscription_expired ? '/renew' : '/dashboard')
      }
    } catch (err: unknown) {
      const axiosErr = err as {
        response?: { data?: { message?: string; errors?: Record<string, string[]> } }
      }
      setError(
        axiosErr.response?.data?.errors?.login?.[0]
          || axiosErr.response?.data?.errors?.password?.[0]
          || axiosErr.response?.data?.message
          || 'خطا در ورود. دوباره تلاش کنید.'
      )
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center p-4">
      <div className="absolute inset-0 overflow-hidden">
        <div className="absolute -top-40 -right-40 h-80 w-80 rounded-full bg-primary/20 blur-3xl" />
        <div className="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-accent/20 blur-3xl" />
      </div>

      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        className="relative w-full max-w-md"
      >
        <div className="mb-8 text-center">
          <Link to="/" className="text-sm text-muted hover:text-primary mb-4 inline-block">← بازگشت به صفحه اصلی</Link>
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-accent shadow-lg shadow-primary/30">
            <Building2 className="h-8 w-8 text-white" />
          </div>
          <h1 className="text-3xl font-bold gradient-text">پوشه</h1>
          <p className="mt-2 text-muted">سامانه ابری ثبت و مدیریت املاک</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <KeyRound className="h-5 w-5 text-primary" />
              ورود به حساب
            </CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleLogin} className="space-y-4">
              <div>
                <label className="mb-2 block text-sm text-muted">ایمیل یا نام کاربری</label>
                <div className="relative">
                  <Mail className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                  <Input
                    type="text"
                    placeholder="email@example.com یا username"
                    value={login}
                    onChange={(e) => setLogin(e.target.value)}
                    dir="ltr"
                    className="pr-10"
                    autoComplete="username"
                    required
                  />
                </div>
              </div>
              <div>
                <label className="mb-2 block text-sm text-muted">رمز عبور</label>
                <Input
                  type="password"
                  placeholder="رمز عبور"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  autoComplete="current-password"
                  required
                />
              </div>
              {error && <p className="text-sm text-danger">{error}</p>}
              <Button type="submit" className="w-full" disabled={loading || !login || !password}>
                {loading ? 'در حال ورود...' : 'ورود'}
              </Button>
              <p className="text-center text-xs text-muted">
                اگر قبلاً فقط با شماره موبایل ثبت‌نام کرده‌اید، ایمیل و نام کاربری را از پشتیبانی بگیرید و رمز تنظیم کنید.
              </p>
              <p className="text-center text-sm text-muted">
                حساب ندارید؟ <Link to="/register" className="text-primary hover:underline">ثبت‌نام</Link>
              </p>
            </form>
          </CardContent>
        </Card>
      </motion.div>
    </div>
  )
}
