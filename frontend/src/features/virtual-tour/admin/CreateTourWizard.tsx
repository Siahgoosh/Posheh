import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import axios from 'axios'
import { Box, Footprints, X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'
import { tourApi } from '../api/tourApi'
import type { TourType } from '../types'

interface Props {
  open: boolean
  onClose: () => void
  onCreated?: () => void
}

export function CreateTourWizard({ open, onClose, onCreated }: Props) {
  const navigate = useNavigate()
  const [step, setStep] = useState(1)
  const [tourType, setTourType] = useState<TourType | null>(null)
  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)

  if (!open) return null

  const reset = () => {
    setStep(1)
    setTourType(null)
    setTitle('')
    setDescription('')
    setError(null)
  }

  const handleClose = () => {
    reset()
    onClose()
  }

  const handleCreate = async () => {
    if (!tourType || !title.trim()) return
    setIsSubmitting(true)
    setError(null)
    try {
      const res = await tourApi.create({
        title: title.trim(),
        description: description.trim() || undefined,
        tour_type: tourType,
      })
      const tour = res.data.data as { id: number }
      onCreated?.()
      handleClose()
      navigate(`/virtual-tours/${tour.id}/edit`)
    } catch (err: unknown) {
      if (axios.isAxiosError(err)) {
        const data = err.response?.data as { message?: string; code?: string } | undefined
        if (data?.code === 'schema_outdated') {
          setError('دیتابیس به‌روز نیست. لطفاً migrate را روی سرور اجرا کنید.')
        } else if (err.response?.status === 500) {
          setError(data?.message || 'خطای سرور هنگام ایجاد تور. با پشتیبانی تماس بگیرید.')
        } else if (err.response?.status === 422) {
          setError(data?.message || 'اطلاعات وارد شده معتبر نیست.')
        } else {
          setError(data?.message || 'ایجاد تور ناموفق بود.')
        }
      } else {
        setError('ایجاد تور ناموفق بود.')
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
      <Card className="w-full max-w-lg p-6 space-y-5 relative">
        <button
          type="button"
          onClick={handleClose}
          className="absolute top-4 right-4 text-muted hover:text-foreground"
        >
          <X className="h-5 w-5" />
        </button>

        <div>
          <h2 className="text-xl font-bold">ساخت تور جدید</h2>
          <p className="text-sm text-muted mt-1">
            {step === 1 ? 'مرحله ۱: نوع تور را انتخاب کنید' : 'مرحله ۲: اطلاعات تور'}
          </p>
        </div>

        {step === 1 && (
          <div className="grid gap-3">
            <button
              type="button"
              onClick={() => setTourType('smart_walk')}
              className={`flex items-start gap-4 p-4 rounded-xl border-2 text-left transition-all ${
                tourType === 'smart_walk'
                  ? 'border-primary bg-primary/10'
                  : 'border-card-border hover:border-primary/40'
              }`}
            >
              <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500/30 to-teal-600/30 flex items-center justify-center shrink-0">
                <Footprints className="h-6 w-6 text-primary" />
              </div>
              <div>
                <p className="font-semibold">Poshe Smart Walk</p>
                <p className="text-xs text-muted mt-1">
                  تور تعاملی با عکس‌های معمولی موبایل — بدون دوربین ۳۶۰، بدون استیچینگ
                </p>
              </div>
            </button>

            <button
              type="button"
              onClick={() => setTourType('panorama_360')}
              className={`flex items-start gap-4 p-4 rounded-xl border-2 text-left transition-all ${
                tourType === 'panorama_360'
                  ? 'border-primary bg-primary/10'
                  : 'border-card-border hover:border-primary/40'
              }`}
            >
              <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500/30 to-blue-600/30 flex items-center justify-center shrink-0">
                <Box className="h-6 w-6 text-violet-400" />
              </div>
              <div>
                <p className="font-semibold">360 Tour</p>
                <p className="text-xs text-muted mt-1">
                  پانورامای equirectangular — Insta360، Ricoh Theta، GoPro Max، Kandao
                </p>
              </div>
            </button>

            <Button
              className="w-full mt-2"
              disabled={!tourType}
              onClick={() => setStep(2)}
            >
              ادامه
            </Button>
          </div>
        )}

        {step === 2 && (
          <div className="space-y-4">
            <Input
              placeholder="عنوان تور"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
            />
            <Input
              placeholder="توضیحات (اختیاری)"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
            />
            {error && <p className="text-xs text-red-400">{error}</p>}
            <div className="flex gap-2">
              <Button variant="outline" onClick={() => setStep(1)}>بازگشت</Button>
              <Button
                className="flex-1"
                disabled={!title.trim() || isSubmitting}
                onClick={handleCreate}
              >
                {isSubmitting ? 'در حال ایجاد...' : 'ایجاد و ورود به ویرایشگر'}
              </Button>
            </div>
          </div>
        )}
      </Card>
    </div>
  )
}
