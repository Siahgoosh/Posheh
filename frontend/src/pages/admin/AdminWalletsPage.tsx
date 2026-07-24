import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { AdminPageHeader } from '@/components/admin/AdminPageHeader'

export function AdminWalletsPage() {
  const [officeId, setOfficeId] = useState('')
  const [amount, setAmount] = useState('')
  const [desc, setDesc] = useState('')
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-wallets'],
    queryFn: async () => {
      const res = await api.get('/admin/wallets')
      return res.data.data as { id: number; balance: number; office?: { id: number; name: string } }[]
    },
  })

  const adjustMutation = useMutation({
    mutationFn: () => api.post(`/admin/offices/${officeId}/wallet/adjust`, {
      amount: parseInt(amount, 10),
      type: 'credit',
      description: desc || 'شارژ دستی توسط مدیر',
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-wallets'] })
      setAmount('')
      setDesc('')
    },
  })

  return (
    <div className="space-y-6 animate-fade-in">
      <AdminPageHeader title="کیف پول دفاتر" />

      <Card>
        <CardHeader><CardTitle>شارژ دستی</CardTitle></CardHeader>
        <CardContent className="flex flex-wrap gap-2">
          <Input placeholder="شناسه دفتر" value={officeId} onChange={(e) => setOfficeId(e.target.value)} className="w-32" />
          <Input placeholder="مبلغ (تومان)" value={amount} onChange={(e) => setAmount(e.target.value)} className="w-40" />
          <Input placeholder="توضیح" value={desc} onChange={(e) => setDesc(e.target.value)} className="flex-1 min-w-[200px]" />
          <Button onClick={() => adjustMutation.mutate()} disabled={!officeId || !amount}>شارژ</Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>موجودی دفاتر</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {isLoading ? <p className="text-muted text-sm">بارگذاری…</p> : data?.map((w) => (
            <div key={w.id} className="flex justify-between text-sm border-b border-card-border pb-2">
              <span>{w.office?.name}</span>
              <span className="font-medium">{formatPrice(w.balance)}</span>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
