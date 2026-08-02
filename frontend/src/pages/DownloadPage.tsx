import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Smartphone, Monitor, Download, Globe, ArrowLeft, Apple } from 'lucide-react'
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
  pwa: { icon: Globe, label: 'iPhone / PWA' },
}

function isIos(): boolean {
  if (typeof navigator === 'undefined') return false
  return /iPad|iPhone|iPod/.test(navigator.userAgent)
}

function PwaInstallSteps() {
  const ios = isIos()
  return (
    <ol className="text-sm text-muted space-y-2 list-decimal list-inside leading-relaxed text-right">
      {ios ? (
        <>
          <li>در Safari به <strong className="text-foreground">posheapp.ir</strong> بروید</li>
          <li>دکمه <strong className="text-foreground">اشتراک‌گذاری</strong> (مربع با فلش) را بزنید</li>
          <li><strong className="text-foreground">Add to Home Screen</strong> را انتخاب کنید</li>
          <li>نام «پوشه» را تأیید و <strong className="text-foreground">Add</strong> را بزنید</li>
        </>
      ) : (
        <>
          <li>در Chrome یا Edge به <strong className="text-foreground">posheapp.ir</strong> بروید</li>
          <li>از منوی مرورگر گزینه <strong className="text-foreground">نصب برنامه</strong> یا Install را بزنید</li>
          <li>یا از منوی ⋮ گزینه «Add to Home screen» را انتخاب کنید</li>
        </>
      )}
    </ol>
  )
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
  const latestVersion = data?.android?.[0]?.version ?? data?.windows?.[0]?.version ?? '1.0.2'

  return (
    <div className="min-h-screen bg-background text-foreground">
      <SeoHead
        title="دانلود اپلیکیشن پوشه"
        description="دانلود نسخه اندروید، ویندوز و PWA (iPhone) نرم‌افزار پوشه — سامانه مدیریت املاک"
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
            نسخه {latestVersion} — اندروید و ویندوز با آخرین تغییرات. iPhone از طریق PWA (نصب روی صفحه اصلی). پنل فردی: ۴۸ ساعت رایگان.
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
              const isPwa = platform === 'pwa'
              return (
                <Card key={platform} className="glass">
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                      {isPwa && isIos() ? (
                        <Apple className="h-5 w-5 text-primary" />
                      ) : (
                        <Icon className="h-5 w-5 text-primary" />
                      )}
                      {isPwa && isIos() ? 'iPhone (PWA)' : meta.label}
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    {release ? (
                      <>
                        <p className="font-medium">{release.title}</p>
                        <p className="text-sm text-muted leading-relaxed">{release.description}</p>
                        <p className="text-xs text-muted">نسخه {release.version} · {release.file_size || '—'}</p>
                        {isPwa ? (
                          <>
                            <PwaInstallSteps />
                            <a
                              href={release.download_url}
                              onClick={() => trackDownloadClick(platform, release.version)}
                            >
                              <Button className="w-full">
                                <Globe className="h-4 w-4" />
                                باز کردن سایت برای نصب
                              </Button>
                            </a>
                          </>
                        ) : (
                          <a
                            href={release.download_url}
                            download
                            onClick={() => trackDownloadClick(platform, release.version)}
                          >
                            <Button className="w-full">
                              <Download className="h-4 w-4" />
                              دانلود مستقیم
                            </Button>
                          </a>
                        )}
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

        <Card className="glass border-primary/20">
          <CardContent className="pt-6 space-y-3 text-sm text-muted">
            <p className="font-medium text-foreground">لینک‌های مستقیم دانلود</p>
            <ul className="space-y-1 break-all">
              <li>
                <strong className="text-foreground">اندروید:</strong>{' '}
                <a className="text-primary hover:underline" href="https://posheapp.ir/downloads/posheh-android.apk">
                  https://posheapp.ir/downloads/posheh-android.apk
                </a>
              </li>
              <li>
                <strong className="text-foreground">ویندوز:</strong>{' '}
                <a className="text-primary hover:underline" href="https://posheapp.ir/downloads/posheh-windows.zip">
                  https://posheapp.ir/downloads/posheh-windows.zip
                </a>
              </li>
              <li>
                <strong className="text-foreground">iPhone / PWA:</strong>{' '}
                <a className="text-primary hover:underline" href="https://posheapp.ir/">
                  https://posheapp.ir
                </a>{' '}
                (Safari → Add to Home Screen)
              </li>
            </ul>
          </CardContent>
        </Card>

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
