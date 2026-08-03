import { useMemo } from 'react'
import { getDate, getMonth, getYear, newDate } from 'date-fns-jalali'
import { Calendar } from 'lucide-react'
import { Select, SelectOption } from '@/components/ui/select'
import { toPersianDigits } from '@/lib/utils'

const MONTHS = [
  'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
  'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
]

interface Props {
  value?: string
  onChange: (isoDate: string) => void
  required?: boolean
  label?: string
  hasError?: boolean
}

function daysInJalaliMonth(year: number, month: number): number {
  if (month <= 6) return 31
  if (month <= 11) return 30
  const mod = ((year + 38) * 31) % 128
  return mod < 31 ? 30 : 29
}

function parseIso(iso?: string): { y: number; m: number; d: number } | null {
  if (!iso) return null
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) return null
  return {
    y: getYear(date),
    m: getMonth(date) + 1,
    d: getDate(date),
  }
}

function toIso(y: number, m: number, d: number): string {
  const g = newDate(y, m - 1, d)
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${g.getFullYear()}-${pad(g.getMonth() + 1)}-${pad(g.getDate())}`
}

export function JalaliDatePicker({ value, onChange, required, label, hasError }: Props) {
  const parsed = parseIso(value)
  const currentJy = getYear(new Date())

  const years = useMemo(() => {
    const list: number[] = []
    for (let y = currentJy; y <= currentJy + 5; y++) list.push(y)
    return list
  }, [currentJy])

  const days = parsed
    ? Array.from({ length: daysInJalaliMonth(parsed.y, parsed.m) }, (_, i) => i + 1)
    : Array.from({ length: 31 }, (_, i) => i + 1)

  const setPart = (part: 'y' | 'm' | 'd', v: number) => {
    const y = part === 'y' ? v : (parsed?.y ?? currentJy)
    const m = part === 'm' ? v : (parsed?.m ?? 1)
    let d = part === 'd' ? v : (parsed?.d ?? 1)
    const maxD = daysInJalaliMonth(y, m)
    if (d > maxD) d = maxD
    onChange(toIso(y, m, d))
  }

  return (
    <div>
      {label && (
        <label className="text-sm text-muted mb-1 flex items-center gap-1.5">
          <Calendar className="h-3.5 w-3.5" />
          {label}{required ? ' *' : ''}
        </label>
      )}
      <div className="grid grid-cols-3 gap-2">
        <Select
          className={hasError ? 'ring-2 ring-danger/60 border-danger' : undefined}
          value={parsed ? String(parsed.d) : ''}
          onChange={(e) => e.target.value && setPart('d', Number(e.target.value))}
          required={required && !parsed}
        >
          <SelectOption value="">روز</SelectOption>
          {days.map((d) => (
            <SelectOption key={d} value={String(d)}>{toPersianDigits(String(d))}</SelectOption>
          ))}
        </Select>
        <Select
          className={hasError ? 'ring-2 ring-danger/60 border-danger' : undefined}
          value={parsed ? String(parsed.m) : ''}
          onChange={(e) => e.target.value && setPart('m', Number(e.target.value))}
          required={required && !parsed}
        >
          <SelectOption value="">ماه</SelectOption>
          {MONTHS.map((name, i) => (
            <SelectOption key={i + 1} value={String(i + 1)}>{name}</SelectOption>
          ))}
        </Select>
        <Select
          className={hasError ? 'ring-2 ring-danger/60 border-danger' : undefined}
          value={parsed ? String(parsed.y) : ''}
          onChange={(e) => e.target.value && setPart('y', Number(e.target.value))}
          required={required && !parsed}
        >
          <SelectOption value="">سال</SelectOption>
          {years.map((y) => (
            <SelectOption key={y} value={String(y)}>{toPersianDigits(String(y))}</SelectOption>
          ))}
        </Select>
      </div>
    </div>
  )
}
