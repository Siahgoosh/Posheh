import { useState } from 'react'
import { Copy, Check, QrCode, Code, Lock, Globe, Link2, Calendar } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import type { TourData } from '../types'

interface Props {
  tour: TourData
  onUpdate: (data: Record<string, unknown>) => void
  isSaving?: boolean
}

export function SharingPanel({ tour, onUpdate, isSaving }: Props) {
  const [copied, setCopied] = useState<string | null>(null)
  const [password, setPassword] = useState('')
  const [expiresAt, setExpiresAt] = useState(tour.expires_at?.slice(0, 16) || '')

  const publicUrl = tour.public_url || `${window.location.origin}/tour/${tour.slug}`
  const privateUrl = tour.private_url || publicUrl
  const embedUrl = tour.embed_url || `${window.location.origin}/embed/tour/${tour.slug}`
  const embedCode = `<iframe src="${embedUrl}" width="100%" height="500" frameborder="0" allowfullscreen loading="lazy"></iframe>`

  const copy = async (text: string, key: string) => {
    await navigator.clipboard.writeText(text)
    setCopied(key)
    setTimeout(() => setCopied(null), 2000)
  }

  return (
    <div className="p-4 space-y-5 overflow-y-auto">
      <div>
        <h2 className="font-semibold text-sm flex items-center gap-2">
          <Link2 className="h-4 w-4 text-primary" />
          اشتراک‌گذاری
        </h2>
        <p className="text-[11px] text-muted mt-0.5">لینک، QR، Embed و کنترل دسترسی</p>
      </div>

      <div className="flex gap-2">
        {(['public', 'private'] as const).map((v) => (
          <button
            key={v}
            type="button"
            onClick={() => onUpdate({ visibility: v })}
            className={`flex-1 py-2 rounded-xl text-xs border transition-all ${
              (tour.visibility || 'public') === v
                ? 'bg-primary/20 border-primary/40 text-primary'
                : 'bg-black/20 border-card-border/50 text-muted hover:border-white/10'
            }`}
          >
            {v === 'public' ? <><Globe className="h-3 w-3 inline ml-1" />عمومی</> : <><Lock className="h-3 w-3 inline ml-1" />خصوصی</>}
          </button>
        ))}
      </div>

      <div className="space-y-2">
        <label className="text-xs text-muted">لینک عمومی</label>
        <div className="flex gap-2">
          <Input value={publicUrl} readOnly className="text-xs font-mono" />
          <Button size="icon" variant="outline" onClick={() => copy(publicUrl, 'public')}>
            {copied === 'public' ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
          </Button>
        </div>
      </div>

      {tour.visibility === 'private' && (
        <div className="space-y-2">
          <label className="text-xs text-muted">لینک خصوصی (با توکن)</label>
          <div className="flex gap-2">
            <Input value={privateUrl} readOnly className="text-xs font-mono" />
            <Button size="icon" variant="outline" onClick={() => copy(privateUrl, 'private')}>
              {copied === 'private' ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
            </Button>
          </div>
          <Button size="sm" variant="outline" className="w-full" onClick={() => onUpdate({ regenerate_token: true })} disabled={isSaving}>
            تولید توکن جدید
          </Button>
        </div>
      )}

      <div className="space-y-2">
        <label className="text-xs text-muted flex items-center gap-1"><Lock className="h-3 w-3" />رمز دسترسی (اختیاری)</label>
        <Input
          type="password"
          placeholder={tour.has_password ? 'رمز جدید...' : 'تنظیم رمز'}
          value={password}
          onChange={(e) => setPassword(e.target.value)}
        />
        {password && (
          <Button size="sm" className="w-full" onClick={() => { onUpdate({ access_password: password }); setPassword('') }} disabled={isSaving}>
            ذخیره رمز
          </Button>
        )}
        {tour.has_password && (
          <Button size="sm" variant="outline" className="w-full" onClick={() => onUpdate({ access_password: '' })} disabled={isSaving}>
            حذف رمز
          </Button>
        )}
      </div>

      <div className="space-y-2">
        <label className="text-xs text-muted flex items-center gap-1"><Calendar className="h-3 w-3" />تاریخ انقضا</label>
        <Input
          type="datetime-local"
          value={expiresAt}
          onChange={(e) => setExpiresAt(e.target.value)}
        />
        <Button size="sm" variant="outline" className="w-full" onClick={() => onUpdate({ expires_at: expiresAt || null })} disabled={isSaving}>
          ذخیره انقضا
        </Button>
      </div>

      <div className="rounded-xl border border-card-border/50 p-3 space-y-2">
        <div className="flex items-center justify-between">
          <span className="text-xs font-medium">QR Code</span>
          <a href={`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(publicUrl)}`} target="_blank" rel="noreferrer">
            <Button size="sm" variant="ghost"><QrCode className="h-4 w-4" /></Button>
          </a>
        </div>
        <img
          src={`https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(publicUrl)}`}
          alt="QR"
          className="mx-auto rounded-lg border border-white/10"
          loading="lazy"
        />
      </div>

      <div className="space-y-2">
        <label className="text-xs text-muted flex items-center gap-1"><Code className="h-3 w-3" />کد Embed / Iframe</label>
        <textarea
          readOnly
          value={embedCode}
          className="w-full text-[10px] font-mono bg-black/30 border border-card-border rounded-lg p-2 h-20"
          onClick={(e) => (e.target as HTMLTextAreaElement).select()}
        />
        <Button size="sm" variant="outline" className="w-full" onClick={() => copy(embedCode, 'embed')}>
          {copied === 'embed' ? 'کپی شد!' : 'کپی کد Embed'}
        </Button>
      </div>

      <div className="flex flex-wrap gap-2">
        <Badge variant="outline">نسخه {tour.version ?? 1}</Badge>
        {tour.has_password && <Badge variant="outline">رمزدار</Badge>}
        {tour.expires_at && <Badge variant="outline">انقضا دارد</Badge>}
      </div>
    </div>
  )
}
