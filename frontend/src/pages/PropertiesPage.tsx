import { useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Plus, Search, Download, Upload, FileSpreadsheet, HelpCircle, CheckCircle2 } from 'lucide-react'
import api from '@/lib/api'
import { extractApiError } from '@/lib/apiError'
import { formatPrice, formatNumber } from '@/lib/utils'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

interface Property {
  id: number
  code: string
  type: string
  type_label: string
  property_category_label?: string
  status_label: string
  price?: number
  rent?: number
  deposit?: number
  area?: number
  rooms?: number
  city?: string
  district?: string
  permission_label: string
  created_at_jalali: string
  cover_image?: { url: string }
}

async function downloadBlob(path: string, filename: string) {
  const res = await api.get(path, { responseType: 'blob' })
  const type = String(res.headers['content-type'] || '')
  if (type.includes('application/json') || type.includes('text/json')) {
    const text = await res.data.text()
    const json = JSON.parse(text)
    throw new Error(json.message || 'دانلود ناموفق بود.')
  }
  const url = URL.createObjectURL(res.data)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}

export function PropertiesPage() {
  const [search, setSearch] = useState('')
  const [type, setType] = useState('')
  const [showImportHelp, setShowImportHelp] = useState(false)
  const [exportMsg, setExportMsg] = useState('')
  const [importMsg, setImportMsg] = useState('')
  const [busy, setBusy] = useState(false)
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['properties', search, type],
    queryFn: async () => {
      const params = new URLSearchParams()
      if (search) params.set('q', search)
      if (type) params.set('type', type)
      const res = await api.get(`/properties?${params}`)
      return res.data
    },
  })

  const propertyTypes = [
    { value: '', label: 'همه' },
    { value: 'sale', label: 'فروش' },
    { value: 'rent', label: 'اجاره' },
    { value: 'mortgage', label: 'رهن' },
    { value: 'pre_sale', label: 'پیش‌فروش' },
    { value: 'land', label: 'زمین' },
    { value: 'commercial', label: 'تجاری' },
  ]

  const onExport = async () => {
    setExportMsg('')
    setBusy(true)
    try {
      await downloadBlob('/properties-export', `املاک-${new Date().toISOString().slice(0, 10)}.xlsx`)
      setExportMsg('فایل اکسل دانلود شد.')
    } catch (err) {
      setExportMsg(extractApiError(err, 'خروجی اکسل گرفته نشد.'))
    } finally {
      setBusy(false)
    }
  }

  const onDownloadSample = async () => {
    setImportMsg('')
    try {
      await downloadBlob('/properties-import-template', 'نمونه-ایمپورت-املاک.xlsx')
      setImportMsg('فایل نمونه دانلود شد.')
    } catch (err) {
      setImportMsg(extractApiError(err, 'دانلود نمونه ناموفق بود.'))
    }
  }

  const onImport = async (file: File) => {
    setImportMsg('')
    const ext = file.name.split('.').pop()?.toLowerCase()
    if (ext !== 'xlsx' && ext !== 'xls') {
      setImportMsg('فقط فایل اکسل (.xlsx یا .xls) قابل ایمپورت است.')
      return
    }
    setBusy(true)
    try {
      const body = new FormData()
      body.append('file', file)
      const res = await api.post('/properties-import', body)
      setImportMsg(res.data.message || 'ایمپورت انجام شد.')
      queryClient.invalidateQueries({ queryKey: ['properties'] })
    } catch (err) {
      setImportMsg(extractApiError(err, 'ایمپورت ناموفق بود.'))
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">املاک</h1>
          <p className="text-muted mt-1">
            {formatNumber(data?.meta?.total ?? 0)} ملک ثبت شده
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Button variant="outline" size="sm" disabled={busy} onClick={onExport}>
            <Download className="h-4 w-4" />اکسل
          </Button>
          <Button variant="outline" size="sm" onClick={() => setShowImportHelp((v) => !v)}>
            <Upload className="h-4 w-4" />ایمپورت
          </Button>
          <Link to="/properties/new">
            <Button>
              <Plus className="h-4 w-4" />
              ثبت ملک
            </Button>
          </Link>
        </div>
      </div>

      {(exportMsg || importMsg) && (
        <p className="text-sm text-primary">{exportMsg || importMsg}</p>
      )}

      {showImportHelp && (
        <Card className="p-5 space-y-4 border-primary/20">
          <div className="flex items-start gap-3">
            <HelpCircle className="h-5 w-5 text-primary shrink-0 mt-0.5" />
            <div>
              <h2 className="font-semibold">آموزش ایمپورت اکسل</h2>
              <p className="text-sm text-muted mt-1">فقط فایل اکسل (.xlsx / .xls) پذیرفته می‌شود. CSV پشتیبانی نمی‌شود.</p>
            </div>
          </div>
          <ol className="text-sm space-y-2 list-decimal list-inside text-muted">
            <li>ابتدا «دانلود فایل نمونه» را بزنید.</li>
            <li>ردیف‌های نمونه را با داده‌های خود جایگزین کنید؛ ردیف اول (عنوان ستون‌ها) را پاک نکنید.</li>
            <li>ستون <b>code</b> الزامی است. اگر کد تکراری باشد، همان ملک به‌روزرسانی می‌شود.</li>
            <li>برای type از مقادیر انگلیسی استفاده کنید: sale, rent, mortgage, pre_sale, land, commercial</li>
            <li>status معمولاً active باشد.</li>
            <li>فایل را ذخیره کرده و از دکمه «انتخاب فایل اکسل» آپلود کنید.</li>
          </ol>
          <div className="rounded-xl bg-background/60 p-3 text-xs font-mono dir-ltr text-left overflow-x-auto">
            code | type | status | price | deposit | rent | area | rooms | city | district | address | owner_name | owner_mobile | description
          </div>
          <div className="flex flex-wrap gap-2">
            <Button variant="outline" size="sm" onClick={onDownloadSample}>
              <FileSpreadsheet className="h-4 w-4" />دانلود فایل نمونه
            </Button>
            <label className="cursor-pointer">
              <Button size="sm" asChild disabled={busy}><span><Upload className="h-4 w-4" />انتخاب فایل اکسل</span></Button>
              <input
                type="file"
                className="hidden"
                accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                onChange={(e) => {
                  const file = e.target.files?.[0]
                  if (file) onImport(file)
                  e.target.value = ''
                }}
              />
            </label>
          </div>
          <p className="text-xs text-muted flex items-center gap-1"><CheckCircle2 className="h-3.5 w-3.5" />بعد از ایمپورت موفق، لیست املاک به‌روز می‌شود.</p>
        </Card>
      )}

      <div className="flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted" />
          <Input
            placeholder="جستجوی سریع..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pr-10"
          />
        </div>
        <div className="flex gap-2 overflow-x-auto pb-1">
          {propertyTypes.map((t) => (
            <Button
              key={t.value}
              variant={type === t.value ? 'default' : 'secondary'}
              size="sm"
              onClick={() => setType(t.value)}
            >
              {t.label}
            </Button>
          ))}
        </div>
      </div>

      {isLoading ? (
        <div className="flex justify-center py-20">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>
      ) : (
        <PropertiesGrid items={(data?.data as Property[]) ?? []} />
      )}
    </div>
  )
}

