import { useState } from 'react'
import { Link, useSearchParams, useNavigate } from 'react-router-dom'
import { Building2, KeyRound } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import api from '@/lib/api'

export function ResetPasswordPage() {
  const [params] = useSearchParams()
  const navigate = useNavigate()
  const channel = params.get('channel') === 'sms' ? 'sms' : 'email'
  const email = params.get('email') || ''
  const token = params.get('token') || ''
  const mobile = params.get('mobile') || ''

  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [code, setCode] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const body = channel === 'sms'
        ? { channel: 'sms', mobile, code, password, password_confirmation: passwordConfirmation }
        : { channel: 'email', email, token, password, password_confirmation: passwordConfirmation }

      await api.post('/auth/password/reset', body)
      navigate('/login', { state: { message: 'رمز عبور تنظیم شد. وارد شوید.' } })
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      setError(
        axiosErr.response?.data?.errors?.password?.[0]
          || axiosErr.response?.data?.errors?.code?.[0]
          || axiosErr.response?.data?.errors?.token?.[0]
          || axiosErr.response?.data?.message
          || 'خطا در تنظیم رمز عبور.'
      )
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center p-4">
      <div className="w-full max-w-md">
        <div className="mb-8 text-center">
          <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-accent">
            <Building2 className="h-7 w-7 text-white" />
          </div>
          <h1 className="text-2xl font-bold">تنظیم رمز عبور جدید</h1>
        </div>

        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <KeyRound className="h-5 w-5 text-primary" />
              {channel === 'sms' ? 'بازیابی با پیامک' : 'بازیابی با ایمیل'}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              {channel === 'sms' && (
                <>
                  <Input placeholder="شماره موبایل" value={mobile} disabled dir="ltr" />
                  <Input
                    placeholder="کد ۶ رقمی پیامک"
                    value={code}
                    onChange={(e) => setCode(e.target.value)}
                    maxLength={6}
                    dir="ltr"
                    required
                  />
                </>
              )}
              <Input
                type="password"
                placeholder="رمز عبور جدید (حداقل ۸ کاراکتر)"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                minLength={8}
                required
              />
              <Input
                type="password"
                placeholder="تکرار رمز عبور"
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
                required
              />
              {error && <p className="text-sm text-danger">{error}</p>}
              <Button type="submit" className="w-full" disabled={loading}>
                {loading ? 'در حال ذخیره...' : 'ذخیره رمز عبور'}
              </Button>
            </form>
            <p className="text-center text-sm text-muted mt-4">
              <Link to="/login" className="text-primary hover:underline">بازگشت به ورود</Link>
            </p>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
