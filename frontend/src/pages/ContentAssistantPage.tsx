import { useMutation, useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import {
  Sparkles, Copy, RefreshCw, Sun, Video, PenLine, Calendar, BarChart3,
  MessageCircle, Target, Rocket, Hash, Clock, Film, AlertTriangle, Gift, ChevronDown,
  Wand2, TrendingUp, Share2, ImageIcon,
} from 'lucide-react'
import api from '@/lib/api'
import { usePlanFeature } from '@/components/SubscriptionGuard'
import { isPlatformStaffRole } from '@/lib/subdomain'
import { useAuthStore } from '@/stores/auth'
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
  { id: 'friendly', label: 'صمیمی', emoji: '😊' },
  { id: 'formal', label: 'رسمی', emoji: '📋' },
  { id: 'luxury', label: 'لوکس', emoji: '✨' },
  { id: 'investment', label: 'سرمایه‌گذاری', emoji: '📈' },
  { id: 'educational', label: 'آموزشی', emoji: '🎓' },
]

const HIGHLIGHTS = [
  { icon: Video, title: 'سناریوی ریلز', desc: 'ایده و متن آماده برای اینستاگرام' },
  { icon: Share2, title: 'کپشن شبکه‌ها', desc: 'واتساپ، تلگرام، دیوار' },
  { icon: Calendar, title: 'تقویم محتوا', desc: 'برنامه هفتگی پست و استوری' },
  { icon: TrendingUp, title: 'تحلیل بازار', desc: 'بینش منطقه و قیمت' },
]

