import { useMutation, useQuery } from '@tanstack/react-query'
import { Wallet, Plus, ArrowUpRight } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import api from '@/lib/api'
import { formatPrice } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

const PRESET_AMOUNTS = [500_000, 1_000_000, 2_000_000, 5_000_000]

export function WalletCard({ compact = false }: { compact?: boolean }) {
  const [amount, setAmount] = useState('1000000')
  const [message, setMessage] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['wallet'],
    queryFn: async () => (await api.get('/wallet')).data.data as {
      balance: number
      transactions: { id: number; type: string; amount: number; description: string; created_at: string }[]
    },
  })

  const chargeMutation = useMutation({
    mutationFn: async (chargeAmount: number) => {
      const res = await api.post('/wallet/charge', { amount: chargeAmount })
      return res.data.data as { redirect_url: string }
    },
    onSuccess: (result) => {
      if (result.redirect_url) window.location.href = result.redirect_url
    },
    onError: () => setMessage('خطا در اتصال به درگاه'),
  })

  if (isLoading) return null

  return (
    <Card className={compact ? '' : 'border-primary/20'}>
      <CardHeader className="pb-2">
        <CardTitle className="text-base flex items-center gap-2">
          <Wallet className="h-5 w-5 text-primary" />
          کیف پول دفتر
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex items-end justify-between gap-3">
          <div>
            <p className="text-xs text-muted">موجودی</p>
            <p className="text-2xl font-bold text-primary">{formatPrice(data?.balance ?? 0)}</p>
          </div>
          <Link to="/subscription">
            <Button variant="outline" size="sm">
              <ArrowUpRight className="h-4 w-4" />
              خرید اشتراک
            </Button>
          </Link>
        </div>

        {!compact && (
          <>
            <div className="flex flex-wrap gap-2">
              {PRESET_AMOUNTS.map((a) => (
                <button
                  key={a}
                  type="button"
                  onClick={() => setAmount(String(a))}
                  className={`rounded-lg border px-3 py-1.5 text-xs ${Number(amount) === a ? 'border-primary bg-primary/10 text-primary' : 'border-card-border'}`}
                >
                  {formatPrice(a)}
                </button>
              ))}
            </div>
            <div className="flex gap-2">
              <Input
                placeholder="مبلغ شارژ (تومان)"
                value={amount}
                onChange={(e) => setAmount(e.target.value.replace(/\D/g, ''))}
                dir="ltr"
              />
              <Button
                onClick={() => chargeMutation.mutate(Number(amount))}
                disabled={chargeMutation.isPending || Number(amount) < 100000}
              >
                <Plus className="h-4 w-4" />
                شارژ
              </Button>
            </div>
            {message && <p className="text-sm text-danger">{message}</p>}
          </>
        )}

        {data?.transactions?.length ? (
          <div className="space-y-1 max-h-32 overflow-y-auto text-xs">
            {data.transactions.slice(0, 5).map((t) => (
              <div key={t.id} className="flex justify-between text-muted border-b border-card-border/50 py-1">
                <span className="truncate">{t.description}</span>
                <span className={t.type === 'credit' ? 'text-success' : 'text-danger'} dir="ltr">
                  {t.type === 'credit' ? '+' : '-'}{formatPrice(t.amount)}
                </span>
              </div>
            ))}
          </div>
        ) : null}
      </CardContent>
    </Card>
  )
}
