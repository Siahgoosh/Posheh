import { useState } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { Building2, Mail, Smartphone } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import api from '@/lib/api'

export function ForgotPasswordPage() {
  const [channel, setChannel] = useState<'email' | 'sms'>('email')
  const [login, setLogin] = useState('')
  const [loading, setLoading] = useState(false)
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const [debugUrl, setDebugUrl] = useState('')

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setMessage('')
    setDebugUrl('')
    setLoading(true)
    try {
      const { data } = await api.post('/auth/password/forgot', { channel, login: login.trim() })
      setMessage(data.message)
      if (data.debug_reset_url) setDebugUrl(data.debug_reset_url)
      if (channel === 'sms') {
        window.location.href = `/reset-password?channel=sms&mobile=${encodeURIComponent(login.trim())}`
      }
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      setError(
        axiosErr.response?.data?.errors?.login?.[0]
          || axiosErr.response?.data?.message
          || 'خطا در ارسال درخواست.'
      )
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center p-4">
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="w-full max-w-md">
        <div className="mb-8 text-center">
          <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-accent">
            <Building2 className="h-7 w-7 text-white" />
          </div>
          <h1 className="text-2xl font-bold">بازیابی رمز عبور</h1>
          <p className="text-muted text-sm mt-2">از طریق ایمیل یا پیامک</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle className="text-base">روش بازیابی</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex gap-2 mb-4">
              <Button
                type="button"
                variant={channel === 'email' ? 'default' : 'outline'}
                className="flex-1"
                onClick={() => setChannel('email')}
              >
                <Mail className="h-4 w-4" /> ایمیل
              </Button>
              <Button
                type="button"
                variant={channel === 'sms' ? 'default' : 'outline'}
                className="flex-1"
                onClick={() => setChannel('sms')}
              >
                <Smartphone className="h-4 w-4" /> پیامک
              </Button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="mb-2 block text-sm text-muted">
                  {channel === 'email' ? 'ایمیل یا نام کاربری' : 'شماره موبایل (۰۹...)'}
                </label>
                <Input
                  type={channel === 'email' ? 'text' : 'tel'}
                  placeholder={channel === 'email' ? 'email@example.com' : '09121234567'}
                  value={login}
                  onChange={(e) => setLogin(e.target.value)}
                  dir="ltr"
                  required
                />
              </div>
              {error && <p className="text-sm text-danger">{error}</p>}
              {message && <p className="text-sm text-success">{message}</p>}
              {debugUrl && (
                <p className="text-xs break-all text-warning" dir="ltr">
                  لینک تست: <a href={debugUrl}>{debugUrl}</a>
                </p>
              )}
              <Button type="submit" className="w-full" disabled={loading || !login}>
                {loading ? 'در حال ارسال...' : channel === 'email' ? 'ارسال لینک بازیابی' : 'ارسال کد پیامکی'}
              </Button>
            </form>

            <p className="text-center text-sm text-muted mt-4">
              <Link to="/login" className="text-primary hover:underline">بازگشت به ورود</Link>
            </p>
          </CardContent>
        </Card>
      </motion.div>
    </div>
  )
}
