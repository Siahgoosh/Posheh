import { X, Phone, MessageCircle, Download, MapPin } from 'lucide-react'
import { Button } from '@/components/ui/button'
import type { TourData, TourHotspot } from '../types'
import { buildWhatsAppUrl, resolveTourCallPhone, resolveTourWhatsAppPhone } from '../utils/tourContact'

interface Props {
  hotspot: TourHotspot
  tour: TourData
  onClose: () => void
  onLeadForm?: () => void
}

export function HotspotPopup({ hotspot, tour, onClose, onLeadForm }: Props) {
  const popup = hotspot.popup || {}
  const property = popup.property || tour.property
  const images = popup.gallery || popup.images || []
  const brandColor = tour.settings?.brand_color || '#2dd4bf'
  const callPhone = resolveTourCallPhone(tour.settings, tour.office?.phone) || popup.property?.phone
  const whatsappUrl = buildWhatsAppUrl(resolveTourWhatsAppPhone(tour.settings, tour.office?.phone))

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" onClick={onClose}>
      <div
        className="w-full max-w-lg max-h-[85vh] overflow-y-auto rounded-2xl bg-[#0a0f18]/95 border border-white/10 shadow-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="sticky top-0 flex items-center justify-between p-4 border-b border-white/10 bg-black/40 backdrop-blur-md">
          <h3 className="font-bold text-lg">{popup.title || hotspot.title || 'اطلاعات'}</h3>
          <Button variant="ghost" size="icon" onClick={onClose}><X className="h-5 w-5" /></Button>
        </div>

        <div className="p-5 space-y-4">
          {(popup.description || hotspot.content) && (
            <p className="text-sm text-white/80 leading-relaxed">{popup.description || hotspot.content}</p>
          )}

          {images.length > 0 && (
            <div className="grid grid-cols-2 gap-2">
              {images.map((url, i) => (
                <img key={i} src={url} alt="" className="rounded-xl w-full aspect-video object-cover border border-white/10" />
              ))}
            </div>
          )}

          {popup.video_url && (
            <video src={popup.video_url} controls className="w-full rounded-xl border border-white/10" />
          )}

          {popup.audio_url && (
            <audio src={popup.audio_url} controls className="w-full" />
          )}

          {property && (
            <div className="rounded-xl bg-white/5 border border-white/10 p-4 space-y-2">
              <h4 className="font-semibold text-sm">مشخصات ملک</h4>
              <div className="grid grid-cols-2 gap-2 text-sm text-white/70">
                {'code' in property && <span>کد: {property.code}</span>}
                {property.area && <span>متراژ: {property.area} متر</span>}
                {property.price && <span>قیمت: {property.price.toLocaleString('fa-IR')}</span>}
                {'city' in property && property.city && <span>شهر: {property.city}</span>}
              </div>
              {popup.property?.features && (
                <ul className="text-xs text-white/60 list-disc list-inside">
                  {popup.property.features.map((f, i) => <li key={i}>{f}</li>)}
                </ul>
              )}
            </div>
          )}

          <div className="flex flex-wrap gap-2">
            {callPhone && (
              <a href={`tel:${callPhone}`}>
                <Button size="sm" variant="outline" className="border-white/20">
                  <Phone className="h-4 w-4" />تماس
                </Button>
              </a>
            )}
            {whatsappUrl && (
              <a href={whatsappUrl} target="_blank" rel="noreferrer">
                <Button size="sm" className="bg-green-600 hover:bg-green-700">
                  <MessageCircle className="h-4 w-4" />واتساپ
                </Button>
              </a>
            )}
            {hotspot.type === 'pdf' && hotspot.link_url && (
              <a href={hotspot.link_url} target="_blank" rel="noreferrer">
                <Button size="sm" variant="outline"><Download className="h-4 w-4" />دانلود</Button>
              </a>
            )}
            {hotspot.type === 'maps' && tour.settings?.map_lat && (
              <a href={`https://maps.google.com/?q=${tour.settings.map_lat},${tour.settings.map_lng}`} target="_blank" rel="noreferrer">
                <Button size="sm" variant="outline"><MapPin className="h-4 w-4" />نقشه</Button>
              </a>
            )}
          </div>

          {(popup.show_lead_form || tour.settings?.show_contact_form) && onLeadForm && (
            <Button className="w-full" style={{ background: brandColor }} onClick={onLeadForm}>
              ثبت درخواست بازدید
            </Button>
          )}
        </div>
      </div>
    </div>
  )
}
