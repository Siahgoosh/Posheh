import { AdminPageHeader } from '@/components/admin/AdminPageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import api from '@/lib/api'

const exports = [
  { type: 'users', label: 'کاربران' },
  { type: 'offices', label: 'دفاتر' },
  { type: 'payments', label: 'پرداخت‌ها' },
  { type: 'customers', label: 'مشتریان' },
]

async function downloadExport(type: string) {
  const res = await api.get(`/admin/export/${type}`, { responseType: 'blob' })
  const url = URL.createObjectURL(res.data)
  const a = document.createElement('a')
  a.href = url
  a.download = `posheh-${type}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

export function AdminExportsPage() {
  return (
    <div className="space-y-6 animate-fade-in max-w-lg">
      <AdminPageHeader title="خروجی CSV" description="دانلود داده برای Excel" />
      <Card>
        <CardHeader><CardTitle>Export</CardTitle></CardHeader>
        <CardContent className="grid gap-2">
          {exports.map((e) => (
            <Button key={e.type} variant="outline" onClick={() => downloadExport(e.type)}>
              دانلود {e.label}
            </Button>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
