import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import { Building2, Smartphone } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import api from '@/lib/api'
import { getDeviceId, getDeviceName, getPlatform } from '@/lib/device'
import { useAuthStore } from '@/stores/auth'
import { toEnglishDigits, toPersianDigits, normalizeMobile } from '@/lib/utils'
import { isPanelSubdomain, isPlatformStaffRole } from '@/lib/subdomain'

export function LoginPage({ panelMode = false }: { panelMode?: boolean }) {
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
    setOtpInputKey((key) => key + 1)
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
      const axiosErr = err as { code?: string; response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } }
      if (axiosErr.code === 'ECONNABORTED' || axiosErr.response?.status === 504) {
        setError('سرور پاسخ نداد. اگر کد به موبایل رسید، ادامه دهید؛ وگرنه چند ثانیه بعد دوباره تلاش کنید.')
        setStep('otp')
        setCountdown(120)
      } else {
        setError(axiosErr.response?.data?.errors?.mobile?.[0] || axiosErr.response?.data?.message || 'خطا در ارسال کد')
      }
    } finally {
      setLoading(false)
    }
  }

  const handleVerifyOtp = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const payload = {
        mobile: normalizeMobile(mobile),
        code: toEnglishDigits(otp).replace(/\D/g, '').slice(0, 6).padStart(6, '0'),
        device_id: getDeviceId(),
        device_name: getDeviceName(),
        platform: getPlatform(),
      }
      const { data } = await api.post('/auth/otp/verify', payload)
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
        response?: {
          status?: number
          data?: { message?: string; errors?: Record<string, string[]> }
        }
      }
      const apiMessage = axiosErr.response?.data?.message
      const fieldError =
        axiosErr.response?.data?.errors?.code?.[0] ||
        axiosErr.response?.data?.errors?.mobile?.[0]

      if (axiosErr.response?.status === 419) {
        setError('خطای امنیتی (CSRF). صفحه را رفرش کنید و دوباره تلاش کنید.')
      } else if (fieldError) {
        setError(fieldError)
      } else if (apiMessage) {
        setError(apiMessage)
      } else if (axiosErr.response?.status === 429) {
        setError('تعداد درخواست‌ها زیاد است. چند دقیقه صبر کنید.')
      } else if (err instanceof Error && err.message) {
        setError(`خطا: ${err.message}`)
      } else {
        setError('خطا در تأیید کد. دوباره تلاش کنید.')
      }
      setOtp('')
      setOtpInputKey((key) => key + 1)
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
              <Smartphone className="h-5 w-5 text-primary" />
              {step === 'mobile' ? 'ورود با موبایل' : 'تأیید کد'}
            </CardTitle>
          </CardHeader>
          <CardContent>
            {step === 'mobile' ? (
              <form onSubmit={handleSendOtp} className="space-y-4">
                <div>
                  <label className="mb-2 block text-sm text-muted">شماره موبایل</label>
                  <Input
                    type="tel"
                    placeholder="۰۹۱۲۱۲۳۴۵۶۷"
                    value={mobile}
                    onChange={(e) => setMobile(toEnglishDigits(e.target.value).replace(/\D/g, '').slice(0, 11))}
                    dir="ltr"
                    className="text-center text-lg tracking-widest"
                    maxLength={11}
                  />
                </div>
                {error && <p className="text-sm text-danger">{error}</p>}
                <Button type="submit" className="w-full" disabled={loading || mobile.length < 11}>
                  {loading ? 'در حال ارسال...' : 'دریافت کد تأیید'}
                </Button>
                <p className="text-center text-sm text-muted">
                  حساب ندارید؟ <Link to="/register" className="text-primary hover:underline">ثبت‌نام</Link>
                </p>
              </form>
            ) : (
              <form onSubmit={handleVerifyOtp} className="space-y-4">
                <p className="text-sm text-muted">
                  کد تأیید به {toPersianDigits(mobile)} ارسال شد
                </p>
                {devHint && <p className="text-sm text-warning text-center">{devHint}</p>}
                <Input
                  key={`otp-input-${otpInputKey}`}
                  type="text"
                  inputMode="numeric"
                  autoComplete="one-time-code"
                  placeholder="کد ۶ رقمی"
                  value={otp}
                  onChange={(e) => setOtp(toEnglishDigits(e.target.value).replace(/\D/g, '').slice(0, 6))}
                  dir="ltr"
                  className="text-center text-2xl tracking-widest font-mono"
                  maxLength={6}
                  autoFocus
                />
                {error && <p className="text-sm text-danger">{error}</p>}
                <Button type="submit" className="w-full" disabled={loading || otp.length < 6}>
                  {loading ? 'در حال بررسی...' : 'ورود'}
                </Button>
                <div className="flex justify-between text-sm">
                  <button
                    type="button"
                    onClick={() => {
                      setStep('mobile')
                      setOtp('')
                      setOtpInputKey((key) => key + 1)
                      setError('')
                    }}
                    className="text-muted hover:text-foreground"
                  >
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
      </motion.div>
    </div>
  )
}