function PropertiesGrid({ items }: { items: Property[] }) {
  const empty = useMemo(() => items.length === 0, [items])
  if (empty) return <p className="text-center text-muted py-16">ملکی یافت نشد</p>

  return (
    <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      {items.map((p) => (
        <Link key={p.id} to={`/properties/${p.id}`}>
          <Card className="overflow-hidden glass-hover h-full">
            {p.cover_image?.url ? (
              <img src={p.cover_image.url} alt="" className="h-36 w-full object-cover" />
            ) : (
              <div className="h-36 bg-primary/10" />
            )}
            <div className="p-4 space-y-2">
              <div className="flex justify-between gap-2">
                <h3 className="font-semibold truncate">{p.code}</h3>
                <Badge variant="outline">{p.type_label}</Badge>
              </div>
              <p className="text-sm text-muted">{[p.city, p.district].filter(Boolean).join(' — ') || '—'}</p>
              <p className="font-bold text-primary">
                {p.price ? formatPrice(p.price) : p.rent ? `${formatPrice(p.rent)} اجاره` : '—'}
              </p>
              <div className="flex gap-3 text-xs text-muted">
                {p.area != null && <span>{formatNumber(p.area)} متر</span>}
                {p.rooms != null && <span>{formatNumber(p.rooms)} خواب</span>}
                <span>{p.status_label}</span>
              </div>
            </div>
          </Card>
        </Link>
      ))}
    </div>
  )
}
