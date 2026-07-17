import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Smartphone, Monitor, Download, Globe, ArrowLeft } from 'lucide-react'
import api from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { SeoHead } from '@/components/seo/SeoHead'
import { SiteFooter } from '@/components/layout/SiteFooter'
import { trackDownloadClick } from '@/lib/analytics'

interface Release {
  id: number
  platform: string
  version: string
  title: string
  description?: string
  download_url: string
  file_size?: string
}

const platformMeta: Record<string, { icon: typeof Smartphone; label: string }> = {
  android: { icon: Smartphone, label: 'اندروید' },
  windows: { icon: Monitor, label: 'ویندوز' },
  pwa: { icon: Globe, label: 'PWA (وب‌اپ)' },
}

export function DownloadPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['downloads'],
    queryFn: async () => {
      const res = await api.get('/downloads')
      return res.data.data as Record<string, Release[]>
    },
  })

  const platforms = ['android', 'windows', 'pwa']

  return (
    <div className="min-h-screen bg-background text-foreground">
      <SeoHead
        title="دانلود اپلیکیشن پوشه"
        description="دانلود نسخه اندروید، ویندوز و PWA نرم‌افزار پوشه — سامانه مدیریت املاک"
        path="/download"
      />
      <header className="border-b border-card-border glass">
        <div className="container mx-auto flex h-16 max-w-4xl items-center justify-between px-4">
          <Link to="/" className="text-sm text-muted hover:text-primary">صفحه اصلی</Link>
          <Link to="/login"><Button size="sm">ورود</Button></Link>
        </div>
      </header>

      <main className="container mx-auto max-w-4xl px-4 py-12 space-y-8">
        <div className="text-center space-y-3">
          <h1 className="text-3xl font-bold gradient-text">دانلود پوشه</h1>
          <p className="text-muted max-w-xl mx-auto">
            نسخه ۱.۰.۲ — اندروید همگام با وب. پنل فردی: ۳ روز رایگان.
          </p>
        </div>

        {isLoading ? (
          <p className="text-center text-muted">در حال بارگذاری…</p>
        ) : (
          <div className="grid gap-6 md:grid-cols-3">
            {platforms.map((platform) => {
              const meta = platformMeta[platform]
              const Icon = meta.icon
              const release = data?.[platform]?.[0]
              return (
                <Card key={platform} className="glass">
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                      <Icon className="h-5 w-5 text-primary" />
                      {meta.label}
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    {release ? (
                      <>
                        <p className="font-medium">{release.title}</p>
                        <p className="text-sm text-muted leading-relaxed">{release.description}</p>
                        <p className="text-xs text-muted">نسخه {release.version} · {release.file_size || '—'}</p>
                        <a
                          href={release.download_url}
                          target="_blank"
                          rel="noreferrer"
                          onClick={() => trackDownloadClick(platform, release.version)}
                        >
                          <Button className="w-full">
                            <Download className="h-4 w-4" />
                            دانلود
                          </Button>
                        </a>
                      </>
                    ) : (
                      <p className="text-sm text-muted">به‌زودی منتشر می‌شود.</p>
                    )}
                  </CardContent>
                </Card>
              )
            })}
          </div>
        )}

        <div className="text-center">
          <Link to="/">
            <Button variant="outline">
              <ArrowLeft className="h-4 w-4" />
              بازگشت
            </Button>
          </Link>
        </div>
      </main>

      <SiteFooter />
    </div>
  )
}
