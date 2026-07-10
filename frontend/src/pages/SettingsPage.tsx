import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { LogOut, Moon, Sun, Smartphone, User, Key, Bot } from 'lucide-react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '@/lib/api'
import { useAuthStore, useThemeStore } from '@/stores/auth'
import { toPersianDigits } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface Device {
  id: number
  device_name: string
  platform: string
  last_active_at: string
}

export function SettingsPage() {
  const { user, logout, logoutAll } = useAuthStore()
  const { theme, toggleTheme } = useThemeStore()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const canManage = user?.role === 'office_manager' || user?.role === 'super_admin'

  const [officeForm, setOfficeForm] = useState({
    telegram_bot_token: '',
    whatsapp_phone: '',
    whatsapp_auto_reply: '',
    brand_color: '#6366f1',
    brand_name: '',
    show_on_website: false,
  })
  const [newKeyName, setNewKeyName] = useState('')
  const [plainKey, setPlainKey] = useState('')

  const { data: apiKeys } = useQuery({
    queryKey: ['api-keys'],
    queryFn: async () => (await api.get('/api-keys')).data.data as { id: number; name: string; key_prefix: string }[],
    enabled: canManage,
  })

  const saveOfficeMutation = useMutation({
    mutationFn: () => api.put('/office/settings', officeForm),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['auth'] }),
  })

  const createKeyMutation = useMutation({
    mutationFn: () => api.post('/api-keys', { name: newKeyName }),
    onSuccess: (res) => {
      setPlainKey(res.data.plain_key)
      setNewKeyName('')
      queryClient.invalidateQueries({ queryKey: ['api-keys'] })
    },
  })

  const { data: devices } = useQuery({
    queryKey: ['devices'],
    queryFn: async () => {
      const res = await api.get('/auth/devices')
      return res.data.data as Device[]
    },
  })

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const handleLogoutAll = async () => {
    await logoutAll()
    navigate('/login')
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-2xl">
      <div>
        <h1 className="text-2xl font-bold">تنظیمات</h1>
        <p className="text-muted mt-1">حساب کاربری و ترجیحات</p>
      </div>

      <Card className="overflow-hidden">
        <div className="bg-gradient-to-br from-primary to-accent p-6">
          <div className="flex items-center gap-4">
            <div className="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 text-2xl font-bold text-white">
              {user?.name?.charAt(0)}
            </div>
            <div className="text-white">
              <p className="text-lg font-bold">{user?.name}</p>
              <p className="text-white/80 text-sm">{user?.office?.name}</p>
              <p className="text-white/60 text-xs mt-1" dir="ltr">{user?.mobile && toPersianDigits(user.mobile)}</p>
            </div>
          </div>
        </div>
        <CardContent className="p-4">
          <p className="text-sm text-muted">نقش: {user?.role_label}</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle className="text-base flex items-center gap-2"><User className="h-4 w-4" /> ظاهر</CardTitle></CardHeader>
        <CardContent>
          <Button variant="outline" onClick={toggleTheme} className="w-full justify-start gap-2">
            {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
            {theme === 'dark' ? 'حالت روشن' : 'حالت تاریک'}
          </Button>
        </CardContent>
      </Card>

      {canManage && (
        <>
          <Card>
            <CardHeader><CardTitle className="text-base flex items-center gap-2"><Bot className="h-4 w-4" /> ربات‌ها و برند</CardTitle></CardHeader>
            <CardContent className="space-y-3">
              <Input placeholder="توکن ربات تلگرام" value={officeForm.telegram_bot_token} onChange={(e) => setOfficeForm((f) => ({ ...f, telegram_bot_token: e.target.value }))} dir="ltr" />
              <p className="text-xs text-muted">Webhook تلگرام: /api/v1/bots/telegram/{user?.office?.slug}</p>
              <Input placeholder="شماره واتساپ" value={officeForm.whatsapp_phone} onChange={(e) => setOfficeForm((f) => ({ ...f, whatsapp_phone: e.target.value }))} dir="ltr" />
              <Input placeholder="نام برند" value={officeForm.brand_name} onChange={(e) => setOfficeForm((f) => ({ ...f, brand_name: e.target.value }))} />
              <Input placeholder="رنگ برند" value={officeForm.brand_color} onChange={(e) => setOfficeForm((f) => ({ ...f, brand_color: e.target.value }))} dir="ltr" />
              <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={officeForm.show_on_website} onChange={(e) => setOfficeForm((f) => ({ ...f, show_on_website: e.target.checked }))} />
                نمایش در وبسایت
              </label>
              <Button onClick={() => saveOfficeMutation.mutate()} disabled={saveOfficeMutation.isPending}>ذخیره</Button>
            </CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle className="text-base flex items-center gap-2"><Key className="h-4 w-4" /> API عمومی</CardTitle></CardHeader>
            <CardContent className="space-y-3">
              <div className="flex gap-2">
                <Input placeholder="نام کلید" value={newKeyName} onChange={(e) => setNewKeyName(e.target.value)} />
                <Button onClick={() => createKeyMutation.mutate()} disabled={!newKeyName}>ایجاد</Button>
              </div>
              {plainKey && <p className="text-xs text-warning break-all">کلید: {plainKey}</p>}
              {apiKeys?.map((k) => <div key={k.id} className="text-sm text-muted">{k.name} — {k.key_prefix}…</div>)}
            </CardContent>
          </Card>
        </>
      )}

      <Card>
        <CardHeader><CardTitle className="text-base flex items-center gap-2"><Smartphone className="h-4 w-4" /> دستگاه‌های فعال</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {devices?.length ? devices.map((d) => (
            <div key={d.id} className="flex justify-between text-sm p-3 rounded-lg bg-white/5">
              <span>{d.device_name || d.platform}</span>
              <span className="text-muted text-xs">{d.platform}</span>
            </div>
          )) : (
            <p className="text-sm text-muted">دستگاهی ثبت نشده</p>
          )}
        </CardContent>
      </Card>

      <div className="space-y-2">
        <Button variant="outline" className="w-full text-danger border-danger/30" onClick={handleLogout}>
          <LogOut className="h-4 w-4" />
          خروج از این دستگاه
        </Button>
        <Button variant="ghost" className="w-full text-danger" onClick={handleLogoutAll}>
          خروج از همه دستگاه‌ها
        </Button>
      </div>
    </div>
  )
}
