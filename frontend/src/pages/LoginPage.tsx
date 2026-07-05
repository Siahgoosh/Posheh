import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import { Building2, Smartphone } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import api from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import { toPersianDigits } from '@/lib/utils'

export function LoginPage() {
  const [step, setStep] = useState<'mobile' | 'otp'>('mobile')
  const [mobile, setMobile] = useState('')
  const [otp, setOtp] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [countdown, setCountdown] = useState(0)
  const { setAuth } = useAuthStore()
  const navigate = useNavigate()

  const handleSendOtp = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await api.post('/auth/otp/send', { mobile })
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
      const message = axiosErr.response?.data?.message
      setError(
        axiosErr.response?.data?.errors?.mobile?.[0]
          || (message && message !== 'Server Error' ? message : 'خطا در اتصال به سرور. لطفاً دوباره تلاش کنید.')
      )
    } finally {
      setLoading(false)
    }
  }

  const handleVerifyOtp = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const { data } = await api.post('/auth/otp/verify', { mobile, code: otp })
      setAuth(data.user, data.token)
      navigate('/dashboard')
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      setError(axiosErr.response?.data?.errors?.code?.[0] || axiosErr.response?.data?.errors?.mobile?.[0] || 'کد نامعتبر است')
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
                    onChange={(e) => setMobile(e.target.value)}
                    dir="ltr"
                    className="text-center text-lg tracking-widest"
                    maxLength={11}
                  />
                </div>
                {error && <p className="text-sm text-danger">{error}</p>}
                <Button type="submit" className="w-full" disabled={loading || mobile.length < 11}>
                  {loading ? 'در حال ارسال...' : 'دریافت کد تأیید'}
                </Button>
              </form>
            ) : (
              <form onSubmit={handleVerifyOtp} className="space-y-4">
                <p className="text-sm text-muted">
                  کد تأیید به {toPersianDigits(mobile)} ارسال شد
                </p>
                <Input
                  type="text"
                  placeholder="کد ۶ رقمی"
                  value={otp}
                  onChange={(e) => setOtp(e.target.value.replace(/\D/g, '').slice(0, 6))}
                  dir="ltr"
                  className="text-center text-2xl tracking-[0.5em]"
                  maxLength={6}
                  autoFocus
                />
                {error && <p className="text-sm text-danger">{error}</p>}
                <Button type="submit" className="w-full" disabled={loading || otp.length < 6}>
                  {loading ? 'در حال بررسی...' : 'ورود'}
                </Button>
                <div className="flex justify-between text-sm">
                  <button type="button" onClick={() => setStep('mobile')} className="text-muted hover:text-foreground">
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
