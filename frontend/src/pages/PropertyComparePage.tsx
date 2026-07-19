import { useMutation } from '@tanstack/react-query'
import { GitCompare } from 'lucide-react'
import { useState } from 'react'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { usePlanFeature } from '@/components/SubscriptionGuard'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

export function PropertyComparePage() {
  const hasFeature = usePlanFeature('property_compare')
  const [idsText, setIdsText] = useState('')
  const [result, setResult] = useState<{ properties: Array<Record<string, unknown>>; fields: string[] } | null>(null)

  const compare = useMutation({
    mutationFn: async () => {
      const ids = idsText.split(/[,\s]+/).map((x) => Number(x.trim())).filter((n) => n > 0)
      const res = await api.post('/properties/compare', { ids })
      return res.data.data
    },
    onSuccess: (data) => setResult(data),
  })

  if (!hasFeature) {
    return <p className="text-center text-muted py-20">مقایسه ملک در پلن شما فعال نیست.</p>
  }

  const labelMap: Record<string, string> = {
    code: 'کد', type: 'نوع', deal_type: 'معامله', status: 'وضعیت', price: 'قیمت',
    area: 'متراژ', rooms: 'اتاق', floor: 'طبقه', city: 'شهر', district: 'محله',
    address: 'آدرس', year_built: 'سال ساخت', parking: 'پارکینگ', elevator: 'آسانسور', warehouse: 'انباری',
  }

  return (
    <div className="space-y-6 animate-fade-in">
      <h1 className="text-2xl font-bold flex items-center gap-2"><GitCompare className="h-6 w-6 text-primary" />مقایسه ملک</h1>
      <Card>
        <CardHeader><CardTitle>شناسه املاک (۲ تا ۴ ملک)</CardTitle></CardHeader>
        <CardContent className="flex flex-wrap gap-3">
          <Input className="max-w-md" dir="ltr" placeholder="12, 45, 78" value={idsText} onChange={(e) => setIdsText(e.target.value)} />
          <Button onClick={() => compare.mutate()} disabled={compare.isPending}>مقایسه</Button>
        </CardContent>
      </Card>
      {result && (
        <div className="overflow-x-auto">
          <table className="w-full text-sm border border-card-border rounded-xl overflow-hidden">
            <thead className="bg-white/5">
              <tr>
                <th className="p-3 text-right">فیلد</th>
                {result.properties.map((p) => <th key={String(p.id)} className="p-3 text-right">{String(p.code)}</th>)}
              </tr>
            </thead>
            <tbody>
              {result.fields.map((field) => (
                <tr key={field} className="border-t border-card-border">
                  <td className="p-3 font-medium">{labelMap[field] || field}</td>
                  {result.properties.map((p) => (
                    <td key={`${p.id}-${field}`} className="p-3">
                      {field === 'price' ? formatPrice(Number(p[field] || 0)) : String(p[field] ?? '—')}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
