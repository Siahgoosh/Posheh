import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { FileText, Plus } from 'lucide-react'
import { useState } from 'react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

export function ContractsPage() {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ template_id: '', party_a_name: '', party_b_name: '', property_id: '' })

  const { data: templates } = useQuery({
    queryKey: ['contract-templates'],
    queryFn: async () => (await api.get('/contracts/templates')).data.data as { id: number; name: string }[],
  })

  const { data: contracts } = useQuery({
    queryKey: ['contracts'],
    queryFn: async () => {
      const res = await api.get('/contracts')
      return (res.data.data ?? []) as { id: number; title: string; status: string; pdf_path?: string }[]
    },
  })

  const createMutation = useMutation({
    mutationFn: () => api.post('/contracts', {
      template_id: form.template_id ? parseInt(form.template_id) : null,
      party_a_name: form.party_a_name,
      party_b_name: form.party_b_name,
      property_id: form.property_id ? parseInt(form.property_id) : null,
    }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['contracts'] }),
  })

  return (
    <div className="space-y-6 max-w-2xl mx-auto animate-fade-in">
      <h1 className="text-2xl font-bold flex items-center gap-2"><FileText className="h-6 w-6 text-primary" /> قراردادها</h1>

      <Card>
        <CardHeader><CardTitle className="flex items-center gap-2"><Plus className="h-4 w-4" /> صدور قرارداد</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <select className="w-full rounded-xl border border-card-border bg-background/50 p-2 text-sm" value={form.template_id} onChange={(e) => setForm((f) => ({ ...f, template_id: e.target.value }))}>
            <option value="">انتخاب قالب</option>
            {templates?.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
          </select>
          <Input placeholder="طرف اول" value={form.party_a_name} onChange={(e) => setForm((f) => ({ ...f, party_a_name: e.target.value }))} />
          <Input placeholder="طرف دوم" value={form.party_b_name} onChange={(e) => setForm((f) => ({ ...f, party_b_name: e.target.value }))} />
          <Input placeholder="شناسه ملک (اختیاری)" value={form.property_id} onChange={(e) => setForm((f) => ({ ...f, property_id: e.target.value }))} dir="ltr" />
          <Button onClick={() => createMutation.mutate()} disabled={createMutation.isPending}>تولید PDF</Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>قراردادهای صادرشده</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {contracts?.map((c) => (
            <div key={c.id} className="flex justify-between items-center text-sm border-b border-card-border pb-2">
              <span>{c.title}</span>
              {c.pdf_path && <a href={`/storage/${c.pdf_path}`} target="_blank" rel="noreferrer" className="text-primary text-xs">دانلود PDF</a>}
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
