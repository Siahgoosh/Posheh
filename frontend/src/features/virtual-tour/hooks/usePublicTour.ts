import { useEffect, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import axios from 'axios'
import api from '@/lib/api'
import { tourApi } from '../api/tourApi'
import type { TourData } from '../types'

export type PublicTourGate = 'loading' | 'password' | 'expired' | 'denied' | 'ok'

export interface PublicTourPayload extends TourData {
  slug: string
  view_count: number
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

export function usePublicTour(slug: string | undefined) {
  const token = new URLSearchParams(window.location.search).get('token')
  const [password, setPassword] = useState(() =>
    slug ? sessionStorage.getItem(passwordStorageKey(slug)) || '' : '',
  )
  const [gate, setGate] = useState<PublicTourGate>('loading')
  const [verifyError, setVerifyError] = useState<string | null>(null)
  const [isVerifying, setIsVerifying] = useState(false)

  const query = useQuery({
    queryKey: ['public-tour', slug, token, password],
    queryFn: async () => {
      const res = await api.get(`/tour/${slug}`, {
        params: token ? { token } : undefined,
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
      return
    }
    if (!query.error || !axios.isAxiosError(query.error)) {
      setGate('denied')
      return
    }
    const status = query.error.response?.status
    const data = query.error.response?.data as { requires_password?: boolean; expired?: boolean; message?: string }
    if (status === 403 && data?.requires_password) {
      setGate('password')
    } else if (status === 410 || data?.expired) {
      setGate('expired')
    } else {
      setGate('denied')
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
    verifyPassword,
    verifyError,
    isVerifying,
    isLoading: query.isLoading,
    refetch: query.refetch,
  }
}
