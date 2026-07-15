import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatPrice(price: number): string {
  return new Intl.NumberFormat('fa-IR').format(price) + ' تومان'
}

export function formatNumber(num: number): string {
  return new Intl.NumberFormat('fa-IR').format(num)
}

export function toEnglishDigits(str: string): string {
  const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹']
  const arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩']
  return str
    .replace(/[۰-۹]/g, (d) => String(persian.indexOf(d)))
    .replace(/[٠-٩]/g, (d) => String(arabic.indexOf(d)))
}

export function normalizeMobile(mobile: string): string {
  let digits = toEnglishDigits(mobile).replace(/\D/g, '')
  if (digits.startsWith('98')) digits = '0' + digits.slice(2)
  if (!digits.startsWith('0')) digits = '0' + digits
  return digits
}

export function toPersianDigits(str: string): string {
  const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹']
  return str.replace(/\d/g, (d) => persianDigits[parseInt(d)])
}

/** Format ISO date or Date to Jalali string */
export function formatJalaliDate(value?: string | Date | null, withTime = false): string {
  if (!value) return '—'
  const date = typeof value === 'string' ? new Date(value) : value
  if (Number.isNaN(date.getTime())) return '—'
  try {
    const opts: Intl.DateTimeFormatOptions = withTime
      ? { calendar: 'persian', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }
      : { calendar: 'persian', year: 'numeric', month: 'long', day: 'numeric' }
    return new Intl.DateTimeFormat('fa-IR-u-ca-persian', opts).format(date)
  } catch {
    return toPersianDigits(date.toLocaleDateString('fa-IR'))
  }
}
