import { useQuery } from '@tanstack/react-query'
import { Building, MapPin, Phone, ShieldCheck } from 'lucide-react'
import { Link } from 'react-router-dom'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { SeoHead } from '@/components/seo/SeoHead'

interface ConsultantOffice {
  id: number
  name: string
  slug: string
  city?: string
  phone?: string
  description?: string
  is_verified?: boolean
  plan_name?: string
}

export function ConsultantsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['consultants'],
    queryFn: async () => (await api.get('/consultants')).data.data as ConsultantOffice[],
  })

  return (
    <div className="min-h-screen bg-background text-foreground">
      <SeoHead title="دایرکتوری مشاوران املاک" description="لیست دفاتر املاک فعال در پوشه" path="/consultants" />
      <header className="border-b border-card-border glass">
        <div className="container mx-auto flex h-16 items-center justify-between px-4">
          <Link to="/" className="text-sm text-muted hover:text-primary">صفحه اصلی</Link>
          <Link to="/register"><Button size="sm">ثبت‌نام</Button></Link>
        </div>
      </header>
      <main className="container mx-auto px-4 py-10 space-y-6">
        <h1 className="text-3xl font-bold">دایرکتوری مشاوران</h1>
        {isLoading ? (
          <div className="flex justify-center py-20"><div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" /></div>
        ) : (
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            {data?.map((office) => (
              <Card key={office.id} className="glass">
                <CardHeader>
                  <CardTitle className="flex items-center gap-2 text-lg">
                    <Building className="h-5 w-5 text-primary" />
                    {office.name}
                    {office.is_verified && <ShieldCheck className="h-4 w-4 text-success" />}
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2 text-sm text-muted">
                  {office.city && <p className="flex items-center gap-2"><MapPin className="h-4 w-4" />{office.city}</p>}
                  {office.phone && <p className="flex items-center gap-2" dir="ltr"><Phone className="h-4 w-4" />{office.phone}</p>}
                  {office.plan_name && <p>پلن: {office.plan_name}</p>}
                  {office.description && <p className="line-clamp-3">{office.description}</p>}
                  <Link to={`/o/${office.slug}`}><Button size="sm" variant="outline" className="mt-2">مشاهده دفتر</Button></Link>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </main>
    </div>
  )
}
