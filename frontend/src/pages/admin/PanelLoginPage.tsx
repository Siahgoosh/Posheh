import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Shield, Smartphone } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import api from '@/lib/api'
import { getDeviceId, getDeviceName, getPlatform } from '@/lib/device'
import { useAuthStore } from '@/stores/auth'
import { toEnglishDigits, toPersianDigits, normalizeMobile } from '@/lib/utils'
import { isPlatformStaffRole } from '@/lib/subdomain'

/** ورود فقط برای مدیران پلتفرم — بدون لندینگ و بدون ثبت‌نام */
export function PanelLoginPage() {
  const [step, setStep] = useState<'mobile' | 'otp'>('mobile')
  const [mobile, setMobile] = useState('')
  const [otp, setOtp] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [countdown, setCountdown] = useState(0)
  const [otpInputKey, setOtpInputKey] = useState(0)
  const [devHint, setDevHint] = useState('')
  const { setAuth } = useAuthStore()
  const navigate = useNavigate()

  const handleSendOtp = async (e?: React.FormEvent) => {
    e?.preventDefault()
    setError('')
    setDevHint('')
    setOtp('')
    setOtpInputKey((k) => k + 1)
    setLoading(true)
    const normalizedMobile = normalizeMobile(mobile)
    try {
      const res = await api.post('/auth/otp/send', { mobile: normalizedMobile }, { timeout: 12000 })
      if (res.data.dev_hint) setDevHint(res.data.dev_hint)
      setMobile(normalizedMobile)
      setStep('otp')
      setCountdown(120)
      const timer = setInterval(() => {
        setCountdown((c) => {
          if (c <= 1) { clearInterval(timer); return 0 }
          return c - 1
        })
      }, 1000)
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      setError(axiosErr.response?.data?.errors?.mobile?.[0] || axiosErr.response?.data?.message || 'خطا در ارسال کد')
    } finally {
      setLoading(false)
    }
  }

  const handleVerifyOtp = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const { data } = await api.post('/auth/otp/verify', {
        mobile: normalizeMobile(mobile),
        code: toEnglishDigits(otp).replace(/\D/g, '').slice(0, 6).padStart(6, '0'),
        device_id: getDeviceId(),
        device_name: getDeviceName(),
        platform: getPlatform(),
      })
      if (!isPlatformStaffRole(data.user?.role)) {
        setError('این شماره دسترسی پنل مدیریت ندارد. فقط مدیران پلتفرم می‌توانند وارد شوند.')
        return
      }
      setAuth(data.user, data.token)
      navigate('/')
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      setError(axiosErr.response?.data?.errors?.code?.[0] || axiosErr.response?.data?.message || 'کد نامعتبر است')
      setOtp('')
      setOtpInputKey((k) => k + 1)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-background p-4">
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className="absolute top-0 right-0 h-96 w-96 rounded-full bg-primary/10 blur-3xl" />
        <div className="absolute bottom-0 left-0 h-64 w-64 rounded-full bg-accent/10 blur-3xl" />
      </div>

      <div className="relative w-full max-w-md">
        <div className="mb-8 text-center">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-accent shadow-lg shadow-primary/20">
            <Shield className="h-8 w-8 text-white" />
          </div>
          <h1 className="text-2xl font-bold">پنل مدیریت پلتفرم</h1>
          <p className="mt-2 text-sm text-muted">پوشه — کنترل مرکزی SaaS (کاربران، دفاتر، پرداخت، تنظیمات)</p>
          <p className="mt-1 text-xs text-muted/80">panel.posheapp.ir</p>
        </div>

        <Card className="glass border-card-border">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <Smartphone className="h-5 w-5 text-primary" />
              {step === 'mobile' ? 'ورود مدیر' : 'کد تأیید'}
            </CardTitle>
          </CardHeader>
          <CardContent>
            {step === 'mobile' ? (
              <form onSubmit={handleSendOtp} className="space-y-4">
                <Input
                  type="tel"
                  placeholder="09121234567"
                  value={mobile}
                  onChange={(e) => setMobile(toEnglishDigits(e.target.value).replace(/\D/g, '').slice(0, 11))}
                  dir="ltr"
                  className="text-center text-lg tracking-widest"
                  maxLength={11}
                />
                {error && <p className="text-sm text-danger">{error}</p>}
                <Button type="submit" className="w-full" disabled={loading || mobile.length < 11}>
                  {loading ? 'در حال ارسال…' : 'دریافت کد'}
                </Button>
              </form>
            ) : (
              <form onSubmit={handleVerifyOtp} className="space-y-4">
                <p className="text-sm text-muted text-center">کد به {toPersianDigits(mobile)} ارسال شد</p>
                {devHint && <p className="text-sm text-warning text-center">{devHint}</p>}
                <Input
                  key={otpInputKey}
                  type="text"
                  inputMode="numeric"
                  autoComplete="one-time-code"
                  placeholder="------"
                  value={otp}
                  onChange={(e) => setOtp(toEnglishDigits(e.target.value).replace(/\D/g, '').slice(0, 6))}
                  dir="ltr"
                  className="text-center text-2xl tracking-widest font-mono"
                  maxLength={6}
                  autoFocus
                />
                {error && <p className="text-sm text-danger">{error}</p>}
                <Button type="submit" className="w-full" disabled={loading || otp.length < 6}>
                  {loading ? 'بررسی…' : 'ورود به پنل'}
                </Button>
                <div className="flex flex-col items-center gap-2 text-sm">
                  <button type="button" onClick={() => { setStep('mobile'); setOtp(''); setError('') }} className="text-muted hover:text-foreground">
                    تغییر شماره
                  </button>
                  {countdown > 0 ? (
                    <span className="text-muted">ارسال مجدد ({toPersianDigits(String(countdown))})</span>
                  ) : (
                    <button type="button" onClick={handleSendOtp} className="text-primary hover:underline">
                      ارسال مجدد
                    </button>
                  )}
                </div>
              </form>
            )}
          </CardContent>
        </Card>

        <p className="mt-6 text-center text-xs text-muted">
          این پنل فقط برای مدیریت کل پلتفرم است — مشاوران از posheapp.ir وارد می‌شوند.
        </p>
      </div>
    </div>
  )
}
