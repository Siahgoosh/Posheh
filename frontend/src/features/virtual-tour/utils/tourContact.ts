import { toEnglishDigits, normalizeMobile } from '@/lib/utils'
import type { TourSettings } from '../types'

/** Digits only, Iran mobile without leading 0 (for wa.me/98…). */
export function digitsForWhatsApp(phone: string | null | undefined): string | null {
  if (!phone) return null
  let digits = toEnglishDigits(phone).replace(/\D/g, '')
  if (digits.startsWith('98')) digits = digits.slice(2)
  if (digits.startsWith('0')) digits = digits.slice(1)
  if (digits.length < 10) return null
  return digits
}

export function buildWhatsAppUrl(phone: string | null | undefined): string | null {
  const digits = digitsForWhatsApp(phone)
  if (!digits) return null
  return `https://wa.me/98${digits}`
}

export function resolveTourWhatsAppPhone(
  settings?: TourSettings | null,
  officePhone?: string | null,
): string | null {
  const raw = settings?.whatsapp?.trim() || settings?.phone?.trim() || officePhone?.trim() || null
  if (!raw) return null
  return digitsForWhatsApp(raw) ? raw : null
}

export function resolveTourCallPhone(
  settings?: TourSettings | null,
  officePhone?: string | null,
): string | null {
  const raw = settings?.phone?.trim() || officePhone?.trim() || null
  if (!raw) return null
  const normalized = normalizeMobile(toEnglishDigits(raw))
  return normalized.length >= 10 ? normalized : raw
}

export function normalizeTourMobileInput(value: string): string {
  return toEnglishDigits(value).replace(/\D/g, '').slice(0, 11)
}
