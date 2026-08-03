import { useState } from 'react'
import { useParams } from 'react-router-dom'
import { Lock } from 'lucide-react'
import { TourViewer } from '@/features/virtual-tour'
import { usePublicTour } from '@/features/virtual-tour/hooks/usePublicTour'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

export function VirtualTourEmbedPage() {
  const { slug } = useParams<{ slug: string }>()
  const { tour, gate, verifyPassword, verifyError, isVerifying } = usePublicTour(slug)
  const [passwordInput, setPasswordInput] = useState('')

  if (gate === 'loading') {
    return (
      <div className="min-h-screen flex items-center justify-center bg-black">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  if (gate === 'password') {
    return (
      <div className="min-h-screen flex items-center justify-center bg-black p-4">
        <div className="w-full max-w-xs space-y-3 text-white text-center">
          <Lock className="h-8 w-8 mx-auto text-primary" />
          <p className="text-sm text-white/70">رمز دسترسی</p>
          <Input
            type="password"
            value={passwordInput}
            onChange={(e) => setPasswordInput(e.target.value)}
            className="text-center"
          />
          {verifyError && <p className="text-xs text-red-400">{verifyError}</p>}
          <Button
            className="w-full"
            disabled={!passwordInput || isVerifying}
            onClick={() => verifyPassword(passwordInput)}
          >
            ورود
          </Button>
        </div>
      </div>
    )
  }

  if (gate !== 'ok' || !tour) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-black text-white/60 text-sm">
        تور در دسترس نیست
      </div>
    )
  }

  return (
    <div className="h-screen w-screen bg-black overflow-hidden touch-none">
      <TourViewer tour={tour} className="h-full" showControls showFeatures publicUrl={tour.public_url} />
    </div>
  )
}
