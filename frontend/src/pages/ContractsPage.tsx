import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { FileText, Plus, Download, FileType } from 'lucide-react'
import { useMemo, useState } from 'react'
import api from '@/lib/api'
import { formatJalaliDate } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface ContractField {
  key: string
  label: string
  group: string
  auto?: boolean
}

interface ContractRow {
  id: number
  title: string
  status: string
  pdf_path?: string
  docx_path?: string
  created_at?: string
}

export function ContractsPage() {
  const queryClient = useQueryClient()
  const [templateId, setTemplateId] = useState('')
  const [propertyId, setPropertyId] = useState('')
  const [fields, setFields] = useState<Record<string, string>>({})

  const { data: templates } = useQuery({
    queryKey: ['contract-templates'],
    queryFn: async () => (await api.get('/contracts/templates')).data.data as { id: number; name: string; slug: string }[],
  })

  const { data: fieldDefs } = useQuery({
    queryKey: ['contract-fields'],
    queryFn: async () => (await api.get('/contracts/fields')).data.data as ContractField[],
  })

  const { data: contracts } = useQuery({
    queryKey: ['contracts'],
    queryFn: async () => {
      const res = await api.get('/contracts')
      return (res.data.data ?? res.data.data?.data ?? []) as ContractRow[]
    },
  })

  const groupedFields = useMemo(() => {
    const groups: Record<string, ContractField[]> = {}
    fieldDefs?.forEach((f) => {
      if (!groups[f.group]) groups[f.group] = []
      groups[f.group].push(f)
    })
    return groups
  }, [fieldDefs])

  const createMutation = useMutation({
    mutationFn: () => api.post('/contracts', {
      template_id: templateId ? parseInt(templateId) : null,
      property_id: propertyId ? parseInt(propertyId) : null,
      fields,
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['contracts'] })
      setFields({})
    },
  })

  const selectedTemplate = templates?.find((t) => String(t.id) === templateId)

  return (
    <div className="space-y-6 max-w-4xl mx-auto animate-fade-in">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2"><FileText className="h-6 w-6 text-primary" /> قراردادها</h1>
        <p className="text-sm text-muted mt-1">مبایعه‌نامه فرم ۱۲۵ — نام دفتر خودکار پر می‌شود — خروجی Word و PDF</p>
      </div>

      <Card>
        <CardHeader><CardTitle className="flex items-center gap-2 text-base"><Plus className="h-4 w-4" /> صدور قرارداد جدید</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="grid sm:grid-cols-2 gap-3">
            <select className="w-full rounded-xl border border-card-border bg-background/50 p-2.5 text-sm" value={templateId} onChange={(e) => setTemplateId(e.target.value)}>
              <option value="">انتخاب قالب</option>
              {templates?.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
            </select>
            <Input placeholder="شناسه ملک (اختیاری — پر کردن خودکار)" value={propertyId} onChange={(e) => setPropertyId(e.target.value)} dir="ltr" />
          </div>

          {selectedTemplate?.slug === 'mubayaeh-125' && (
            <div className="space-y-4 max-h-[50vh] overflow-y-auto pr-1">
              {Object.entries(groupedFields).map(([group, items]) => (
                <div key={group}>
                  <p className="text-sm font-semibold text-primary mb-2">{group}</p>
                  <div className="grid sm:grid-cols-2 gap-2">
                    {items.filter((f) => !f.auto).map((f) => (
                      <Input
                        key={f.key}
                        placeholder={f.label}
                        value={fields[f.key] ?? ''}
                        onChange={(e) => setFields((prev) => ({ ...prev, [f.key]: e.target.value }))}
                        className="text-sm"
                      />
                    ))}
                  </div>
                </div>
              ))}
              <p className="text-xs text-muted">فیلدهای «نام دفتر»، «کد ملک» و «تاریخ» خودکار از سیستم پر می‌شوند.</p>
            </div>
          )}

          {!selectedTemplate && (
            <div className="grid sm:grid-cols-2 gap-2">
              <Input placeholder="نام فروشنده" value={fields.seller_name ?? ''} onChange={(e) => setFields((f) => ({ ...f, seller_name: e.target.value }))} />
              <Input placeholder="نام خریدار" value={fields.buyer_name ?? ''} onChange={(e) => setFields((f) => ({ ...f, buyer_name: e.target.value }))} />
            </div>
          )}

          <Button onClick={() => createMutation.mutate()} disabled={createMutation.isPending || !templateId}>
            {createMutation.isPending ? 'در حال تولید…' : 'تولید Word + PDF'}
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>قراردادهای صادرشده</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {!contracts?.length && <p className="text-sm text-muted">هنوز قراردادی صادر نشده.</p>}
          {contracts?.map((c) => (
            <div key={c.id} className="flex flex-wrap justify-between items-center gap-2 text-sm border-b border-card-border pb-3">
              <div>
                <p className="font-medium">{c.title}</p>
                {c.created_at && <p className="text-xs text-muted">{formatJalaliDate(c.created_at)}</p>}
              </div>
              <div className="flex gap-2">
                {c.pdf_path && (
                  <a href={`/storage/${c.pdf_path}`} target="_blank" rel="noreferrer">
                    <Button variant="outline" size="sm"><Download className="h-3 w-3 ml-1" /> PDF</Button>
                  </a>
                )}
                {c.docx_path && (
                  <a href={`/storage/${c.docx_path}`} target="_blank" rel="noreferrer">
                    <Button variant="outline" size="sm"><FileType className="h-3 w-3 ml-1" /> Word</Button>
                  </a>
                )}
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