export function ContentAssistantPage() {
  const { user } = useAuthStore()
  const hasAi = usePlanFeature('content_assistant') || isPlatformStaffRole(user?.role)
  const [selectedType, setSelectedType] = useState('daily_plan')
  const [tone, setTone] = useState('friendly')
  const [propertyId, setPropertyId] = useState<number | ''>('')
  const [output, setOutput] = useState('')
  const [reason, setReason] = useState('')
  const [copied, setCopied] = useState(false)
  const [genError, setGenError] = useState('')

  const { data: types } = useQuery({
    queryKey: ['ai-content-types'],
    queryFn: async () => (await api.get('/ai/content/types')).data.data as {
      id: string; label: string; icon: string; group: string
    }[],
    enabled: hasAi,
  })

  const { data: properties, isError: propertiesError } = useQuery({
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
      setGenError('')
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      setGenError(
        Object.values(e.response?.data?.errors ?? {}).flat().join('، ')
          || e.response?.data?.message
          || 'خطا در تولید محتوا — دوباره تلاش کنید',
      )
    },
  })

  const copyOutput = async () => {
    await navigator.clipboard.writeText(output || daily?.output || '')
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  if (!hasAi) {
    return (
      <div className="max-w-2xl mx-auto text-center py-16 space-y-6 animate-fade-in">
        <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br from-primary/20 to-accent/20">
          <Sparkles className="h-10 w-10 text-primary" />
        </div>
        <h1 className="text-2xl font-bold gradient-text">دستیار هوشمند تولید محتوا</h1>
        <p className="text-muted leading-relaxed">
          این ماژول در پلن <strong>دفتر حرفه‌ای (Premium)</strong> فعال است.
          سناریوی ریلز، کپشن، تقویم محتوا، تحلیل بازار و بیش از ۱۵ ابزار بازاریابی اختصاصی.
        </p>
        <div className="grid sm:grid-cols-2 gap-3 text-right">
          {HIGHLIGHTS.map((h) => (
            <div key={h.title} className="glass rounded-xl p-4 flex gap-3 items-start">
              <h.icon className="h-5 w-5 text-primary shrink-0 mt-0.5" />
              <div>
                <p className="font-medium text-sm">{h.title}</p>
                <p className="text-xs text-muted">{h.desc}</p>
              </div>
            </div>
          ))}
        </div>
        <Link to="/subscription"><Button size="lg">ارتقا به پلن حرفه‌ای</Button></Link>
      </div>
    )
  }

  const groups = [...new Set(types?.map((t) => t.group) ?? [])]
  const displayOutput = output || (selectedType === 'daily_plan' && !generate.isSuccess ? daily?.output : '')
  const selectedLabel = types?.find((t) => t.id === selectedType)?.label

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Hero */}
      <div className="relative overflow-hidden rounded-2xl glass card-shine p-6 md:p-8">
        <div className="absolute top-0 left-0 w-40 h-40 bg-primary/10 rounded-full blur-3xl" />
        <div className="absolute bottom-0 right-0 w-56 h-56 bg-accent/10 rounded-full blur-3xl" />
        <div className="relative flex flex-wrap items-start justify-between gap-4">
          <div className="space-y-2">
            <div className="flex items-center gap-2">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/15">
                <Wand2 className="h-5 w-5 text-primary" />
              </div>
              <Badge variant="outline" className="border-primary/50 text-primary">پلن حرفه‌ای</Badge>
            </div>
            <h1 className="text-2xl md:text-3xl font-bold gradient-text">دستیار هوشمند تولید محتوا</h1>
            <p className="text-muted text-sm max-w-xl">
              مدیر مارکتینگ اختصاصی دفتر — داده از فایلینگ و CRM شما خوانده می‌شود؛ بدون نیاز به نوشتن پرامپت.
            </p>
          </div>
          <div className="hidden md:grid grid-cols-2 gap-2">
            {HIGHLIGHTS.map((h) => (
              <div key={h.title} className="flex items-center gap-2 rounded-lg bg-background/40 px-3 py-2 text-xs">
                <h.icon className="h-4 w-4 text-primary" />
                <span>{h.title}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {daily?.output && (
        <Card className="border-primary/30 bg-gradient-to-l from-primary/10 to-transparent">
          <CardHeader className="pb-2">
            <CardTitle className="text-base flex items-center gap-2">
              <Sun className="h-5 w-5 text-warning" />
              برنامه امروز شما
            </CardTitle>
          </CardHeader>
          <CardContent>
            <pre className="whitespace-pre-wrap text-sm leading-relaxed font-sans">{daily.output}</pre>
          </CardContent>
        </Card>
      )}

      <div className="grid lg:grid-cols-3 gap-6">
        <div className="lg:col-span-1 space-y-4">
          <Card className="card-shine">
            <CardHeader><CardTitle className="text-sm flex items-center gap-2"><ImageIcon className="h-4 w-4" />نوع خروجی</CardTitle></CardHeader>
            <CardContent className="space-y-4 max-h-[50vh] overflow-y-auto">
              {groups.map((group) => (
                <div key={group}>
                  <p className="text-xs text-muted font-medium mb-2 px-1">{group}</p>
                  <div className="space-y-1">
                    {types?.filter((t) => t.group === group).map((t) => {
                      const Icon = ICONS[t.icon] ?? Sparkles
                      return (
                        <button
                          key={t.id}
                          type="button"
                          onClick={() => { setSelectedType(t.id); setOutput('') }}
                          className={`w-full flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm text-right transition-all ${selectedType === t.id ? 'bg-primary/15 text-primary ring-1 ring-primary/30' : 'hover:bg-muted/10'}`}
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
                <label className="text-xs text-muted mb-1 block">لحن محتوا</label>
                <div className="flex flex-wrap gap-1.5">
                  {TONES.map((t) => (
                    <button
                      key={t.id}
                      type="button"
                      onClick={() => setTone(t.id)}
                      className={`rounded-lg px-2.5 py-1.5 text-xs transition-all ${tone === t.id ? 'bg-primary/15 text-primary ring-1 ring-primary/30' : 'bg-muted/10 hover:bg-muted/20'}`}
                    >
                      {t.emoji} {t.label}
                    </button>
                  ))}
                </div>
              </div>

              <div>
                <label className="text-xs text-muted mb-1 block">فایل ملک</label>
                <div className="relative">
                  <select
                    value={propertyId}
                    onChange={(e) => setPropertyId(e.target.value ? Number(e.target.value) : '')}
                    className="w-full rounded-xl border border-card-border bg-background px-3 py-2 text-sm appearance-none"
                  >
                    <option value="">خودکار — بهترین فایل فعال دفتر</option>
                    {properties?.map((p) => <option key={p.id} value={p.id}>{p.label}</option>)}
                  </select>
                  <ChevronDown className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted pointer-events-none" />
                </div>
                {propertiesError && <p className="text-xs text-danger mt-1">بارگذاری فایل‌ها ناموفق بود</p>}
                {!propertiesError && properties?.length === 0 && (
                  <p className="text-xs text-warning mt-1">فایل فعالی ندارید — ابتدا یک ملک ثبت کنید</p>
                )}
                <p className="text-[11px] text-muted mt-1">داده از فایلینگ خوانده می‌شود: قیمت، متراژ، منطقه، توضیحات</p>
              </div>

              {genError && <p className="text-xs text-danger">{genError}</p>}

              <Button className="w-full" size="lg" onClick={() => generate.mutate(undefined)} disabled={generate.isPending}>
                <Sparkles className="h-4 w-4" />
                {generate.isPending ? 'در حال تولید…' : 'تولید با یک کلیک'}
              </Button>
            </CardContent>
          </Card>
        </div>

        <Card className="lg:col-span-2 card-shine">
          <CardHeader className="flex flex-row items-center justify-between gap-2">
            <div>
              <CardTitle className="text-base">خروجی</CardTitle>
              {selectedLabel && <p className="text-xs text-muted mt-0.5">{selectedLabel}</p>}
            </div>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => generate.mutate(true)} disabled={generate.isPending}>
                <RefreshCw className={`h-4 w-4 ${generate.isPending ? 'animate-spin' : ''}`} />
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
              <p className="text-xs text-muted mb-3 p-3 rounded-xl bg-primary/5 border border-primary/10">💡 {reason}</p>
            )}
            {displayOutput ? (
              <pre className="whitespace-pre-wrap text-sm leading-relaxed font-sans max-h-[60vh] overflow-y-auto p-4 rounded-xl bg-background/50 border border-card-border">{displayOutput}</pre>
            ) : (
              <div className="text-center py-16 space-y-3">
                <Sparkles className="h-12 w-12 mx-auto text-muted/40" />
                <p className="text-muted text-sm">نوع خروجی را انتخاب کنید و «تولید با یک کلیک» را بزنید</p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
