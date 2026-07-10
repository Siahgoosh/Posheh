import { useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import { Building2, User, Users, Crown, CheckCircle2, Smartphone } from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { getDeviceId, getDeviceName, getPlatform } from '@/lib/device'
import { useAuthStore } from '@/stores/auth'
import { formatPrice, normalizeMobile, toEnglishDigits, toPersianDigits } from '@/lib/utils'

interface Plan {
  id: number
  slug: string
  name: string
  description?: string
  monthly_price: number
  trial_days: number
  max_users: number
  max_properties: number
  features: string[]
  panel_type: string
}

const planIcons: Record<string, typeof User> = {
  solo: User,
  office: Users,
  premium: Crown,
}

const featureLabels: Record<string, string> = {
  filing: 'فایلینگ حرفه‌ای',
  accounting: 'حسابداری دفتر',
  team: 'مدیریت تیم',
  telegram_bot: 'ربات تلگرام',
  whatsapp_bot: 'ربات واتساپ',
  website_listing: 'نمایش در وبسایت',
  verified_badge: 'تیک وریفای',
  crm: 'CRM و قیف فروش',
}

type Step = 'plan' | 'mobile' | 'otp' | 'details'

export function RegisterPage() {
  const navigate = useNavigate()
  const { setAuth } = useAuthStore()
  const [step, setStep] = useState<Step>('plan')
  const [selectedPlan, setSelectedPlan] = useState<Plan | null>(null)
  const [mobile, setMobile] = useState('')
  const [otp, setOtp] = useState('')
  const [registrationToken, setRegistrationToken] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [countdown, setCountdown] = useState(0)
  const logoRef = useRef<HTMLInputElement>(null)

  const [form, setForm] = useState({
    first_name: '',
    last_name: '',
    manager_name: '',
    office_name: '',
    office_phone: '',
    office_address: '',
    office_city: '',
    office_description: '',
    telegram_bot_token: '',
    whatsapp_phone: '',
  })

  const { data: plans } = useQuery({
    queryKey: ['plans'],
    queryFn: async () => {
      const res = await api.get('/plans')
      return res.data.data as Plan[]
    },
  })

  const sendOtp = async () => {
    setError('')
    setLoading(true)
    try {
      await api.post('/auth/otp/send', { mobile: normalizeMobile(mobile), purpose: 'register' })
      setMobile(normalizeMobile(mobile))
      setStep('otp')
      setCountdown(120)
      const timer = setInterval(() => {
        setCountdown((c) => { if (c <= 1) { clearInterval(timer); return 0 } return c - 1 })
      }, 1000)
    } catch (err: unknown) {
      const e = err as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } }
      setError(e.response?.data?.errors?.mobile?.[0] || e.response?.data?.message || 'خطا در ارسال کد')
    } finally {
      setLoading(false)
    }
  }

  const verifyOtp = async () => {
    setError('')
    setLoading(true)
    try {
      const { data } = await api.post('/auth/otp/verify', {
        mobile: normalizeMobile(mobile),
        code: toEnglishDigits(otp).replace(/\D/g, '').slice(0, 6).padStart(6, '0'),
        purpose: 'register',
      })
      if (data.needs_registration) {
        setRegistrationToken(data.registration_token)
        setStep('details')
      } else if (data.token) {
        setAuth(data.user, data.token)
        navigate(data.subscription_expired ? '/renew' : '/dashboard')
      }
    } catch (err: unknown) {
      const e = err as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } }
      setError(e.response?.data?.errors?.code?.[0] || e.response?.data?.message || 'کد نامعتبر')
    } finally {
      setLoading(false)
    }
  }

  const submitRegistration = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!selectedPlan || !registrationToken) return
    setError('')
    setLoading(true)
    try {
      const body = new FormData()
      body.append('registration_token', registrationToken)
      body.append('plan_slug', selectedPlan.slug)
      body.append('device_id', getDeviceId())
      body.append('device_name', getDeviceName())
      body.append('platform', getPlatform())
      Object.entries(form).forEach(([k, v]) => { if (v) body.append(k, v) })
      const logo = logoRef.current?.files?.[0]
      if (logo) body.append('logo', logo)

      const { data } = await api.post('/auth/register', body, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      setAuth(data.user, data.token)
      navigate('/dashboard')
    } catch (err: unknown) {
      const e = err as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } }
      setError(
        Object.values(e.response?.data?.errors ?? {}).flat().join('، ')
          || e.response?.data?.message
          || 'خطا در ثبت‌نام'
      )
    } finally {
      setLoading(false)
    }
  }

  const isSolo = selectedPlan?.slug === 'solo'

  return (
    <div className="min-h-screen bg-background p-4 py-10">
      <div className="max-w-4xl mx-auto space-y-6">
        <div className="text-center">
          <Link to="/" className="text-sm text-muted hover:text-primary">← بازگشت</Link>
          <h1 className="text-3xl font-bold gradient-text mt-4">ثبت‌نام در پوشه</h1>
          <p className="text-muted mt-2">پلن مناسب خود را انتخاب کنید — {toPersianDigits('3')} روز تست رایگان</p>
        </div>

        {step === 'plan' && (
          <div className="grid md:grid-cols-3 gap-4">
            {plans?.map((plan) => {
              const Icon = planIcons[plan.panel_type] ?? Building2
              return (
                <Card
                  key={plan.id}
                  className={`cursor-pointer transition-all hover:border-primary/50 ${selectedPlan?.id === plan.id ? 'border-primary ring-2 ring-primary/30' : ''}`}
                  onClick={() => setSelectedPlan(plan)}
                >
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                      <Icon className="h-5 w-5 text-primary" />
                      {plan.name}
                    </CardTitle>
                    <p className="text-2xl font-bold text-primary">{formatPrice(plan.monthly_price)}</p>
                    <p className="text-xs text-muted">ماهانه · {toPersianDigits(String(plan.trial_days))} روز رایگان</p>
                  </CardHeader>
                  <CardContent className="space-y-2 text-sm text-muted">
                    <p>{plan.description}</p>
                    <p>تا {toPersianDigits(String(plan.max_users))} کاربر · {toPersianDigits(String(plan.max_properties))} ملک</p>
                    <ul className="space-y-1 pt-2">
                      {plan.features.slice(0, 5).map((f) => (
                        <li key={f} className="flex items-center gap-1">
                          <CheckCircle2 className="h-3 w-3 text-success shrink-0" />
                          {featureLabels[f] || f}
                        </li>
                      ))}
                    </ul>
                  </CardContent>
                </Card>
              )
            })}
            <div className="md:col-span-3 flex justify-center">
              <Button disabled={!selectedPlan} onClick={() => setStep('mobile')}>
                ادامه با {selectedPlan?.name}
              </Button>
            </div>
          </div>
        )}

        {step === 'mobile' && (
          <Card className="max-w-md mx-auto">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Smartphone className="h-5 w-5" />
                شماره موبایل
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <p className="text-sm text-muted">پلن انتخابی: <strong>{selectedPlan?.name}</strong></p>
              <Input
                type="tel"
                placeholder="۰۹۱۲۱۲۳۴۵۶۷"
                value={mobile}
                onChange={(e) => setMobile(toEnglishDigits(e.target.value).replace(/\D/g, '').slice(0, 11))}
                dir="ltr"
                className="text-center text-lg tracking-widest"
              />
              {error && <p className="text-sm text-danger">{error}</p>}
              <div className="flex gap-2">
                <Button variant="ghost" onClick={() => setStep('plan')}>بازگشت</Button>
                <Button className="flex-1" disabled={loading || mobile.length < 11} onClick={sendOtp}>
                  {loading ? 'ارسال…' : 'دریافت کد'}
                </Button>
              </div>
            </CardContent>
          </Card>
        )}

        {step === 'otp' && (
          <Card className="max-w-md mx-auto">
            <CardHeader><CardTitle>تأیید کد</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              <Input
                placeholder="کد ۶ رقمی"
                value={otp}
                onChange={(e) => setOtp(toEnglishDigits(e.target.value).replace(/\D/g, '').slice(0, 6))}
                dir="ltr"
                className="text-center text-2xl tracking-widest font-mono"
                autoFocus
              />
              {error && <p className="text-sm text-danger">{error}</p>}
              <Button className="w-full" disabled={loading || otp.length < 6} onClick={verifyOtp}>
                {loading ? 'بررسی…' : 'تأیید'}
              </Button>
              {countdown > 0 ? (
                <p className="text-center text-sm text-muted">ارسال مجدد ({toPersianDigits(String(countdown))})</p>
              ) : (
                <button type="button" onClick={sendOtp} className="w-full text-sm text-primary">ارسال مجدد</button>
              )}
            </CardContent>
          </Card>
        )}

        {step === 'details' && selectedPlan && (
          <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }}>
            <Card className="max-w-xl mx-auto">
              <CardHeader>
                <CardTitle>{isSolo ? 'اطلاعات شخصی' : 'اطلاعات دفتر املاک'}</CardTitle>
              </CardHeader>
              <CardContent>
                <form onSubmit={submitRegistration} className="space-y-4">
                  {isSolo ? (
                    <>
                      <Input placeholder="نام" value={form.first_name} onChange={(e) => setForm((f) => ({ ...f, first_name: e.target.value }))} required />
                      <Input placeholder="نام خانوادگی" value={form.last_name} onChange={(e) => setForm((f) => ({ ...f, last_name: e.target.value }))} required />
                    </>
                  ) : (
                    <>
                      <Input placeholder="نام دفتر املاک *" value={form.office_name} onChange={(e) => setForm((f) => ({ ...f, office_name: e.target.value }))} required />
                      <Input placeholder="نام مدیر دفتر" value={form.manager_name} onChange={(e) => setForm((f) => ({ ...f, manager_name: e.target.value }))} />
                      <Input placeholder="تلفن دفتر" value={form.office_phone} onChange={(e) => setForm((f) => ({ ...f, office_phone: e.target.value }))} dir="ltr" />
                      <Input placeholder="شهر" value={form.office_city} onChange={(e) => setForm((f) => ({ ...f, office_city: e.target.value }))} />
                      <textarea
                        className="w-full min-h-[72px] rounded-xl border border-card-border bg-background/50 p-3 text-sm"
                        placeholder="آدرس دفتر"
                        value={form.office_address}
                        onChange={(e) => setForm((f) => ({ ...f, office_address: e.target.value }))}
                      />
                      <textarea
                        className="w-full min-h-[72px] rounded-xl border border-card-border bg-background/50 p-3 text-sm"
                        placeholder="توضیحات دفتر (اختیاری)"
                        value={form.office_description}
                        onChange={(e) => setForm((f) => ({ ...f, office_description: e.target.value }))}
                      />
                      <div>
                        <label className="text-sm text-muted block mb-1">لوگو دفتر</label>
                        <input ref={logoRef} type="file" accept="image/*" className="text-sm" />
                      </div>
                      {(selectedPlan.slug === 'office' || selectedPlan.slug === 'premium') && (
                        <Input placeholder="توکن ربات تلگرام (اختیاری — بعداً هم قابل تنظیم)" value={form.telegram_bot_token} onChange={(e) => setForm((f) => ({ ...f, telegram_bot_token: e.target.value }))} dir="ltr" />
                      )}
                      {selectedPlan.slug === 'premium' && (
                        <Input placeholder="شماره واتساپ پاسخگو (اختیاری)" value={form.whatsapp_phone} onChange={(e) => setForm((f) => ({ ...f, whatsapp_phone: e.target.value }))} dir="ltr" />
                      )}
                    </>
                  )}
                  {error && <p className="text-sm text-danger">{error}</p>}
                  <Button type="submit" className="w-full" disabled={loading}>
                    {loading ? 'ثبت‌نام…' : `شروع ${toPersianDigits(String(selectedPlan.trial_days))} روز رایگان`}
                  </Button>
                </form>
              </CardContent>
            </Card>
          </motion.div>
        )}

        {step === 'plan' && (
          <p className="text-center text-sm text-muted">
            حساب دارید؟ <Link to="/login" className="text-primary hover:underline">ورود</Link>
          </p>
        )}
      </div>
    </div>
  )
}
