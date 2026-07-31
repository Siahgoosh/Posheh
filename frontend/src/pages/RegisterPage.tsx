import { useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import { Building2, User, Users, Crown, CheckCircle2, RefreshCw, AlertCircle } from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { getDeviceId, getDeviceName, getPlatform } from '@/lib/device'
import { useAuthStore } from '@/stores/auth'
import { formatPrice, normalizeMobile, toEnglishDigits, toPersianDigits } from '@/lib/utils'
import { FALLBACK_PLANS, PLAN_FEATURE_LABELS, trialBadgeForPlan, type PlanOption } from '@/constants/plans'

const planIcons: Record<string, typeof User> = {
  solo: User,
  office: Users,
  premium: Crown,
}

type Step = 'plan' | 'details'

export function RegisterPage() {
  const navigate = useNavigate()
  const { setAuth } = useAuthStore()
  const [step, setStep] = useState<Step>('plan')
  const [selectedPlan, setSelectedPlan] = useState<PlanOption | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const logoRef = useRef<HTMLInputElement>(null)

  const [form, setForm] = useState({
    email: '',
    username: '',
    password: '',
    password_confirmation: '',
    mobile: '',
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

  const { data: plans, isLoading: plansLoading, isError: plansError, refetch } = useQuery({
    queryKey: ['plans'],
    queryFn: async () => {
      const res = await api.get('/plans')
      const list = res.data.data as PlanOption[]
      if (!list?.length) return FALLBACK_PLANS
      return list
    },
    retry: 2,
    staleTime: 60000,
  })

  const displayPlans = plans?.length ? plans : FALLBACK_PLANS
  const isSolo = selectedPlan?.slug === 'solo'

  const submitRegistration = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!selectedPlan) return
    setError('')
    setLoading(true)
    try {
      const body = new FormData()
      body.append('plan_slug', selectedPlan.slug)
      body.append('email', form.email.trim().toLowerCase())
      body.append('username', form.username.trim().toLowerCase())
      body.append('password', form.password)
      body.append('password_confirmation', form.password_confirmation)
      body.append('mobile', normalizeMobile(form.mobile))
      body.append('office_name', form.office_name)
      body.append('office_address', form.office_address)
      body.append('device_id', getDeviceId())
      body.append('device_name', getDeviceName())
      body.append('platform', getPlatform())

      const optionalFields = [
        'first_name', 'last_name', 'manager_name', 'office_phone',
        'office_city', 'office_description', 'telegram_bot_token', 'whatsapp_phone',
      ] as const
      optionalFields.forEach((k) => {
        if (form[k]) body.append(k, form[k])
      })

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

  return (
    <div className="min-h-screen bg-background p-4 py-10">
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className="absolute top-0 right-1/4 h-96 w-96 rounded-full bg-primary/10 blur-[120px]" />
        <div className="absolute bottom-0 left-1/4 h-80 w-80 rounded-full bg-accent/10 blur-[100px]" />
      </div>

      <div className="max-w-4xl mx-auto space-y-6 relative">
        <div className="text-center">
          <Link to="/" className="text-sm text-muted hover:text-primary">← بازگشت</Link>
          <div className="mx-auto mt-4 mb-2 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-accent shadow-lg shadow-primary/20">
            <Building2 className="h-7 w-7 text-primary-foreground" />
          </div>
          <h1 className="text-3xl font-bold gradient-text">ثبت‌نام در پوشه</h1>
          <p className="text-muted mt-2">پلن مناسب خود را انتخاب کنید — بدون نیاز به پیامک</p>
        </div>

        {step === 'plan' && (
          <>
            {plansLoading && (
              <div className="grid md:grid-cols-3 gap-4">
                {[1, 2, 3].map((i) => (
                  <Card key={i} className="animate-pulse h-64" />
                ))}
              </div>
            )}

            {!plansLoading && (
              <>
                {plansError && (
                  <div className="flex items-center justify-center gap-2 text-sm text-warning">
                    <AlertCircle className="h-4 w-4" />
                    اتصال به سرور برقرار نشد — نمایش تعرفه پیش‌فرض
                    <button type="button" onClick={() => refetch()} className="text-primary hover:underline flex items-center gap-1">
                      <RefreshCw className="h-3 w-3" /> تلاش مجدد
                    </button>
                  </div>
                )}

                <div className="grid md:grid-cols-3 gap-4">
                  {displayPlans.map((plan) => {
                    const Icon = planIcons[plan.panel_type] ?? Building2
                    const isPopular = plan.slug === 'office'
                    return (
                      <Card
                        key={plan.slug}
                        className={`cursor-pointer transition-all hover:border-primary/50 relative ${selectedPlan?.slug === plan.slug ? 'border-primary ring-2 ring-primary/30' : ''}`}
                        onClick={() => setSelectedPlan(plan)}
                      >
                        {isPopular && (
                          <span className="absolute -top-3 left-1/2 -translate-x-1/2 text-xs bg-accent text-accent-foreground px-3 py-0.5 rounded-full font-medium">
                            پرفروش
                          </span>
                        )}
                        <CardHeader>
                          <CardTitle className="flex items-center gap-2 text-lg">
                            <Icon className="h-5 w-5 text-primary" />
                            {plan.name}
                          </CardTitle>
                          <p className="text-2xl font-bold text-primary">{formatPrice(plan.monthly_price)}</p>
                          <p className="text-xs text-muted">
                            ماهانه
                            {trialBadgeForPlan(plan.slug) ? ` · ${trialBadgeForPlan(plan.slug)}` : ''}
                          </p>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm text-muted">
                          <p>{plan.description}</p>
                          <p>تا {toPersianDigits(String(plan.max_users))} کاربر · {toPersianDigits(String(plan.max_properties))} ملک</p>
                          <ul className="space-y-1 pt-2">
                            {plan.features.slice(0, 6).map((f) => (
                              <li key={f} className="flex items-center gap-1">
                                <CheckCircle2 className="h-3 w-3 text-success shrink-0" />
                                {PLAN_FEATURE_LABELS[f] || f}
                              </li>
                            ))}
                          </ul>
                        </CardContent>
                      </Card>
                    )
                  })}
                </div>
                <div className="md:col-span-3 flex justify-center">
                  <Button size="lg" disabled={!selectedPlan} onClick={() => setStep('details')}>
                    ادامه با {selectedPlan?.name ?? 'پلن انتخابی'}
                  </Button>
                </div>
              </>
            )}
          </>
        )}

        {step === 'details' && selectedPlan && (
          <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }}>
            <Card className="max-w-xl mx-auto">
              <CardHeader>
                <CardTitle>اطلاعات حساب و دفتر — {selectedPlan.name}</CardTitle>
              </CardHeader>
              <CardContent>
                <form onSubmit={submitRegistration} className="space-y-4">
                  <div className="grid sm:grid-cols-2 gap-3">
                    <Input
                      type="email"
                      placeholder="ایمیل *"
                      value={form.email}
                      onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                      dir="ltr"
                      required
                    />
                    <Input
                      placeholder="نام کاربری (انگلیسی) *"
                      value={form.username}
                      onChange={(e) => setForm((f) => ({ ...f, username: toEnglishDigits(e.target.value).replace(/[^a-zA-Z0-9._-]/g, '') }))}
                      dir="ltr"
                      required
                    />
                  </div>
                  <div className="grid sm:grid-cols-2 gap-3">
                    <Input
                      type="password"
                      placeholder="رمز عبور (حداقل ۸ کاراکتر) *"
                      value={form.password}
                      onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
                      required
                      minLength={8}
                    />
                    <Input
                      type="password"
                      placeholder="تکرار رمز عبور *"
                      value={form.password_confirmation}
                      onChange={(e) => setForm((f) => ({ ...f, password_confirmation: e.target.value }))}
                      required
                    />
                  </div>
                  <Input
                    type="tel"
                    placeholder="شماره موبایل *"
                    value={form.mobile}
                    onChange={(e) => setForm((f) => ({ ...f, mobile: toEnglishDigits(e.target.value).replace(/\D/g, '').slice(0, 11) }))}
                    dir="ltr"
                    required
                  />

                  {isSolo ? (
                    <div className="grid sm:grid-cols-2 gap-3">
                      <Input placeholder="نام *" value={form.first_name} onChange={(e) => setForm((f) => ({ ...f, first_name: e.target.value }))} required />
                      <Input placeholder="نام خانوادگی *" value={form.last_name} onChange={(e) => setForm((f) => ({ ...f, last_name: e.target.value }))} required />
                    </div>
                  ) : (
                    <Input placeholder="نام مدیر دفتر" value={form.manager_name} onChange={(e) => setForm((f) => ({ ...f, manager_name: e.target.value }))} />
                  )}

                  <Input
                    placeholder="نام دفتر املاک *"
                    value={form.office_name}
                    onChange={(e) => setForm((f) => ({ ...f, office_name: e.target.value }))}
                    required
                  />
                  <Input placeholder="شهر" value={form.office_city} onChange={(e) => setForm((f) => ({ ...f, office_city: e.target.value }))} />
                  <textarea
                    className="w-full min-h-[72px] rounded-xl border border-card-border bg-background/50 p-3 text-sm"
                    placeholder="آدرس دفتر *"
                    value={form.office_address}
                    onChange={(e) => setForm((f) => ({ ...f, office_address: e.target.value }))}
                    required
                  />

                  {!isSolo && (
                    <>
                      <Input placeholder="تلفن دفتر" value={form.office_phone} onChange={(e) => setForm((f) => ({ ...f, office_phone: e.target.value }))} dir="ltr" />
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
                        <Input placeholder="توکن ربات تلگرام (اختیاری)" value={form.telegram_bot_token} onChange={(e) => setForm((f) => ({ ...f, telegram_bot_token: e.target.value }))} dir="ltr" />
                      )}
                      {selectedPlan.slug === 'premium' && (
                        <Input placeholder="شماره واتساپ پاسخگو (اختیاری)" value={form.whatsapp_phone} onChange={(e) => setForm((f) => ({ ...f, whatsapp_phone: e.target.value }))} dir="ltr" />
                      )}
                    </>
                  )}

                  {error && <p className="text-sm text-danger">{error}</p>}
                  <div className="flex gap-2">
                    <Button type="button" variant="ghost" onClick={() => setStep('plan')}>بازگشت</Button>
                    <Button type="submit" className="flex-1" disabled={loading}>
                      {loading ? 'ثبت‌نام…' : 'ایجاد حساب'}
                    </Button>
                  </div>
                  <p className="text-xs text-muted text-center">
                    با ثبت‌نام، <Link to="/terms" className="text-primary hover:underline">قوانین</Link>
                    {' '}و{' '}
                    <Link to="/privacy" className="text-primary hover:underline">حریم خصوصی</Link>
                    {' '}را می‌پذیرید.
                  </p>
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
