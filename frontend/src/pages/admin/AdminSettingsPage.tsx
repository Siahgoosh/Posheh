import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { ArrowRight, Save, Settings } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

interface SettingItem {
  key: string
  value: string
  label: string
  type: string
  is_secret: boolean
  has_value: boolean
}

const GROUP_LABELS: Record<string, string> = {
  payment: 'درگاه پرداخت',
  general: 'عمومی و پشتیبانی',
  sms: 'پیامک (IPPanel)',
  marketing: 'مارکتینگ و رشد',
  accounting: 'حسابداری و فاکتور',
  notifications: 'اعلان‌ها',
  crm: 'قیف فروش (CRM)',
}

export function AdminSettingsPage() {
  const queryClient = useQueryClient()
  const [values, setValues] = useState<Record<string, string>>({})

  const { data, isLoading } = useQuery({
    queryKey: ['admin-system-settings'],
    queryFn: async () => {
      const res = await api.get('/admin/system-settings')
      const grouped = res.data.data as Record<string, SettingItem[]>
      const flat: Record<string, string> = {}
      Object.values(grouped).flat().forEach((s) => {
        flat[s.key] = s.value ?? ''
      })
      setValues(flat)
      return grouped
    },
  })

  const save = useMutation({
    mutationFn: async () => {
      await api.put('/admin/system-settings', { settings: values })
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-system-settings'] }),
  })

  const renderField = (item: SettingItem) => {
    const isSecret = item.is_secret || item.type === 'password'
    const isLtr = item.key.includes('merchant') || item.key.includes('pin') || item.key.includes('analytics') || item.key.includes('pixel') || item.key.includes('utm')

    if (item.type === 'textarea') {
      return (
        <div key={item.key} className="space-y-1 md:col-span-2">
          <label className="text-sm text-muted">{item.label}</label>
          <textarea
            className="w-full min-h-[80px] rounded-lg border border-card-border bg-background px-3 py-2 text-sm"
            value={values[item.key] ?? ''}
            onChange={(e) => setValues((v) => ({ ...v, [item.key]: e.target.value }))}
          />
        </div>
      )
    }

    if (item.type === 'boolean' || item.type === 'select') {
      return (
        <div key={item.key} className="space-y-1">
          <label className="text-sm text-muted">{item.label}</label>
          <select
            className="w-full rounded-lg border border-card-border bg-background px-3 py-2 text-sm"
            value={values[item.key] ?? ''}
            onChange={(e) => setValues((v) => ({ ...v, [item.key]: e.target.value }))}
          >
            {item.type === 'boolean' ? (
              <>
                <option value="1">فعال</option>
                <option value="0">غیرفعال</option>
              </>
            ) : (
              <>
                <option value="toman">تومان</option>
                <option value="rial">ریال</option>
                <option value="log">log</option>
                <option value="live">live</option>
                <option value="maxsms">maxsms</option>
                <option value="ippanel">ippanel</option>
              </>
            )}
          </select>
        </div>
      )
    }

    return (
      <div key={item.key} className="space-y-1">
        <label className="text-sm text-muted">{item.label}</label>
        <Input
          value={values[item.key] ?? ''}
          onChange={(e) => setValues((v) => ({ ...v, [item.key]: e.target.value }))}
          dir={isLtr ? 'ltr' : 'rtl'}
          type={isSecret ? 'password' : item.type === 'number' ? 'number' : 'text'}
        />
      </div>
    )
  }

  const groups = data ? Object.entries(data) : []

  return (
    <div className="space-y-6 max-w-5xl mx-auto">
      <div className="flex items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Link to="/admin"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
          <div>
            <h1 className="text-2xl font-bold flex items-center gap-2"><Settings className="h-6 w-6 text-primary" />تنظیمات سیستم</h1>
            <p className="text-sm text-muted">مارکتینگ، حسابداری، اعلان‌ها، CRM و درگاه پرداخت — بلافاصله در وب و اپ اعمال می‌شود</p>
          </div>
        </div>
        <Button onClick={() => save.mutate()} disabled={save.isPending}>
          <Save className="h-4 w-4" />
          ذخیره
        </Button>
      </div>

      {isLoading ? (
        <p className="text-muted">در حال بارگذاری…</p>
      ) : (
        <div className="grid gap-6 lg:grid-cols-2">
          {groups.map(([group, items]) => (
            <Card key={group} className={`glass ${group === 'sms' ? 'lg:col-span-2' : ''}`}>
              <CardHeader><CardTitle>{GROUP_LABELS[group] ?? group}</CardTitle></CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2">
                {items.map((item) => renderField(item))}
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
