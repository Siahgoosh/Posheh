import { useEffect, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import axios from 'axios'
import api from '@/lib/api'
import { tourApi } from '../api/tourApi'
import { getTourSessionQueryParams } from '../hooks/useTourSessionAnalytics'
import type { TourData } from '../types'

export type PublicTourGate = 'loading' | 'password' | 'expired' | 'denied' | 'private' | 'ok'

export interface PublicTourPayload extends TourData {
  slug: string
  view_count: number
  session_id?: string
  seo?: {
    title?: string
    description?: string
    canonical?: string
    og_image?: string
    noindex?: boolean
    json_ld?: Record<string, unknown> | Record<string, unknown>[]
  }
  security?: {
    disable_direct_download?: boolean
    watermark_enabled?: boolean
    watermark_text?: string
  }
  gallery?: { id: number; type: string; url: string; title?: string }[]
  public_url?: string
  settings?: TourData['settings'] & {
    phone?: string
    whatsapp?: string
    show_contact_form?: boolean
    show_gallery?: boolean
    music_url?: string
  }
}

function passwordStorageKey(slug: string) {
  return `vt-pwd-${slug}`
}

export function usePublicTour(slug: string | undefined, options?: { embed?: boolean }) {
  const token = new URLSearchParams(window.location.search).get('token')
  const [password, setPassword] = useState(() =>
    slug ? sessionStorage.getItem(passwordStorageKey(slug)) || '' : '',
  )
  const [gate, setGate] = useState<PublicTourGate>('loading')
  const [deniedMessage, setDeniedMessage] = useState<string | null>(null)
  const [verifyError, setVerifyError] = useState<string | null>(null)
  const [isVerifying, setIsVerifying] = useState(false)

  const query = useQuery({
    queryKey: ['public-tour', slug, token, password, options?.embed],
    queryFn: async () => {
      const res = await api.get(`/tour/${slug}`, {
        params: {
          ...(token ? { token } : {}),
          ...(options?.embed ? { embed: 1 } : {}),
          ...getTourSessionQueryParams(),
        },
        headers: password ? { 'X-Tour-Password': password } : undefined,
      })
      return res.data.data as PublicTourPayload
    },
    enabled: !!slug,
    retry: false,
  })

  useEffect(() => {
    if (query.isLoading) {
      setGate('loading')
      return
    }
    if (query.isSuccess) {
      setGate('ok')
      setDeniedMessage(null)
      return
    }
    if (!query.error || !axios.isAxiosError(query.error)) {
      setGate('denied')
      setDeniedMessage(null)
      return
    }
    const status = query.error.response?.status
    const data = query.error.response?.data as {
      requires_password?: boolean
      expired?: boolean
      message?: string
      access?: { visibility?: string }
    }
    if (status === 403 && data?.requires_password) {
      setGate('password')
      setDeniedMessage(null)
    } else if (status === 403 && data?.access?.visibility === 'private') {
      setGate('private')
      setDeniedMessage(data.message || 'این تور خصوصی است.')
    } else if (status === 410 || data?.expired) {
      setGate('expired')
      setDeniedMessage(null)
    } else {
      setGate('denied')
      setDeniedMessage(data?.message || null)
    }
  }, [query.isLoading, query.isSuccess, query.error])

  const verifyPassword = async (value: string) => {
    if (!slug) return false
    setIsVerifying(true)
    setVerifyError(null)
    try {
      await tourApi.verifyPublicPassword(slug, value)
      sessionStorage.setItem(passwordStorageKey(slug), value)
      setPassword(value)
      return true
    } catch (e) {
      if (axios.isAxiosError(e)) {
        setVerifyError(e.response?.data?.message || 'رمز دسترسی نادرست است.')
      } else {
        setVerifyError('خطا در تأیید رمز.')
      }
      return false
    } finally {
      setIsVerifying(false)
    }
  }

  return {
    tour: query.data,
    gate,
    deniedMessage,
    verifyPassword,
    verifyError,
    isVerifying,
    isLoading: query.isLoading,
    refetch: query.refetch,
  }
}
