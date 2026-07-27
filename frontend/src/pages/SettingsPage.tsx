import { useAuthStore } from '@/stores/auth'
import { Settings } from 'lucide-react'
import { Card } from '@/components/ui/card'

export function SettingsPage() {
  const { user } = useAuthStore()

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold flex items-center gap-2"><Settings className="h-6 w-6 text-primary" />تنظیمات</h1>
      <Card className="p-6 space-y-4">
        <div><p className="text-xs text-muted">نام</p><p className="font-medium">{user?.name}</p></div>
        <div><p className="text-xs text-muted">موبایل</p><p className="font-medium">{user?.mobile}</p></div>
        <div><p className="text-xs text-muted">دفتر</p><p className="font-medium">{user?.office?.name}</p></div>
        <div><p className="text-xs text-muted">نقش</p><p className="font-medium">{user?.role_label}</p></div>
      </Card>
    </div>
  )
}
