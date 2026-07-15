import { useEffect, useState } from 'react'
import { MessageCircle, Send, X } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { normalizeMobile, toEnglishDigits } from '@/lib/utils'

interface PropertyShareModalProps {
  propertyId: number
  onClose: () => void
}

export function PropertyShareModal({ propertyId, onClose }: PropertyShareModalProps) {
  const [mobile, setMobile] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
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

  const share = async (channel: 'whatsapp' | 'telegram') => {
    setError('')
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
      window.open(data.data.url, '_blank', 'noopener,noreferrer')
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
            ارسال فایل ملک
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
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
