import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { useMutation } from '@tanstack/react-query'
import {
  Share2, Phone, MessageCircle, MapPin, Images, X, Copy, Check, Eye, Lock,
} from 'lucide-react'
import api from '@/lib/api'
import { TourViewer } from '@/features/virtual-tour'
import { usePublicTour } from '@/features/virtual-tour/hooks/usePublicTour'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

export function VirtualTourPublicPage() {
  const { slug } = useParams<{ slug: string }>()
  const { tour, gate, verifyPassword, verifyError, isVerifying } = usePublicTour(slug)
  const [showGallery, setShowGallery] = useState(false)
  const [showForm, setShowForm] = useState(false)
  const [copied, setCopied] = useState(false)
  const [passwordInput, setPasswordInput] = useState('')
  const [form, setForm] = useState({ name: '', mobile: '', message: '' })

  const leadMutation = useMutation({
    mutationFn: async () => (await api.post(`/tour/${slug}/lead`, form)).data,
    onSuccess: () => {
      setForm({ name: '', mobile: '', message: '' })
      setShowForm(false)
      alert('درخواست شما ثبت شد. به زودی با شما تماس می‌گیریم.')
    },
  })

  if (gate === 'loading') {
    return (
      <div className="min-h-screen flex items-center justify-center bg-black">
        <div className="h-10 w-10 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  if (gate === 'expired') {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center gap-4 bg-[#0a0a0f] text-white">
        <p className="text-muted">این تور منقضی شده است.</p>
        <Link to="/"><Button variant="outline">صفحه اصلی</Button></Link>
      </div>
    )
  }

  if (gate === 'password') {
    return (
      <div className="min-h-screen flex items-center justify-center bg-[#0a0a0f] p-4">
        <Card className="w-full max-w-sm p-6 space-y-4">
          <div className="text-center">
            <Lock className="h-10 w-10 mx-auto mb-3 text-primary" />
            <h1 className="font-bold text-lg">تور محافظت‌شده</h1>
            <p className="text-sm text-muted mt-1">برای مشاهده، رمز دسترسی را وارد کنید.</p>
          </div>
          <Input
            type="password"
            placeholder="رمز دسترسی"
            value={passwordInput}
            onChange={(e) => setPasswordInput(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && verifyPassword(passwordInput)}
          />
          {verifyError && <p className="text-xs text-red-400">{verifyError}</p>}
          <Button
            className="w-full"
            disabled={!passwordInput || isVerifying}
            onClick={() => verifyPassword(passwordInput)}
          >
            {isVerifying ? 'در حال بررسی...' : 'ورود به تور'}
          </Button>
        </Card>
      </div>
    )
  }

  if (gate === 'denied' || !tour) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center gap-4">
        <p className="text-muted">تور مجازی یافت نشد یا دسترسی مجاز نیست.</p>
        <Link to="/"><Button variant="outline">صفحه اصلی</Button></Link>
      </div>
    )
  }

  const shareUrl = tour.public_url || `${window.location.origin}/tour/${slug}`
  const whatsapp = tour.settings?.whatsapp || tour.settings?.phone
  const mapUrl = tour.settings?.map_lat && tour.settings?.map_lng
    ? `https://maps.google.com/?q=${tour.settings.map_lat},${tour.settings.map_lng}`
    : null

  const copyLink = async () => {
    await navigator.clipboard.writeText(shareUrl)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  return (
    <div className="min-h-screen bg-[#0a0a0f] text-white flex flex-col">
      <header className="flex items-center justify-between px-4 py-3 border-b border-white/10 bg-black/40 backdrop-blur z-30">
        <div className="flex items-center gap-3">
          <div
            className="w-9 h-9 rounded-lg flex items-center justify-center text-sm font-bold"
            style={{ background: tour.settings?.brand_color || '#2dd4bf' }}
          >
            ۳۶۰
          </div>
          <div>
            <h1 className="font-bold text-sm md:text-base leading-tight">{tour.title}</h1>
            {tour.office?.name && <p className="text-xs text-white/60">{tour.office.name}</p>}
          </div>
        </div>
        <div className="flex items-center gap-2">
          <Badge variant="outline" className="text-xs gap-1 border-white/20">
            <Eye className="h-3 w-3" />{tour.view_count}
          </Badge>
          <Button size="sm" variant="ghost" onClick={copyLink} className="text-white">
            {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
          </Button>
          <Button size="sm" variant="ghost" onClick={() => navigator.share?.({ title: tour.title, url: shareUrl })} className="text-white">
            <Share2 className="h-4 w-4" />
          </Button>
        </div>
      </header>

      <div className="flex-1 relative min-h-[65vh]">
        <TourViewer tour={tour} className="h-full" showControls showSceneName showFeatures publicUrl={shareUrl} />
      </div>

      <div className="border-t border-white/10 bg-black/60 backdrop-blur px-4 py-4">
        <div className="max-w-4xl mx-auto flex flex-wrap items-center justify-between gap-4">
          <div>
            {tour.property && (
              <div className="flex flex-wrap gap-3 text-sm text-white/80">
                <span>کد: {tour.property.code}</span>
                {tour.property.area && <span>{tour.property.area} متر</span>}
                {tour.property.city && (
                  <span className="flex items-center gap-1"><MapPin className="h-3 w-3" />{tour.property.city}</span>
                )}
              </div>
            )}
            {tour.description && <p className="text-xs text-white/50 mt-1">{tour.description}</p>}
          </div>

          <div className="flex flex-wrap gap-2">
            {tour.settings?.show_gallery && tour.gallery && tour.gallery.length > 0 && (
              <Button size="sm" variant="outline" onClick={() => setShowGallery(true)} className="border-white/20">
                <Images className="h-4 w-4" />گالری
              </Button>
            )}
            {mapUrl && (
              <a href={mapUrl} target="_blank" rel="noreferrer">
                <Button size="sm" variant="outline" className="border-white/20"><MapPin className="h-4 w-4" />نقشه</Button>
              </a>
            )}
            {whatsapp && (
              <a href={`https://wa.me/98${whatsapp.replace(/^0/, '')}`} target="_blank" rel="noreferrer">
                <Button size="sm" className="bg-green-600 hover:bg-green-700"><MessageCircle className="h-4 w-4" />واتساپ</Button>
              </a>
            )}
            {(tour.settings?.phone || tour.office?.phone) && (
              <a href={`tel:${tour.settings?.phone || tour.office?.phone}`}>
                <Button size="sm" variant="outline" className="border-white/20"><Phone className="h-4 w-4" />تماس</Button>
              </a>
            )}
            {tour.settings?.show_contact_form !== false && (
              <Button size="sm" onClick={() => setShowForm(true)} style={{ background: tour.settings?.brand_color || '#2dd4bf' }}>
                درخواست بازدید
              </Button>
            )}
          </div>
        </div>
      </div>

      {showGallery && tour.gallery && (
        <div className="fixed inset-0 z-50 bg-black/90 flex flex-col">
          <div className="flex justify-between items-center p-4">
            <h2 className="font-bold">گالری تصاویر</h2>
            <Button variant="ghost" size="icon" onClick={() => setShowGallery(false)}><X /></Button>
          </div>
          <div className="flex-1 overflow-y-auto p-4 grid grid-cols-2 md:grid-cols-3 gap-3">
            {tour.gallery.map((g) => (
              <Card key={g.id} className="overflow-hidden">
                <img src={g.url} alt={g.title || ''} className="w-full aspect-video object-cover" loading="lazy" />
                {g.title && <p className="p-2 text-xs text-muted">{g.title}</p>}
              </Card>
            ))}
          </div>
        </div>
      )}

      {showForm && (
        <div className="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
          <Card className="w-full max-w-md p-6 space-y-4">
            <div className="flex justify-between items-center">
              <h2 className="font-bold text-lg">درخواست مشاوره / بازدید</h2>
              <Button variant="ghost" size="icon" onClick={() => setShowForm(false)}><X /></Button>
            </div>
            <Input placeholder="نام و نام خانوادگی" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
            <Input placeholder="شماره همراه" value={form.mobile} onChange={(e) => setForm({ ...form, mobile: e.target.value })} />
            <Input placeholder="پیام (اختیاری)" value={form.message} onChange={(e) => setForm({ ...form, message: e.target.value })} />
            <Button
              className="w-full"
              disabled={!form.name || !form.mobile || leadMutation.isPending}
              onClick={() => leadMutation.mutate()}
            >
              {leadMutation.isPending ? 'در حال ارسال...' : 'ثبت درخواست'}
            </Button>
          </Card>
        </div>
      )}

      <footer className="text-center py-3 text-xs text-white/40 border-t border-white/5">
        تور مجازی ۳۶۰ درجه — قدرت گرفته از <Link to="/" className="text-primary hover:underline">پوشه</Link>
      </footer>
    </div>
  )
}
