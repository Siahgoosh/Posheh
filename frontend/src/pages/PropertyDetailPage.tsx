import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowRight, Box, Copy } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { formatPrice } from '@/lib/utils'

export function PropertyDetailPage() {
  const { id } = useParams<{ id: string }>()

  const { data: property, isLoading } = useQuery({
    queryKey: ['property', id],
    queryFn: async () => (await api.get(`/properties/${id}`)).data.data,
    enabled: !!id,
  })

  const generateAd = async (platform: string) => {
    const res = await api.post('/tools/ad-copy', { property_id: Number(id), platform })
    const text = res.data.data.text
    await navigator.clipboard.writeText(text)
    alert(`متن ${platform} کپی شد!`)
  }

  if (isLoading) return <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
  if (!property) return <p className="text-muted">ملک یافت نشد</p>

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Link to="/properties"><Button variant="ghost" size="icon"><ArrowRight className="h-5 w-5" /></Button></Link>
        <div className="flex-1">
          <h1 className="text-2xl font-bold">{property.code}</h1>
          <p className="text-muted text-sm">{property.city} {property.district}</p>
        </div>
        <Link to={`/properties/${id}/edit`}><Button variant="outline">ویرایش</Button></Link>
      </div>

      <div className="grid md:grid-cols-3 gap-4">
        <Card className="p-4"><p className="text-xs text-muted">قیمت</p><p className="text-xl font-bold text-primary">{formatPrice(property.price)}</p></Card>
        <Card className="p-4"><p className="text-xs text-muted">متراژ</p><p className="text-xl font-bold">{property.area} متر</p></Card>
        <Card className="p-4"><p className="text-xs text-muted">نوع</p><p className="text-xl font-bold">{property.type_label || property.type}</p></Card>
      </div>

      {property.description && <Card className="p-4"><p className="text-sm leading-relaxed">{property.description}</p></Card>}

      <Card className="p-4 space-y-3">
        <h2 className="font-semibold flex items-center gap-2"><Copy className="h-4 w-4" />کپی آگهی چندپلتفرمی</h2>
        <div className="flex flex-wrap gap-2">
          {['divar', 'sheypoor', 'instagram', 'telegram', 'whatsapp'].map((p) => (
            <Button key={p} size="sm" variant="outline" onClick={() => generateAd(p)}>{p}</Button>
          ))}
        </div>
      </Card>

      <Card className="p-4">
        <h2 className="font-semibold flex items-center gap-2 mb-3"><Box className="h-4 w-4" />تور مجازی</h2>
        <Link to="/virtual-tours"><Button size="sm">ساخت تور ۳۶۰ درجه</Button></Link>
      </Card>
    </div>
  )
}
