import { useMemo } from 'react'
import { getDate as getJalaliDay, getMonth as getJalaliMonth, getYear as getJalaliYear } from 'date-fns-jalali'
import { newDate } from 'date-fns-jalali/newDate'
import { cn, toPersianDigits } from '@/lib/utils'

const MONTHS = [
  'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
  'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
]

function daysInJalaliMonth(year: number, monthIndex: number): number {
  for (let day = 31; day >= 29; day--) {
    const test = newDate(year, monthIndex, day)
    if (getJalaliMonth(test) === monthIndex) return day
  }
  return 30
}

function toIsoDate(year: number, monthIndex: number, day: number): string {
  return newDate(year, monthIndex, day, 12).toISOString().slice(0, 10)
}

interface JalaliDateInputProps {
  value: string
  onChange: (isoDate: string) => void
  className?: string
}

export function JalaliDateInput({ value, onChange, className }: JalaliDateInputProps) {
  const base = value ? new Date(`${value}T12:00:00`) : new Date()
  const year = getJalaliYear(base)
  const monthIndex = getJalaliMonth(base)
  const day = getJalaliDay(base)

  const years = useMemo(() => {
    const current = getJalaliYear(new Date())
    return Array.from({ length: 11 }, (_, i) => current - 5 + i)
  }, [])

  const maxDay = daysInJalaliMonth(year, monthIndex)
  const safeDay = Math.min(day, maxDay)

  const selectClass =
    'flex h-11 flex-1 rounded-xl border border-card-border bg-white/5 px-3 py-2 text-sm text-foreground focus:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary/20'

  const update = (y: number, m: number, d: number) => {
    const capped = Math.min(d, daysInJalaliMonth(y, m))
    onChange(toIsoDate(y, m, capped))
  }

  return (
    <div className={cn('flex gap-2', className)}>
      <select
        className={selectClass}
        value={safeDay}
        onChange={(e) => update(year, monthIndex, parseInt(e.target.value, 10))}
        aria-label="روز"
      >
        {Array.from({ length: maxDay }, (_, i) => i + 1).map((d) => (
          <option key={d} value={d}>{toPersianDigits(String(d))}</option>
        ))}
      </select>
      <select
        className={cn(selectClass, 'flex-[1.4]')}
        value={monthIndex}
        onChange={(e) => update(year, parseInt(e.target.value, 10), safeDay)}
        aria-label="ماه"
      >
        {MONTHS.map((label, i) => (
          <option key={label} value={i}>{label}</option>
        ))}
      </select>
      <select
        className={selectClass}
        value={year}
        onChange={(e) => update(parseInt(e.target.value, 10), monthIndex, safeDay)}
        aria-label="سال"
      >
        {years.map((y) => (
          <option key={y} value={y}>{toPersianDigits(String(y))}</option>
        ))}
      </select>
    </div>
  )
}

interface JalaliDateTimeInputProps {
  value: string
  onChange: (isoDateTime: string) => void
  className?: string
}

/** value: `YYYY-MM-DDTHH:mm` (local) for datetime-local compatibility */
export function JalaliDateTimeInput({ value, onChange, className }: JalaliDateTimeInputProps) {
  const datePart = value ? value.slice(0, 10) : new Date().toISOString().slice(0, 10)
  const timePart = value && value.length >= 16 ? value.slice(11, 16) : '10:00'

  return (
    <div className={cn('space-y-2', className)}>
      <JalaliDateInput
        value={datePart}
        onChange={(iso) => onChange(`${iso}T${timePart}`)}
      />
      <input
        type="time"
        value={timePart}
        onChange={(e) => onChange(`${datePart}T${e.target.value}`)}
        dir="ltr"
        className="flex h-11 w-full rounded-xl border border-card-border bg-white/5 px-4 py-2 text-sm text-foreground focus:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary/20"
        aria-label="ساعت"
      />
    </div>
  )
}
