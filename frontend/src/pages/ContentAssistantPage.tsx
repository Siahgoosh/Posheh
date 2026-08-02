import { useMutation, useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import {
  Sparkles, Copy, RefreshCw, Sun, Video, PenLine, Calendar, BarChart3,
  MessageCircle, Target, Rocket, Hash, Clock, Film, AlertTriangle, Gift, ChevronDown,
} from 'lucide-react'
import api from '@/lib/api'
import { usePlanFeature } from '@/components/SubscriptionGuard'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Link } from 'react-router-dom'

const ICONS: Record<string, typeof Sparkles> = {
  sun: Sun, video: Video, pen: PenLine, calendar: Calendar, chart: BarChart3,
  message: MessageCircle, target: Target, rocket: Rocket, hash: Hash, clock: Clock,
  film: Film, alert: AlertTriangle, gift: Gift, lightbulb: Sparkles, layers: Video,
  image: Film, file: PenLine,
}

const TONES = [
  { id: 'friendly', label: 'صمیمی' },
  { id: 'formal', label: 'رسمی' },
  { id: 'luxury', label: 'لوکس' },
  { id: 'investment', label: 'سرمایه‌گذاری' },
  { id: 'educational', label: 'آموزشی' },
]

export function ContentAssistantPage() {
  const hasAi = usePlanFeature('content_assistant')
  const [selectedType, setSelectedType] = useState('daily_plan')
  const [tone, setTone] = useState('friendly')
  const [propertyId, setPropertyId] = useState<number | ''>('')
  const [output, setOutput] = useState('')
  const [reason, setReason] = useState('')
  const [copied, setCopied] = useState(false)

  const { data: types } = useQuery({
    queryKey: ['ai-content-types'],
    queryFn: async () => (await api.get('/ai/content/types')).data.data as {
      id: string; label: string; icon: string; group: string
    }[],
    enabled: hasAi,
  })

  const { data: properties } = useQuery({
    queryKey: ['ai-content-properties'],
    queryFn: async () => (await api.get('/ai/content/properties')).data.data as { id: number; label: string }[],
    enabled: hasAi,
  })

  const { data: daily } = useQuery({
    queryKey: ['ai-daily-briefing'],
    queryFn: async () => {
      const res = await api.get('/ai/content/daily')
      return res.data.data as { output: string; meta?: { reason?: string } }
    },
    enabled: hasAi,
  })

  const generate = useMutation({
    mutationFn: async (regenerate?: boolean) => {
      const res = await api.post('/ai/content/generate', {
        type: selectedType,
        tone,
        property_id: propertyId || undefined,
        regenerate: !!regenerate,
      })
      return res.data.data as { output: string; meta?: { reason?: string } }
    },
    onSuccess: (data) => {
      setOutput(data.output)
      setReason(data.meta?.reason ?? '')
    },
  })

  const copyOutput = async () => {
    await navigator.clipboard.writeText(output || daily?.output || '')
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  if (!hasAi) {
    return (
      <div className="max-w-lg mx-auto text-center py-16 space-y-4">
        <Sparkles className="h-14 w-14 mx-auto text-muted" />
        <h1 className="text-xl font-bold">دستیار هوشمند تولید محتوا</h1>
        <p className="text-muted text-sm leading-relaxed">
          این ماژول در پلن <strong>دفتر حرفه‌ای (Premium)</strong> فعال است.
          سناریوی ریلز، کپشن، تقویم محتوا، تحلیل بازار و بیش از ۱۵ ابزار بازاریابی اختصاصی.
        </p>
        <Link to="/subscription"><Button>ارتقا به پلن حرفه‌ای</Button></Link>
      </div>
    )
  }

  const groups = [...new Set(types?.map((t) => t.group) ?? [])]
  const displayOutput = output || (selectedType === 'daily_plan' && !generate.isSuccess ? daily?.output : '')

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <Sparkles className="h-7 w-7 text-primary" />
            دستیار هوشمند تولید محتوا
          </h1>
          <p className="text-muted text-sm mt-1">مدیر مارکتینگ اختصاصی دفتر — بدون نیاز به نوشتن پرامپت</p>
        </div>
        <Badge variant="outline" className="border-primary text-primary">پلن حرفه‌ای</Badge>
      </div>

      {daily?.output && (
        <Card className="border-primary/30 bg-primary/5">
          <CardHeader className="pb-2">
            <CardTitle className="text-base flex items-center gap-2"><Sun className="h-5 w-5 text-warning" />برنامه امروز</CardTitle>
          </CardHeader>
          <CardContent>
            <pre className="whitespace-pre-wrap text-sm leading-relaxed font-sans">{daily.output}</pre>
          </CardContent>
        </Card>
      )}

      <div className="grid lg:grid-cols-3 gap-6">
        <div className="lg:col-span-1 space-y-4">
          <Card>
            <CardHeader><CardTitle className="text-sm">نوع خروجی</CardTitle></CardHeader>
            <CardContent className="space-y-4 max-h-[50vh] overflow-y-auto">
              {groups.map((group) => (
                <div key={group}>
                  <p className="text-xs text-muted font-medium mb-2">{group}</p>
                  <div className="space-y-1">
                    {types?.filter((t) => t.group === group).map((t) => {
                      const Icon = ICONS[t.icon] ?? Sparkles
                      return (
                        <button
                          key={t.id}
                          type="button"
                          onClick={() => { setSelectedType(t.id); setOutput('') }}
                          className={`w-full flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-right transition-colors ${selectedType === t.id ? 'bg-primary/15 text-primary' : 'hover:bg-muted/10'}`}
                        >
                          <Icon className="h-4 w-4 shrink-0" />
                          {t.label}
                        </button>
                      )
                    })}
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-4 space-y-3">
              <div>
                <label className="text-xs text-muted mb-1 block">لحن</label>
                <div className="relative">
                  <select
                    value={tone}
                    onChange={(e) => setTone(e.target.value)}
                    className="w-full rounded-xl border border-card-border bg-background px-3 py-2 text-sm appearance-none"
                  >
                    {TONES.map((t) => <option key={t.id} value={t.id}>{t.label}</option>)}
                  </select>
                  <ChevronDown className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted pointer-events-none" />
                </div>
              </div>

              <div>
                <label className="text-xs text-muted mb-1 block">فایل ملک (اختیاری)</label>
                <select
                  value={propertyId}
                  onChange={(e) => setPropertyId(e.target.value ? Number(e.target.value) : '')}
                  className="w-full rounded-xl border border-card-border bg-background px-3 py-2 text-sm"
                >
                  <option value="">خودکار — بهترین فایل</option>
                  {properties?.map((p) => <option key={p.id} value={p.id}>{p.label}</option>)}
                </select>
              </div>

              <Button className="w-full" onClick={() => generate.mutate(undefined)} disabled={generate.isPending}>
                <Sparkles className="h-4 w-4" />
                {generate.isPending ? 'در حال تولید…' : 'تولید با یک کلیک'}
              </Button>
            </CardContent>
          </Card>
        </div>

        <Card className="lg:col-span-2">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="text-base">خروجی</CardTitle>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => generate.mutate(true)} disabled={generate.isPending}>
                <RefreshCw className="h-4 w-4" />
                بازتولید
              </Button>
              <Button variant="outline" size="sm" onClick={copyOutput} disabled={!displayOutput}>
                <Copy className="h-4 w-4" />
                {copied ? 'کپی شد!' : 'کپی'}
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            {reason && (
              <p className="text-xs text-muted mb-3 p-2 rounded-lg bg-muted/10">💡 {reason}</p>
            )}
            {displayOutput ? (
              <pre className="whitespace-pre-wrap text-sm leading-relaxed font-sans max-h-[60vh] overflow-y-auto">{displayOutput}</pre>
            ) : (
              <p className="text-muted text-sm text-center py-16">نوع خروجی را انتخاب کنید و «تولید با یک کلیک» را بزنید</p>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
