import { useEffect, useState } from 'react'
import { MessageCircle, Send, X, Share2 } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { normalizeMobile, toEnglishDigits } from '@/lib/utils'

interface PropertyShareModalProps {
  propertyId: number
  onClose: () => void
}

type ShareChannel = 'whatsapp' | 'telegram' | 'rubika' | 'bale'

const channelLabels: Record<ShareChannel, string> = {
  whatsapp: 'واتساپ',
  telegram: 'تلگرام',
  rubika: 'روبیکا',
  bale: 'بله',
}

function isAndroid(): boolean {
  return typeof navigator !== 'undefined' && /Android/i.test(navigator.userAgent)
}

export function PropertyShareModal({ propertyId, onClose }: PropertyShareModalProps) {
  const [mobile, setMobile] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')
  const [preview, setPreview] = useState('')

  const loadPreview = async () => {
    try {
      const { data } = await api.get(`/properties/${propertyId}/share-message`)
      setPreview(data.data.message)
    } catch {
      setPreview('')
    }
  }

  useEffect(() => { loadPreview() }, [propertyId])

  const share = async (channel: ShareChannel) => {
    setError('')
    setSuccess('')
    const normalized = normalizeMobile(mobile)
    if (normalized.length < 11) {
      setError('شماره موبایل معتبر وارد کنید')
      return
    }
    setLoading(true)
    try {
      const { data } = await api.post(`/properties/${propertyId}/share`, {
        recipient_mobile: normalized,
        channel,
      })
      const url = data.data.url as string
      const message = data.data.message as string

      if (channel === 'rubika' || channel === 'bale') {
        await navigator.clipboard.writeText(message)
        if (isAndroid() && url) {
          window.location.href = url
        }
        setSuccess(`متن فایل کپی شد — ${channelLabels[channel]} را باز کنید و برای ${toPersianMobile(normalized)} ارسال کنید`)
        return
      }

      window.open(url, '_blank', 'noopener,noreferrer')
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string } } }
      setError(e.response?.data?.message || 'خطا در ارسال')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" onClick={onClose}>
      <Card className="w-full max-w-md" onClick={(e) => e.stopPropagation()}>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="text-lg flex items-center gap-2">
            <Send className="h-5 w-5 text-primary" />
            ارسال فایل به مشتری
          </CardTitle>
          <button type="button" onClick={onClose} className="text-muted hover:text-foreground">
            <X className="h-5 w-5" />
          </button>
        </CardHeader>
        <CardContent className="space-y-4">
          <div>
            <label className="text-sm text-muted mb-2 block">شماره گیرنده</label>
            <Input
              type="tel"
              placeholder="۰۹۱۲۱۲۳۴۵۶۷"
              value={mobile}
              onChange={(e) => setMobile(toEnglishDigits(e.target.value).replace(/\D/g, '').slice(0, 11))}
              dir="ltr"
              className="text-center tracking-widest"
            />
          </div>
          {preview && (
            <div className="rounded-xl border border-card-border bg-background/50 p-3 text-xs text-muted whitespace-pre-wrap max-h-32 overflow-y-auto">
              {preview}
            </div>
          )}
          {error && <p className="text-sm text-danger">{error}</p>}
          {success && <p className="text-sm text-success">{success}</p>}
          <div className="grid grid-cols-2 gap-2">
            <Button
              className="bg-[#25D366] hover:bg-[#20bd5a] text-white"
              disabled={loading || mobile.length < 11}
              onClick={() => share('whatsapp')}
            >
              <MessageCircle className="h-4 w-4 ml-2" />
              واتساپ
            </Button>
            <Button
              variant="outline"
              disabled={loading || mobile.length < 11}
              onClick={() => share('telegram')}
            >
              <Send className="h-4 w-4 ml-2" />
              تلگرام
            </Button>
            <Button
              className="bg-[#7B2CBF] hover:bg-[#6a25a8] text-white"
              disabled={loading || mobile.length < 11}
              onClick={() => share('rubika')}
            >
              <Share2 className="h-4 w-4 ml-2" />
              روبیکا
            </Button>
            <Button
              className="bg-[#E63946] hover:bg-[#cf3240] text-white"
              disabled={loading || mobile.length < 11}
              onClick={() => share('bale')}
            >
              <Share2 className="h-4 w-4 ml-2" />
              بله
            </Button>
          </div>
          <p className="text-xs text-muted leading-relaxed">
            واتساپ مستقیم به شماره باز می‌شود. تلگرام، روبیکا و بله متن فایل را آماده می‌کنند — در اندروید اپ مربوطه باز می‌شود.
          </p>
        </CardContent>
      </Card>
    </div>
  )
}

function toPersianMobile(mobile: string): string {
  const digits = '۰۱۲۳۴۵۶۷۸۹'
  return mobile.replace(/\d/g, (d) => digits[Number(d)])
}
